<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuardianDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user && $user->role === 'guardian') {
            $guardian = Guardian::with([
                'students.tuition',
                'students.optionalFees',
                'students.gradelvl',
                'students.payments',
            ])->find($user->guardian_id);

            if (!$guardian) {
                return view('auth.guardiandashboard', [
                    'guardian'      => null,
                    'children'      => collect(),
                    'kpiLearners'   => 0,
                    'kpiBalance'    => 0.0,
                    'announcements' => collect(),
                ]);
            }

            $children    = $guardian->students ?? collect();
            $kpiLearners = $children->count();

            $tuitionMap = \App\Models\Tuition::orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get()
                ->keyBy('grade_level');

            $kpiBalance = 0.0;

            foreach ($children as $st) {
                $base = 0.0;
                if ($st->relationLoaded('tuition') && $st->tuition) {
                    $base = (float) $st->tuition->total_yearly;
                } elseif (!empty($st->s_tuition_sum)) {
                    $base = (float) preg_replace('/[^\d.]+/', '', (string) $st->s_tuition_sum);
                } else {
                    $base = (float) optional($tuitionMap->get($st->s_gradelvl))->total_yearly;
                }

                $opt = 0.0;
                if ($st->relationLoaded('optionalFees')) {
                    $opt = (float) $st->optionalFees->sum(function ($f) {
                        return (float) ($f->pivot->amount_override ?? $f->amount ?? 0);
                    });
                }

                $origTotal      = $base + $opt;
                $currentBalance = isset($st->s_total_due) ? (float) $st->s_total_due : $origTotal;
                $st->_computed_base = $base;
                $st->_optional_sum  = $opt;
                $st->_computed_due  = $origTotal;
                $kpiBalance += $currentBalance;
            }

            $learnerGradeLevelIds = $children
                ->pluck('gradelvl.id')
                ->filter()
                ->unique()
                ->values();

            $announcements = Announcement::with(['gradelvls'])
                ->where(function ($q) use ($learnerGradeLevelIds) {
                    $q->whereDoesntHave('gradelvls')
                      ->orWhereHas('gradelvls', function ($qq) use ($learnerGradeLevelIds) {
                          if ($learnerGradeLevelIds->isNotEmpty()) {
                              $qq->whereIn('gradelvl_id', $learnerGradeLevelIds);
                          } else {
                              $qq->whereRaw('1=0');
                          }
                      });
                })
                ->latest()
                ->get();

            return view('auth.guardiandashboard', [
                'guardian'      => $guardian,
                'children'      => $children,
                'kpiLearners'   => $kpiLearners,
                'kpiBalance'    => $kpiBalance,
                'announcements' => $announcements,
            ]);
        }

        $guardians = Guardian::with(['students', 'user'])->get();
        $students  = Student::with('guardian')->get();

        return view('auth.admindashboard.guardians', compact('guardians', 'students'));
    }

    /**
     * Reports page (guardian).
     * Makes no assumption about subject column names.
     */
  public function reports(\Illuminate\Http\Request $request)
{
    $user = \Illuminate\Support\Facades\Auth::user();
    abort_unless($user && $user->role === 'guardian', 403);

    // 1) School Years
    $schoolyrs = collect();
    if (class_exists(\App\Models\SchoolYear::class)) {
        $schoolyrs = \App\Models\SchoolYear::orderByDesc('id')->get(['id','school_year','display_year']);
    } else {
        try {
            $schoolyrs = \Illuminate\Support\Facades\DB::table('schoolyrs')
                ->select('id', 'school_year', \Illuminate\Support\Facades\DB::raw('NULL as display_year'))
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            $schoolyrs = collect();
        }
    }

    $schoolyrId = (int) $request->query('schoolyr_id');
    $studentId  = (int) $request->query('student_id');

    $rows = [];
    $generalAverage = null;

    if ($schoolyrId && $studentId) {
        // Pull grades (with subject relation if available)
        if (class_exists(\App\Models\Grade::class)) {
            $gradeQuery = \App\Models\Grade::query()
                ->where('student_id', $studentId)
                ->where('schoolyr_id', $schoolyrId);

            if (method_exists(\App\Models\Grade::class, 'subject')) {
                $gradeQuery->with('subject'); // Eloquent relation preferred
            }

            $gradeRecords = $gradeQuery->get();
        } else {
            $gradeRecords = \Illuminate\Support\Facades\DB::table('grades')
                ->where('student_id', $studentId)
                ->where('schoolyr_id', $schoolyrId)
                ->get();
        }

        $finals = [];

        foreach ($gradeRecords as $g) {
            // ---------- SUBJECT LABEL (robust) ----------
            $label = null;

            // A) If relation exists: use related subject fields
            if (is_object($g) && isset($g->subject) && is_object($g->subject)) {
                $label = $g->subject->subject_name
                    ?? $g->subject->name
                    ?? $g->subject->title
                    ?? $g->subject->subject_code
                    ?? null;
            }

            // B) Direct columns on the grade row
            if (!$label && isset($g->subject_name) && $g->subject_name !== '') {
                $label = $g->subject_name;
            }
            if (!$label && isset($g->subject_code) && $g->subject_code !== '') {
                $label = $g->subject_code;
            }

            // C) `subject` column could be plain text OR JSON — handle both
            if (!$label && isset($g->subject) && $g->subject !== null) {
                if (is_string($g->subject)) {
                    $try = trim($g->subject);
                    if ($try !== '') {
                        if ($try[0] === '{' || $try[0] === '[') {
                            $decoded = json_decode($try, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $label = $decoded['subject_name']
                                    ?? $decoded['name']
                                    ?? $decoded['title']
                                    ?? $decoded['subject']
                                    ?? $decoded['subject_code']
                                    ?? $try; // fallback to raw
                            } else {
                                $label = $try;
                            }
                        } else {
                            $label = $try; // plain text
                        }
                    }
                } elseif (is_object($g->subject)) {
                    // Defensive duplicate of (A)
                    $label = $g->subject->subject_name
                        ?? $g->subject->name
                        ?? $g->subject->title
                        ?? $g->subject->subject_code
                        ?? null;
                }
            }

            $subject = $label ?: 'Subject';

            // ---------- QUARTERS / FINAL ----------
            $q1 = is_numeric($g->q1 ?? null) ? (float) $g->q1 : null;
            $q2 = is_numeric($g->q2 ?? null) ? (float) $g->q2 : null;
            $q3 = is_numeric($g->q3 ?? null) ? (float) $g->q3 : null;
            $q4 = is_numeric($g->q4 ?? null) ? (float) $g->q4 : null;

            if (isset($g->final) && is_numeric($g->final)) {
                $final = (float) $g->final;
            } else {
                $qs = array_filter([$q1,$q2,$q3,$q4], fn($n) => $n !== null);
                $final = count($qs) ? round(array_sum($qs) / count($qs)) : null;
            }

            [$desc, $abbr] = $this->descriptorFromFinal($final);
            $remark = $final === null ? null : ($final >= 75 ? 'PASSED' : 'FAILED');

            $rows[] = [
                'subject'         => $subject,
                'q1'              => $q1,
                'q2'              => $q2,
                'q3'              => $q3,
                'q4'              => $q4,
                'final'           => $final,
                'remark'          => $remark,
                'descriptor'      => $desc,
                'descriptor_abbr' => $abbr,
            ];

            if ($final !== null) $finals[] = $final;
        }

        // Sort by clean label
        usort($rows, fn($a, $b) => strcasecmp($a['subject'] ?? '', $b['subject'] ?? ''));

        if (!empty($finals)) {
            $generalAverage = round(array_sum($finals) / count($finals));
        }
    }

    $guardian = $user->guardian_id
        ? \App\Models\Guardian::with(['students.gradelvl','students.payments','students.optionalFees','students.tuition'])
            ->find($user->guardian_id)
        : null;

    return view('auth.guardiandashboard.reports', [
        'schoolyrs'       => $schoolyrs,
        'rows'            => $rows,
        'generalAverage'  => $generalAverage,
        'schoolyrId'      => $schoolyrId ?: null,
        'studentId'       => $studentId ?: null,
        'guardian'        => $guardian,
    ]);
}


    private function descriptorFromFinal($final): array
    {
        if ($final === null) return [null, null];
        if ($final >= 90) return ['Outstanding', 'O'];
        if ($final >= 85) return ['Very Satisfactory', 'VS'];
        if ($final >= 80) return ['Satisfactory', 'S'];
        if ($final >= 75) return ['Fairly Satisfactory', 'FS'];
        return ['Did Not Meet Expectations', 'DNME'];
    }

    // --- rest unchanged (store/update/destroy/selfUpsert) ---
    public function store(Request $request)
    {
        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email',
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($request) {
            $guardian = Guardian::create([
                'g_address'    => $request->g_address,
                'g_contact'    => $request->g_contact,
                'g_email'      => $request->g_email,
                'm_firstname'  => $request->g_firstname,
                'm_middlename' => $request->g_middlename,
                'm_lastname'   => $request->g_lastname,
                'f_firstname'  => null,
                'f_middlename' => null,
                'f_lastname'   => null,
            ]);

            User::create([
                'name'        => trim($request->g_firstname.' '.$request->g_lastname),
                'username'    => $request->username,
                'password'    => bcrypt($request->password),
                'role'        => 'guardian',
                'guardian_id' => $guardian->id,
            ]);
        });

        return back()->with('success', 'Guardian account created successfully!');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role === 'guardian' && (int)$user->guardian_id !== (int)$id) {
            abort(403, 'Unauthorized');
        }

        $guardian = Guardian::with('user')->findOrFail($id);
        $currentUserId = optional($guardian->user)->id;

        $request->validate([
            'g_firstname' => 'nullable|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'nullable|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => ['required','string','max:255', Rule::unique('users','username')->ignore($currentUserId)],
            'password'    => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $guardian) {
            $data = [
                'g_address' => $request->g_address,
                'g_contact' => $request->g_contact,
                'g_email'   => $request->g_email,
            ];

            if ($request->filled('g_firstname'))  $data['m_firstname']  = $request->g_firstname;
            if ($request->filled('g_middlename')) $data['m_middlename'] = $request->g_middlename;
            if ($request->filled('g_lastname'))   $data['m_lastname']   = $request->g_lastname;

            $guardian->update($data);
            $guardian->refresh();

            $displayFirst = $guardian->m_firstname ?: $guardian->f_firstname ?: '';
            $displayLast  = $guardian->m_lastname  ?: $guardian->f_lastname  ?: '';

            if ($guardian->user) {
                $payload = [
                    'name'     => trim($displayFirst.' '.$displayLast),
                    'username' => $request->username,
                ];
                if ($request->filled('password')) {
                    $payload['password'] = bcrypt($request->password);
                }
                $guardian->user->update($payload);
            } else {
                User::create([
                    'name'        => trim($displayFirst.' '.$displayLast),
                    'username'    => $request->username,
                    'password'    => bcrypt($request->filled('password') ? $request->password : 'password123'),
                    'role'        => 'guardian',
                    'guardian_id' => $guardian->id,
                ]);
            }
        });

        return back()->with('success', 'Guardian updated successfully!');
    }

    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        DB::transaction(function () use ($guardian) {
            User::where('guardian_id', $guardian->id)->delete();
            $guardian->delete();
        });

        return back()->with('success', 'Guardian account deleted successfully!');
    }

    public function selfUpsert(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'guardian', 403);

        if (!empty($user->guardian_id)) {
            return $this->update($request, $user->guardian_id);
        }

        $request->validate([
            'g_firstname'  => 'nullable|string|max:255',
            'g_middlename' => 'nullable|string|max:255',
            'g_lastname'   => 'nullable|string|max:255',
            'g_email'   => 'nullable|email|max:255|unique:guardians,g_email',
            'g_address' => 'nullable|string|max:255',
            'g_contact' => 'nullable|string|max:255',
            'username' => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $user) {
            $guardian = Guardian::create([
                'g_address'    => $request->input('g_address'),
                'g_contact'    => $request->input('g_contact'),
                'g_email'      => $request->input('g_email'),
                'm_firstname'  => $request->input('g_firstname'),
                'm_middlename' => $request->input('g_middlename'),
                'm_lastname'   => $request->input('g_lastname'),
                'f_firstname'  => null,
                'f_middlename' => null,
                'f_lastname'   => null,
            ]);

            $displayFirst = $guardian->m_firstname ?: $guardian->f_firstname ?: '';
            $displayLast  = $guardian->m_lastname  ?: $guardian->f_lastname  ?: '';

            $user->guardian_id = $guardian->id;
            $user->name        = trim($displayFirst.' '.$displayLast) ?: ($user->name ?? '');
            $user->username    = $request->input('username');
            if ($request->filled('password')) {
                $user->password = bcrypt($request->input('password'));
            }
            $user->save();
        });

        return back()->with('success', 'Profile created and linked.');
    }
}
