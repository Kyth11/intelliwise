<?php

namespace App\Http\Controllers;

use App\Mail\ParentAccountCredentials;
use App\Mail\StudentCorMail;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Schedule;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use App\Models\OptionalFee;
use App\Models\CorHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    // in App\Http\Controllers\StudentController.php

    public function index(Request $request)
    {
        // Selected school year from the query string, if any
        $selectedId = $request->query('schoolyr_id');
        $current = null;

        if ($selectedId) {
            $current = Schoolyr::find($selectedId);
        }

        // Fallback: active school year, or first by school_year
        if (!$current) {
            $current = $this->currentSchoolYear();
        }

        // Base query
        $studentsQuery = Student::with(['guardian', 'optionalFees']);

        // Limit to the chosen school year if we have one and a matching column
        if ($current) {
            if ($this->hasColumn('students', 'schoolyr_id')) {
                $studentsQuery->where('schoolyr_id', $current->id);
            } elseif ($this->hasColumn('students', 'school_year')) {
                $studentsQuery->where('school_year', $current->school_year);
            }
        }

        $students     = $studentsQuery->get()->groupBy('s_gradelvl');
        $gradelvls    = Gradelvl::all();
        $tuitions     = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();
        $optionalFees = OptionalFee::orderBy('name')->get();

        $guardians = Guardian::all()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #' . $g->id));
            $g->display_name    = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        // All school years (for dropdown, etc.)
        $schoolyrs = Schoolyr::orderBy('school_year')->get();

        return view('auth.admindashboard.students', compact(
            'students',
            'gradelvls',
            'tuitions',
            'guardians',
            'optionalFees',
            'schoolyrs',
            'current'
        ));
    }

    public function create()
    {
        // Current school year (same logic as Settings blade snippet)
        $current = $this->currentSchoolYear();

        // Guardians mapping
        $guardians = Guardian::all()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #' . $g->id));
            $g->display_name    = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        // Tuition rows for the fees summary modal in the enrollment form
        // We load all, but the Blade will filter by $current->school_year.
        $tuitionsQuery = Tuition::query();

        if ($current && $current->school_year) {
            $tuitionsQuery->where('school_year', $current->school_year);
        }

        $tuitions = $tuitionsQuery
            ->orderBy('grade_level')
            ->orderBy('school_year')
            ->get();

        // Optional fees – show all, Blade will filter:
        //  - active is NULL or 1
        //  - scope in ['student','both','any']
        $optionalFees = OptionalFee::orderBy('name')->get();

        // Determine view based on role
        $role = Auth::user()?->role;
        $view = ($role === 'faculty')
            ? 'auth.facultydashboard.enroll-student'
            : 'auth.admindashboard.enroll-student';

        return view($view, compact('current', 'guardians', 'optionalFees', 'tuitions'));
    }

    /** Live search (names or LRN) for suggest dropdowns */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = Student::query()
            ->select(['lrn', 's_firstname', 's_middlename', 's_lastname'])
            ->where(function ($w) use ($q) {
                $w->where('s_firstname', 'like', "%{$q}%")
                    ->orWhere('s_lastname', 'like', "%{$q}%")
                    ->orWhere('lrn', 'like', "%{$q}%");
            })
            ->orderBy('s_lastname')->orderBy('s_firstname')
            ->limit(20)->get();

        $items = $rows->map(function (Student $s) {
            $mid  = trim($s->s_middlename ?? '');
            $name = trim($s->s_firstname . ' ' . ($mid ? $mid . ' ' : '') . $s->s_lastname);
            return ['id' => (string) $s->lrn, 'name' => $name]; // id = LRN for the UI
        });

        return response()->json($items);
    }

    /** Prefill payload for a picked suggestion (LRN-based) */
    public function prefill($id)
    {
        // Load guardian AND optional fees
        $s = Student::with(['guardian', 'optionalFees'])->where('lrn', $id)->firstOrFail();

        $religion = $s->s_religion ?? $s->religion ?? '';

        $payload = [
            'id'             => $s->lrn,
            's_firstname'    => $s->s_firstname,
            's_middlename'   => $s->s_middlename,
            's_lastname'     => $s->s_lastname,
            's_gender'       => $s->s_gender,
            's_birthdate'    => $s->s_birthdate ? substr((string) $s->s_birthdate, 0, 10) : '',
            's_citizenship'  => $s->s_citizenship,
            's_address'      => $s->s_address,
            's_religion'     => $religion,
            's_contact'      => $s->s_contact,
            's_email'        => $s->s_email,
            'sped_has'       => $s->sped_has,
            'sped_desc'      => $s->sped_desc,
            's_gradelvl'     => $s->s_gradelvl,
            'previous_school'=> $s->previous_school,

            'guardian_id'          => optional($s->guardian)->id,
            'g_address'            => optional($s->guardian)->g_address,
            'm_firstname'          => optional($s->guardian)->m_firstname,
            'm_middlename'         => optional($s->guardian)->m_middlename,
            'm_lastname'           => optional($s->guardian)->m_lastname,
            'm_contact'            => optional($s->guardian)->m_contact,
            'm_email'              => optional($s->guardian)->m_email,
            'm_occupation'         => optional($s->guardian)->m_occupation,
            'f_firstname'          => optional($s->guardian)->f_firstname,
            'f_middlename'         => optional($s->guardian)->f_middlename,
            'f_lastname'           => optional($s->guardian)->f_lastname,
            'f_contact'            => optional($s->guardian)->f_contact,
            'f_email'              => optional($s->guardian)->f_email,
            'f_occupation'         => optional($s->guardian)->f_occupation,
            'alt_guardian_details' => optional($s->guardian)->alt_guardian_details,

            // NEW: current optional fee IDs for this student
            'student_optional_fee_ids' => $s->optionalFees->pluck('id')->values(),
        ];

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        // LRN rule: new/transferee -> unique; old -> exists
        $type     = (string) $request->input('enroll_type', 'new');
        $lrnRules = ['required', 'digits_between:10,12'];
        if ($type === 'old') {
            $lrnRules[] = Rule::exists('students', 'lrn');
        } else {
            $lrnRules[] = Rule::unique('students', 'lrn');
        }

        $request->validate([
            'lrn'                       => $lrnRules,
            's_firstname'               => 'required|string',
            's_lastname'                => 'required|string',
            's_address'                 => 'required|string',
            's_birthdate'               => 'required|date',
            's_gradelvl'                => 'required|string',
            's_citizenship'             => 'nullable|string',
            's_religion'                => 'nullable|string',
            'student_optional_fee_ids'  => ['array'],
            'student_optional_fee_ids.*'=> ['integer', 'exists:optional_fees,id'],
            'guardian_id'               => 'required'
        ]);

        DB::beginTransaction();
        try {
            // Current school year (for tagging enrollment)
            $currentSy = $this->currentSchoolYear();

            // ----- Guardian upsert
            if ($request->guardian_id === 'new') {
                $hasMother = $request->filled('m_firstname') || $request->filled('m_lastname');
                $hasFather = $request->filled('f_firstname') || $request->filled('f_lastname');
                if (!$hasMother && !$hasFather) {
                    return back()->with('error', 'Provide at least Mother or Father details.')->withInput();
                }

                $guardianData = [
                    'g_address'           => $request->g_address,
                    'g_contact'           => $request->m_contact ?: ($request->f_contact ?: null),
                    'g_email'             => $request->g_email ?: ($request->m_email ?: ($request->f_email ?: null)),
                    'm_firstname'         => $request->m_firstname,
                    'm_middlename'        => $request->m_middlename,
                    'm_lastname'          => $request->m_lastname,
                    'm_contact'           => $request->m_contact,
                    'm_email'             => $request->m_email,
                    'm_occupation'        => $request->m_occupation,
                    'f_firstname'         => $request->f_firstname,
                    'f_middlename'        => $request->f_middlename,
                    'f_lastname'          => $request->f_lastname,
                    'f_contact'           => $request->f_contact,
                    'f_email'             => $request->f_email,
                    'f_occupation'        => $request->f_occupation,
                    'alt_guardian_details'=> $request->alt_guardian_details,
                ];
                $guardian = Guardian::create($this->filterColumns('guardians', $guardianData));
            } else {
                $guardian = Guardian::lockForUpdate()->findOrFail((int) $request->guardian_id);
            }

            // ----- Tuition & optional fees
            [$tuitionId, $baseTotal] = $this->resolveTuition($request->s_gradelvl);
            $studentOptIds = collect($request->input('student_optional_fee_ids', []))->filter()->unique()->values();
            $studentOptSum = $studentOptIds->isNotEmpty()
                ? (float) OptionalFee::whereIn('id', $studentOptIds)->sum('amount')
                : 0.0;
            $total = round($baseTotal + $studentOptSum, 2);

            // ----- Shared student fields
            $studentData = [
                'lrn'              => $request->lrn,
                's_firstname'      => $request->s_firstname,
                's_middlename'     => $request->s_middlename,
                's_lastname'       => $request->s_lastname,
                's_birthdate'      => $request->s_birthdate,
                's_address'        => $request->s_address,
                's_citizenship'    => $request->s_citizenship,
                's_religion'       => $request->s_religion,
                's_contact'        => $request->s_contact,
                's_email'          => $request->s_email,
                's_gradelvl'       => $request->s_gradelvl,
                'enrollment_status'=> $request->enrollment_status ?? 'Enrolled',
                'payment_status'   => $request->payment_status ?? 'Unpaid',
                's_tuition_sum'    => $baseTotal,
                's_optional_total' => round($studentOptSum, 2),
                's_total_due'      => $total,
                'tuition_id'       => $tuitionId,
                'guardian_id'      => $guardian->id,
                's_gender'         => $request->s_gender,
                'previous_school'  => $request->previous_school,
                'sped_has'         => $request->sped_has,
                'sped_desc'        => $request->sped_desc,
            ];

            // Attach current school year to student (flexible: supports schoolyr_id OR school_year column)
            if ($currentSy) {
                if ($this->hasColumn('students', 'schoolyr_id')) {
                    $studentData['schoolyr_id'] = $currentSy->id;
                } elseif ($this->hasColumn('students', 'school_year')) {
                    $studentData['school_year'] = $currentSy->school_year;
                }
            }

            if ($type === 'old') {
                // Update existing by LRN (not PK)
                $student = Student::where('lrn', $request->lrn)->lockForUpdate()->firstOrFail();
                $student->fill($this->filterColumns('students', $studentData));
                $student->save();

                // Sync optional fees
                $sync = [];
                foreach ($studentOptIds as $fid) {
                    $sync[$fid] = [];
                }
                if (method_exists($student, 'optionalFees')) {
                    $student->optionalFees()->sync($sync);
                }
            } else {
                // Create new record for new/transferee
                $student = Student::create($this->filterColumns('students', $studentData));

                if ($studentOptIds->isNotEmpty() && method_exists($student, 'optionalFees')) {
                    $attach = [];
                    foreach ($studentOptIds as $fid) {
                        $attach[$fid] = [];
                    }
                    try {
                        $student->optionalFees()->attach($attach);
                    } catch (\Throwable $e) {
                        Log::warning('Optional fee attach failed', ['err' => $e->getMessage()]);
                    }
                }
            }

            // Make temporary properties for COR rendering (not persisted)
            $student->enroll_type   = $type;
            $guardian->display_name = $this->guardianDisplayName($guardian);

            // ----- COR generation (Certificate of Registration)
            $schoolYearStr = $currentSy?->school_year ?? null;
            $gradeLevel    = $student->s_gradelvl;

            $tuitionFee      = (float) $baseTotal;
            $miscFee         = 0.0;
            $otherFees       = (float) $studentOptSum;
            $totalSchoolFees = round($tuitionFee + $miscFee + $otherFees, 2);

            // Build subjects for COR from schedules (grade level + SY)
            $corSubjects = $this->buildCorSubjectsForStudent($student, $schoolYearStr);

            $corBilling = [
                'date_enrolled'      => now(),
                'tuition_fee'        => $tuitionFee,
                'misc_fee'           => $miscFee,
                'other_fees'         => $otherFees,
                'total_school_fees'  => $totalSchoolFees,
                'subjects'           => $corSubjects,
            ];

            // Registration number — SY digits + LRN + random
            $registrationNo = sprintf(
                '%s-%s-%s',
                preg_replace('/[^0-9]/', '', $schoolYearStr ?? ''),
                $student->lrn,
                strtoupper(Str::random(4))
            );

            $semester   = null;
            $courseYear = $gradeLevel;
            $signerName = Auth::user()?->name ?? 'School Registrar';

            // Render COR HTML once
            $corHtml = view('emails.cor', [
                'student'        => $student,
                'guardian'       => $guardian,
                'billing'        => $corBilling,
                'schoolYear'     => $schoolYearStr,
                'semester'       => $semester,
                'courseYear'     => $courseYear,
                'registrationNo' => $registrationNo,
                'signerName'     => $signerName,
            ])->render();

            // Save COR header record
            CorHeader::create([
                'student_id'        => $student->lrn,
                'guardian_id'       => $guardian->id,
                'school_year'       => $schoolYearStr,
                'semester'          => $semester,
                'course_year'       => $courseYear,
                'registration_no'   => $registrationNo,
                'cor_no'            => null,
                'date_enrolled'     => $corBilling['date_enrolled'],
                'tuition_fee'       => $tuitionFee,
                'misc_fee'          => $miscFee,
                'other_fees'        => $otherFees,
                'total_school_fees' => $totalSchoolFees,
                'signed_by_name'    => $signerName,
                'signed_by_user_id' => Auth::id(),
                'html_snapshot'     => $corHtml,
            ]);

            // ----- Guardian user credentials: create once, then only send COR on subsequent enrollments
            $emailTarget     = trim((string) ($guardian->g_email ?: $guardian->m_email ?: $guardian->f_email ?: ''));
            $emailed         = false;
            $credentialsSent = false;

            // Use mother first, fallback to father
            $parentLast  = $guardian->m_lastname ?: $guardian->f_lastname;
            $parentFirst = $guardian->m_firstname ?: $guardian->f_firstname;
            $displayName = $this->guardianDisplayName($guardian);

            $username      = null;
            $passwordPlain = null;

            $existingUser = User::where('guardian_id', $guardian->id)->first();

            if ($existingUser) {
                // Keep existing username/password; only ensure email is filled in
                if ($this->hasColumn('users', 'email') && $emailTarget && !$existingUser->email) {
                    $existingUser->email = $emailTarget;
                    $existingUser->save();
                }
            } else {
                // First time: create guardian user account and generate credentials
                $usernameBase  = $this->buildUsernameBaseFromParent($parentLast, $parentFirst);
                $username      = $this->uniqueUsername($usernameBase);
                $passwordPlain = $this->buildPasswordFromParent($parentLast);

                $user       = new User();
                $user->name = $displayName;

                if ($this->hasColumn('users', 'username')) {
                    $user->username = $username;
                }
                if ($this->hasColumn('users', 'password')) {
                    $user->password = Hash::make($passwordPlain);
                }
                if ($this->hasColumn('users', 'role')) {
                    $user->role = 'guardian';
                }
                if ($this->hasColumn('users', 'email')) {
                    $user->email = $emailTarget ?: null;
                }
                if ($this->hasColumn('users', 'guardian_id')) {
                    $user->guardian_id = $guardian->id;
                }

                $user->save();
            }

            if ($emailTarget !== '') {
                try {
                    // Send credentials only if this is the first time (no existing user before)
                    if (!$existingUser && $username && $passwordPlain) {
                        Mail::to($emailTarget)->send(new ParentAccountCredentials(
                            guardianName: $displayName,
                            studentName: trim(($student->s_firstname ?? '') . ' ' . ($student->s_lastname ?? '')),
                            username: $username,
                            password: $passwordPlain,
                            appUrl: config('app.url')
                        ));
                        $credentialsSent = true;
                    }

                    // COR email is always sent on enrollment
                    Mail::to($emailTarget)->send(new StudentCorMail(
                        $student,
                        $guardian,
                        $corBilling,
                        $schoolYearStr,
                        $semester,
                        $courseYear,
                        $registrationNo,
                        $signerName,
                        $corHtml
                    ));

                    $emailed = true;
                } catch (\Throwable $e) {
                    Log::warning('Email send failed', ['error' => $e->getMessage(), 'guardian_id' => $guardian->id]);
                }
            } else {
                Log::info('No guardian email on file; skipping credentials/COR email.', ['guardian_id' => $guardian->id]);
            }

            DB::commit();

            // Redirect to the proper list
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';

            $msg = $type === 'old' ? 'Student updated. ' : 'Student saved. ';
            if ($emailed) {
                if ($credentialsSent) {
                    $msg .= 'Parent/guardian credentials and COR emailed.';
                } else {
                    $msg .= 'COR emailed to parent/guardian.';
                }
            } else {
                $msg .= 'No email on file.';
            }

            return redirect()->route($route)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Student store failed', ['error' => $e->getMessage()]);
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with('error', 'Failed to enroll student. Check inputs/logs.');
        }
    }

    /** Route param is {lrn}; do not rely on PK */
    public function update(Request $request, $lrn)
    {
        $request->validate([
            's_firstname'               => 'required|string',
            's_lastname'                => 'required|string',
            's_address'                 => 'required|string',
            's_birthdate'               => 'required|date',
            's_gradelvl'                => 'nullable|string',
            's_citizenship'             => 'nullable|string',
            's_religion'                => 'nullable|string',
            'student_optional_fee_ids'  => ['array'],
            'student_optional_fee_ids.*'=> ['integer', 'exists:optional_fees,id'],
        ]);

        try {
            $student = Student::where('lrn', $lrn)->firstOrFail();

            $old_base    = (float) ($student->s_tuition_sum ?? 0);
            $old_opt     = (float) ($student->s_optional_total ?? 0);
            $old_total   = $old_base + $old_opt;
            $old_balance = (float) ($student->s_total_due ?? $old_total);
            $paid_so_far = max($old_total - $old_balance, 0);

            $grade = $request->s_gradelvl ?? $student->s_gradelvl;
            [$tuitionId, $baseTotal] = $this->resolveTuition($grade);

            $studentOptIds = collect($request->input('student_optional_fee_ids', []))->filter()->unique()->values();
            $studentOptSum = $studentOptIds->isNotEmpty()
                ? (float) OptionalFee::whereIn('id', $studentOptIds)->sum('amount')
                : 0.0;

            $new_total   = round($baseTotal + $studentOptSum, 2);
            $new_balance = max($new_total - $paid_so_far, 0);
            $new_status  = $new_balance <= 0 ? 'Paid' : ($paid_so_far > 0 ? 'Partial' : 'Unpaid');

            $updateData = [
                's_firstname'      => $request->s_firstname,
                's_middlename'     => $request->s_middlename,
                's_lastname'       => $request->s_lastname,
                's_birthdate'      => $request->s_birthdate,
                's_address'        => $request->s_address,
                's_citizenship'    => $request->s_citizenship,
                's_religion'       => $request->s_religion,
                's_contact'        => $request->s_contact,
                's_email'          => $request->s_email,
                's_gradelvl'       => $grade,
                'enrollment_status'=> $request->enrollment_status ?? $student->enrollment_status,
                'payment_status'   => $new_status,
                's_tuition_sum'    => $baseTotal,
                's_optional_total' => round($studentOptSum, 2),
                's_total_due'      => $new_balance,
                'tuition_id'       => $tuitionId,
                's_gender'         => $request->s_gender ?? $student->s_gender,
                'previous_school'  => $request->previous_school ?? $student->previous_school,
                'sped_has'         => $request->sped_has ?? $student->sped_has,
                'sped_desc'        => $request->sped_desc ?? $student->sped_desc,
            ];

            $student->update($this->filterColumns('students', $updateData));

            $sync = [];
            foreach ($studentOptIds as $fid) {
                $sync[$fid] = [];
            }
            if (method_exists($student, 'optionalFees')) {
                $student->optionalFees()->sync($sync);
            }

            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            Log::error('Student update failed', ['error' => $e->getMessage(), 'lrn' => $lrn]);
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with('error', 'Failed to update student.');
        }
    }

    public function destroy($lrn)
    {
        try {
            $student = Student::where('lrn', $lrn)->firstOrFail();
            $student->delete();
            return redirect()->back()->with('success', 'Student archived successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to archive student.');
        }
    }

    private function resolveTuition(?string $gradeLevel): array
    {
        if (!$gradeLevel) {
            return [null, 0];
        }

        $t = Tuition::where('grade_level', $gradeLevel)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        return [$t?->id, (float) ($t?->total_yearly ?? 0)];
    }

    private function filterColumns(string $table, array $data): array
    {
        $kept = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($table, $k)) {
                $kept[$k] = $v;
            }
        }
        return $kept;
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function guardianDisplayName(Guardian $g): string
    {
        $mother = trim(collect([$g->m_firstname, $g->m_lastname])->filter()->implode(' '));
        $father = trim(collect([$g->f_firstname, $g->f_lastname])->filter()->implode(' '));
        return $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Parent/Guardian'));
    }

    private function buildUsernameBaseFromParent(?string $last, ?string $first): string
    {
        $last    = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $last));
        $first   = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $first));
        $initial = $first !== '' ? substr($first, 0, 2) : 'xx'; // first 2 letters of first name, fallback 'xx'
        return ($last !== '' ? $last : 'parent') . $initial;   // e.g., davisjo
    }

    private function uniqueUsername(string $base): string
    {
        $u = $base;
        $i = 2;
        while (User::where('username', $u)->exists()) {
            $u = $base . $i;
            $i++;
            if ($i > 9999) {
                break;
            }
        }
        return $u;
    }

    private function buildPasswordFromParent(?string $last): string
    {
        $last = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $last));
        return ($last !== '' ? $last : 'parent') . '123'; // e.g., davis123
    }

    /**
     * Build the subjects array for COR from schedules, using
     * the student's grade level and the given (or active) school year.
     */
    private function buildCorSubjectsForStudent(Student $student, ?string $schoolYearStr): array
    {
        // Resolve school year: use given or active
        if (!$schoolYearStr) {
            $schoolYearStr = Schoolyr::where('active', true)->value('school_year');
        }
        if (!$schoolYearStr) {
            return [];
        }

        // Resolve grade name from student record, then map to Gradelvl.id
        $gradeName = $student->s_gradelvl;
        if (!$gradeName) {
            return [];
        }

        $gradeLevelId = Gradelvl::where('grade_level', $gradeName)->value('id');
        if (!$gradeLevelId) {
            return [];
        }

        // Fetch schedules for this grade level in this school year
        $schedules = Schedule::with(['subject', 'faculty'])
            ->where('school_year', $schoolYearStr)
            ->where('gradelvl_id', $gradeLevelId)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // Group by subject so each COR row is one subject with combined days/times
        return $schedules
            ->groupBy('subject_id')
            ->map(function ($rows) {
                $first   = $rows->first();
                $subject = $first->subject;
                $faculty = $first->faculty;

                // Subject code + title (code may be blank if not stored)
                $code  = $subject->subject_code ?? '';
                $title = $subject->subject_name ?? '';

                // Combine days: e.g., "Monday/Wednesday/Friday"
                $day = $rows->pluck('day')
                    ->filter()
                    ->unique()
                    ->implode('/');

                // Combine time ranges: e.g., "8:00 AM–9:00 AM, 1:00 PM–2:00 PM"
                $time = $rows->map(function ($row) {
                        if (!$row->class_start || !$row->class_end) {
                            return null;
                        }

                        $st = date('g:i A', strtotime($row->class_start));
                        $et = date('g:i A', strtotime($row->class_end));

                        return "{$st}–{$et}";
                    })
                    ->filter()
                    ->unique()
                    ->implode(', ');

                // Teacher name (supports first_name/last_name or f_firstname/f_lastname)
                $teacherName = trim(
                    ($faculty->first_name  ?? $faculty->f_firstname ?? '') . ' ' .
                    ($faculty->last_name   ?? $faculty->f_lastname  ?? '')
                );

                return [
                    'code'    => $code,
                    'title'   => $title,
                    'day'     => $day ?: '-',
                    'time'    => $time ?: '-',
                    'teacher' => $teacherName ?: '-',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Helper: get the "current" Schoolyr, same logic as Settings blade:
     * active=true first, fallback to first by school_year.
     */
    private function currentSchoolYear(): ?Schoolyr
    {
        return Schoolyr::where('active', true)->first()
            ?? Schoolyr::orderBy('school_year')->first();
    }
}
