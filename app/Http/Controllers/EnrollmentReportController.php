<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Schoolyr;
use App\Models\Gradelvl;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EnrollmentReportController extends Controller
{
    public function index(Request $request)
    {
        // --- Filters ---
        $sy_id = $request->filled('sy_id') ? (int) $request->input('sy_id') : null; // schoolyrs.id

        // Default to active School Year if none explicitly selected
        if (!$sy_id) {
            $currentSy = Schoolyr::where('active', true)->first();
            if ($currentSy) {
                $sy_id = (int) $currentSy->id;
            }
        }

        // Grade filter: accept either id or grade_level string (e.g. "Grade 1")
        $gradelvlParam = $request->input('gradelvl_id', '');
        $gradelvl_id = null;
        if ($gradelvlParam !== null && $gradelvlParam !== '' && $gradelvlParam !== 'all') {
            if (is_numeric($gradelvlParam)) {
                $gradelvl_id = (int) $gradelvlParam;
            } else {
                $gradeRow = Gradelvl::where('grade_level', $gradelvlParam)->first();
                if ($gradeRow) {
                    $gradelvl_id = (int) $gradeRow->id;
                }
            }
        }

        $student_id  = $request->filled('student_id')  ? (int) $request->input('student_id')  : null; // students.id
        $status      = trim((string) $request->input('status', ''));      // Enrolled / Not Enrolled
        $pay_status  = trim((string) $request->input('pay_status', ''));  // Paid / Partial / Unpaid
        $q           = trim((string) $request->input('q', ''));           // fuzzy text

        // Page size rules
        $perPage = 25;
        if ($student_id) {
            $perPage = 1;
        } elseif ($gradelvl_id && !$student_id) {
            $perPage = 5;
        }

        // --- Base query (NO pay_status filter here; we'll derive and filter after) ---
        $rows = Enrollment::query()
            ->with([
                'student.guardian',
                'student.gradelvl',
                'student.tuition',
                'student.optionalFees',
                'student.payments',
                'guardian',
                'schoolyr',
                'gradelvl',
            ])
            ->when($sy_id, fn($qr) => $qr->where('schoolyr_id', $sy_id))

            // Grade filter — match either enrollment.gradelvl_id or student's.gradelvl_id
            ->when($gradelvl_id, function ($qr) use ($gradelvl_id) {
                $qr->where(function ($w) use ($gradelvl_id) {
                    $w->where('gradelvl_id', $gradelvl_id)
                      ->orWhereHas('student', fn($s) => $s->where('gradelvl_id', $gradelvl_id));
                });
            })

            // Student filter
            ->when($student_id, function ($qr) use ($student_id) {
                $qr->where(function ($w) use ($student_id) {
                    $w->where('student_id', $student_id)
                      ->orWhereHas('student', fn($s) => $s->where('id', $student_id));
                });
            })

            // Enrollment status from DB
            ->when($status !== '', fn($qr) => $qr->where('status', $status))

            // Fuzzy query across Student + Guardian
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->whereHas('student', function ($s) use ($q) {
                        $s->where('s_firstname', 'like', "%{$q}%")
                          ->orWhere('s_middlename', 'like', "%{$q}%")
                          ->orWhere('s_lastname', 'like', "%{$q}%")
                          ->orWhere('s_contact', 'like', "%{$q}%")
                          ->orWhere('s_email', 'like', "%{$q}%");
                    })
                    ->orWhereHas('guardian', function ($g) use ($q) {
                        $g->where('g_contact', 'like', "%{$q}%")
                          ->orWhere('g_email', 'like', "%{$q}%")
                          ->orWhere('m_firstname', 'like', "%{$q}%")
                          ->orWhere('m_lastname', 'like', "%{$q}%")
                          ->orWhere('f_firstname', 'like', "%{$q}%")
                          ->orWhere('f_lastname', 'like', "%{$q}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->get();

        // If Enrollment has no rows (e.g., empty table), synthesize from Students
        if ($rows->count() === 0) {
            $rows = $this->buildFallbackFromStudents(
                $sy_id,
                $gradelvl_id,
                $student_id,
                $status,
                $pay_status,
                $q
            );
        }

        // Derived pay-status filter (server-side)
        if ($pay_status !== '') {
            $rows = $rows->filter(function ($en) use ($pay_status) {
                $s = $en->student ?? null;
                if (!$s) {
                    return false;
                }
                [, , , , , $derivedPay] = $this->computeFinance($s);
                $db = $en->payment_status ?? '';
                return ($derivedPay === $pay_status) || ($db === $pay_status);
            })->values();
        }

        // Manual pagination
        $page  = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();
        $enrollments = new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );

        // Page totals for CURRENT PAGE
        $pageTotals = [
            'tuition'   => 0.0,
            'optional'  => 0.0,
            'total_due' => 0.0,
            'paid'      => 0.0,
            'balance'   => 0.0,
        ];

        foreach ($enrollments as $en) {
            $s = $en->student ?? null;
            if (!$s) {
                continue;
            }
            [$base, $optional, $total, $paid, $balance] = $this->computeFinance($s);
            $pageTotals['tuition']   += $base;
            $pageTotals['optional']  += $optional;
            $pageTotals['total_due'] += $total;
            $pageTotals['paid']      += $paid;
            $pageTotals['balance']   += $balance;
        }

        // Dropdown data — ordered like migration (by id ascending)
        $schoolyrs = Schoolyr::orderBy('id', 'asc')->get(['id', 'school_year']);
        $gradelvls = Gradelvl::orderBy('id', 'asc')->get(['id', 'grade_level']);

        // Student label for the Student filter
        $currentStudentName = '';
        if ($student_id) {
            $st = Student::find($student_id);
            if ($st) {
                $currentStudentName = trim(implode(' ', array_filter([
                    $st->s_firstname,
                    $st->s_middlename,
                    $st->s_lastname,
                ])));
            }
        }

        // Group by Grade for current page
        $grouped = collect($enrollments->items())->groupBy(function ($en) {
            $s = $en->student ?? null;
            return $en->gradelvl?->grade_level
                ?? ($s?->gradelvl?->grade_level ?? $s?->s_gradelvl ?? '— No Grade —');
        });

        return view('auth.admindashboard.reports', compact(
            'enrollments',
            'pageTotals',
            'grouped',
            'schoolyrs',
            'gradelvls',
            'sy_id',
            'gradelvl_id',
            'student_id',
            'status',
            'pay_status',
            'q',
            'perPage',
            'currentStudentName'
        ));
    }

    /**
     * Print view — same filters/derivation as index (no pagination).
     */
    public function print(Request $request)
    {
        $sy_id = $request->filled('sy_id') ? (int) $request->input('sy_id') : null;

        // Default to active School Year if none explicitly selected
        if (!$sy_id) {
            $currentSy = Schoolyr::where('active', true)->first();
            if ($currentSy) {
                $sy_id = (int) $currentSy->id;
            }
        }

        // Grade filter: accept either id or grade_level string
        $gradelvlParam = $request->input('gradelvl_id', '');
        $gradelvl_id = null;
        if ($gradelvlParam !== null && $gradelvlParam !== '' && $gradelvlParam !== 'all') {
            if (is_numeric($gradelvlParam)) {
                $gradelvl_id = (int) $gradelvlParam;
            } else {
                $gradeRow = Gradelvl::where('grade_level', $gradelvlParam)->first();
                if ($gradeRow) {
                    $gradelvl_id = (int) $gradeRow->id;
                }
            }
        }

        $student_id  = $request->filled('student_id')  ? (int) $request->input('student_id')  : null;
        $status      = trim((string) $request->input('status', ''));
        $pay_status  = trim((string) $request->input('pay_status', ''));
        $q           = trim((string) $request->input('q', ''));

        $rows = Enrollment::query()
            ->with([
                'student.guardian',
                'student.gradelvl',
                'student.tuition',
                'student.optionalFees',
                'student.payments',
                'guardian',
                'schoolyr',
                'gradelvl',
            ])
            ->when($sy_id, fn($qr) => $qr->where('schoolyr_id', $sy_id))
            // Grade filter — match either enrollment.gradelvl_id or student's.gradelvl_id
            ->when($gradelvl_id, function ($qr) use ($gradelvl_id) {
                $qr->where(function ($w) use ($gradelvl_id) {
                    $w->where('gradelvl_id', $gradelvl_id)
                      ->orWhereHas('student', fn($s) => $s->where('gradelvl_id', $gradelvl_id));
                });
            })
            ->when($student_id, function ($qr) use ($student_id) {
                $qr->where(function ($w) use ($student_id) {
                    $w->where('student_id', $student_id)
                      ->orWhereHas('student', fn($s) => $s->where('id', $student_id));
                });
            })
            ->when($status !== '', fn($qr) => $qr->where('status', $status))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->whereHas('student', function ($s) use ($q) {
                        $s->where('s_firstname', 'like', "%{$q}%")
                          ->orWhere('s_middlename', 'like', "%{$q}%")
                          ->orWhere('s_lastname', 'like', "%{$q}%")
                          ->orWhere('s_contact', 'like', "%{$q}%")
                          ->orWhere('s_email', 'like', "%{$q}%");
                    })
                    ->orWhereHas('guardian', function ($g) use ($q) {
                        $g->where('g_contact', 'like', "%{$q}%")
                          ->orWhere('g_email', 'like', "%{$q}%")
                          ->orWhere('m_firstname', 'like', "%{$q}%")
                          ->orWhere('m_lastname', 'like', "%{$q}%")
                          ->orWhere('f_firstname', 'like', "%{$q}%")
                          ->orWhere('f_lastname', 'like', "%{$q}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->get();

        if ($rows->count() === 0) {
            $rows = $this->buildFallbackFromStudents(
                $sy_id,
                $gradelvl_id,
                $student_id,
                $status,
                $pay_status,
                $q
            );
        }

        if ($pay_status !== '') {
            $rows = $rows->filter(function ($en) use ($pay_status) {
                $s = $en->student ?? null;
                if (!$s) {
                    return false;
                }
                [, , , , , $derivedPay] = $this->computeFinance($s);
                $db = $en->payment_status ?? '';
                return ($derivedPay === $pay_status) || ($db === $pay_status);
            })->values();
        }

        $grouped = $rows->groupBy(function ($en) {
            $s = $en->student ?? null;
            return $en->gradelvl?->grade_level
                ?? ($s?->gradelvl?->grade_level ?? $s?->s_gradelvl ?? '— No Grade —');
        });

        return view('auth.admindashboard.reports-print', compact('grouped'));
    }

    /**
     * Student list for the Student filter (by grade).
     */
    public function students(Request $request)
    {
        $gradelvlId = $request->integer('gradelvl_id'); // optional

        $query = Student::query()
            ->select(['id', 's_firstname', 's_middlename', 's_lastname', 'gradelvl_id']);

        if ($gradelvlId) {
            $query->where('gradelvl_id', $gradelvlId);
        }

        $students = $query
            ->orderBy('s_lastname')
            ->orderBy('s_firstname')
            ->get()
            ->map(function ($s) {
                $name = trim(implode(' ', array_filter([
                    $s->s_firstname,
                    $s->s_middlename,
                    $s->s_lastname,
                ])));
                return [
                    'id'   => (string) $s->id,
                    'name' => $name ?: ('Student #' . $s->id),
                ];
            });

        return response()->json($students);
    }

    /**
     * Finance helper — mirrors the logic used in students.blade.
     * Returns: [base, optional, total, paid, balance, derivedPayStatus]
     */
    private function computeFinance($student): array
    {
        // Base = Tuition total_yearly if relation exists, else s_tuition_sum
        $base = 0.0;
        if (optional($student->tuition)->total_yearly !== null) {
            $base = (float) $student->tuition->total_yearly;
        } elseif ($student->s_tuition_sum !== null && $student->s_tuition_sum !== '') {
            $base = (float) $student->s_tuition_sum;
        }

        // Optional = selected optional fees (respect scope/active + pivot override)
        $optCollection = collect($student->optionalFees ?? []);
        $filtered = $optCollection->filter(function ($f) {
            $scopeOk  = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
            $activeOk = !property_exists($f, 'active') || (bool) $f->active;
            return $scopeOk && $activeOk;
        });

        $optional = (float) $filtered->sum(function ($f) {
            $amt = $f->pivot->amount_override ?? $f->amount;
            return (float) $amt;
        });

        $total = $base + $optional;

        // Sum of recorded payments
        $paidRecords = (float) ($student->payments()->sum('amount') ?? 0.0);

        // Balance priority: s_total_due (if stored) else derived from payments
        if ($student->s_total_due !== null && $student->s_total_due !== '') {
            $balance = max(0.0, (float) $student->s_total_due);
            $paid    = max($total - $balance, 0.0);
        } else {
            $paid    = min($paidRecords, $total);
            $balance = max(0.0, round($total - $paid, 2));
        }

        // Derived pay status for filtering — matches UI options: Paid / Partial / Unpaid
        $derivedPay = $balance <= 0.01 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');

        return [
            round($base, 2),
            round($optional, 2),
            round($total, 2),
            round($paid, 2),
            round($balance, 2),
            $derivedPay,
        ];
    }

    /**
     * Build synthetic "enrollment-like" rows from Students when the enrollment table has no data.
     * Applies the same filters as the index/print flows.
     */
    private function buildFallbackFromStudents(
        ?int $sy_id,
        ?int $gradelvl_id,
        ?int $student_id,
        string $status,
        string $pay_status,
        string $q
    ): Collection {
        $studentsQ = Student::query()
            ->with(['guardian', 'gradelvl', 'tuition.optionalFees', 'optionalFees', 'payments'])
            ->when($sy_id, fn($qr) => $qr->where('schoolyr_id', $sy_id))
            ->when($gradelvl_id, fn($qr) => $qr->where('gradelvl_id', $gradelvl_id))
            ->when($student_id, fn($qr) => $qr->where('id', $student_id))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('s_firstname', 'like', "%{$q}%")
                      ->orWhere('s_middlename', 'like', "%{$q}%")
                      ->orWhere('s_lastname', 'like', "%{$q}%")
                      ->orWhere('s_contact', 'like', "%{$q}%")
                      ->orWhere('s_email', 'like', "%{$q}%")
                      ->orWhereHas('guardian', function ($g) use ($q) {
                          $g->where('g_contact', 'like', "%{$q}%")
                            ->orWhere('g_email', 'like', "%{$q}%")
                            ->orWhere('m_firstname', 'like', "%{$q}%")
                            ->orWhere('m_lastname', 'like', "%{$q}%")
                            ->orWhere('f_firstname', 'like', "%{$q}%")
                            ->orWhere('f_lastname', 'like', "%{$q}%");
                      });
                });
            })
            ->orderBy('s_lastname')
            ->orderBy('s_firstname');

        $students = $studentsQ->get();

        $rows = $students->map(function (Student $s) {
            [, , , , , $derivedPay] = $this->computeFinance($s);
            $derivedEnroll = ($s->enrollment_status === 'Enrolled') ? 'Enrolled' : 'Not Enrolled';

            return (object) [
                'id'             => -100000 - $s->id, // unique negative id
                'student'        => $s,
                'guardian'       => $s->guardian,
                'schoolyr'       => null,
                'gradelvl'       => $s->gradelvl,
                'status'         => $derivedEnroll,
                'payment_status' => $derivedPay,
            ];
        });

        // Apply any remaining filters (status / pay_status) not covered above
        if ($status !== '') {
            $rows = $rows->filter(fn($en) => ($en->status === $status))->values();
        }
        if ($pay_status !== '') {
            $rows = $rows->filter(fn($en) => ($en->payment_status === $pay_status))->values();
        }

        return $rows;
    }
}
