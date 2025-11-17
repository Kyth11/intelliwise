<?php

namespace App\Http\Controllers;

use App\Mail\ParentAccountCredentials;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Gradelvl;
use App\Models\Schoolyr; // add at top
use App\Models\OptionalFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
public function index()
{
    $students = Student::with(['guardian', 'optionalFees'])->get()->groupBy('s_gradelvl');
    $gradelvls = Gradelvl::all();
    $tuitions = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();
    $optionalFees = OptionalFee::orderBy('name')->get();

    $guardians = Guardian::all()->map(function ($g) {
        $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
        $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
        $label = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #' . $g->id));
        $g->display_name = $label;
        $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
        return $g;
    });

    // Fetch all school years
    $schoolyrs = Schoolyr::orderBy('school_year')->get();

    // Set current SY explicitly
    $current = Schoolyr::where('school_year', '2025-2026')->first();

    return view('auth.admindashboard.students', compact(
        'students',
        'gradelvls',
        'tuitions',
        'guardians',
        'optionalFees',
        'schoolyrs',
        'current' // now always 2025-2026
    ));
}





    public function create()
    {
        // Current school year
        $current = \App\Models\Schoolyr::where('school_year', '2025-2026')->first()
            ?? \App\Models\Schoolyr::orderBy('school_year')->first();

        // Guardians mapping
        $guardians = Guardian::all()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #' . $g->id));
            $g->display_name = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        // Optional fees
        $optionalFees = OptionalFee::where('active', true)->orderBy('name')->get();

        // Determine view based on role
        $role = Auth::user()?->role;
        $view = ($role === 'faculty')
            ? 'auth.facultydashboard.enroll-student'
            : 'auth.admindashboard.enroll-student';

        return view($view, compact('current', 'guardians', 'optionalFees'));
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
            $mid = trim($s->s_middlename ?? '');
            $name = trim($s->s_firstname . ' ' . ($mid ? $mid . ' ' : '') . $s->s_lastname);
            return ['id' => (string) $s->lrn, 'name' => $name]; // id = LRN for the UI
        });

        return response()->json($items);
    }

    /** Prefill payload for a picked suggestion (LRN-based) */
    public function prefill($id)
    {
        $s = Student::with(['guardian'])->where('lrn', $id)->firstOrFail();

        $religion = $s->s_religion ?? $s->religion ?? '';

        $payload = [
            'id' => $s->lrn,
            's_firstname' => $s->s_firstname,
            's_middlename' => $s->s_middlename,
            's_lastname' => $s->s_lastname,
            's_gender' => $s->s_gender,
            's_birthdate' => $s->s_birthdate ? substr((string) $s->s_birthdate, 0, 10) : '',
            's_citizenship' => $s->s_citizenship,
            's_address' => $s->s_address,
            's_religion' => $religion,
            's_contact' => $s->s_contact,
            's_email' => $s->s_email,
            'sped_has' => $s->sped_has,
            'sped_desc' => $s->sped_desc,
            's_gradelvl' => $s->s_gradelvl,
            'previous_school' => $s->previous_school,

            'guardian_id' => optional($s->guardian)->id,
            'g_address' => optional($s->guardian)->g_address,
            'm_firstname' => optional($s->guardian)->m_firstname,
            'm_middlename' => optional($s->guardian)->m_middlename,
            'm_lastname' => optional($s->guardian)->m_lastname,
            'm_contact' => optional($s->guardian)->m_contact,
            'm_email' => optional($s->guardian)->m_email,
            'm_occupation' => optional($s->guardian)->m_occupation,
            'f_firstname' => optional($s->guardian)->f_firstname,
            'f_middlename' => optional($s->guardian)->f_middlename,
            'f_lastname' => optional($s->guardian)->f_lastname,
            'f_contact' => optional($s->guardian)->f_contact,
            'f_email' => optional($s->guardian)->f_email,
            'f_occupation' => optional($s->guardian)->f_occupation,
            'alt_guardian_details' => optional($s->guardian)->alt_guardian_details,
        ];

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        // LRN rule: new/transferee -> unique; old -> exists
        $type = (string) $request->input('enroll_type', 'new');
        $lrnRules = ['required', 'digits_between:10,12'];
        if ($type === 'old') {
            $lrnRules[] = Rule::exists('students', 'lrn');
        } else {
            $lrnRules[] = Rule::unique('students', 'lrn');
        }

        $request->validate([
            'lrn' => $lrnRules,
            's_firstname' => 'required|string',
            's_lastname' => 'required|string',
            's_address' => 'required|string',
            's_birthdate' => 'required|date',
            's_gradelvl' => 'required|string',
            's_citizenship' => 'nullable|string',
            's_religion' => 'nullable|string',
            'student_optional_fee_ids' => ['array'],
            'student_optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
            'guardian_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // ----- Guardian upsert
            if ($request->guardian_id === 'new') {
                $hasMother = $request->filled('m_firstname') || $request->filled('m_lastname');
                $hasFather = $request->filled('f_firstname') || $request->filled('f_lastname');
                if (!$hasMother && !$hasFather) {
                    return back()->with('error', 'Provide at least Mother or Father details.')->withInput();
                }

                $guardianData = [
                    'g_address' => $request->g_address,
                    'g_contact' => $request->m_contact ?: ($request->f_contact ?: null),
                    'g_email' => $request->g_email ?: ($request->m_email ?: ($request->f_email ?: null)),
                    'm_firstname' => $request->m_firstname,
                    'm_middlename' => $request->m_middlename,
                    'm_lastname' => $request->m_lastname,
                    'm_contact' => $request->m_contact,
                    'm_email' => $request->m_email,
                    'm_occupation' => $request->m_occupation,
                    'f_firstname' => $request->f_firstname,
                    'f_middlename' => $request->f_middlename,
                    'f_lastname' => $request->f_lastname,
                    'f_contact' => $request->f_contact,
                    'f_email' => $request->f_email,
                    'f_occupation' => $request->f_occupation,
                    'alt_guardian_details' => $request->alt_guardian_details,
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
                'lrn' => $request->lrn,
                's_firstname' => $request->s_firstname,
                's_middlename' => $request->s_middlename,
                's_lastname' => $request->s_lastname,
                's_birthdate' => $request->s_birthdate,
                's_address' => $request->s_address,
                's_citizenship' => $request->s_citizenship,
                's_religion' => $request->s_religion,
                's_contact' => $request->s_contact,
                's_email' => $request->s_email,
                's_gradelvl' => $request->s_gradelvl,
                'enrollment_status' => $request->enrollment_status ?? 'Enrolled',
                'payment_status' => $request->payment_status ?? 'Unpaid',
                's_tuition_sum' => $baseTotal,
                's_optional_total' => round($studentOptSum, 2),
                's_total_due' => $total,
                'tuition_id' => $tuitionId,
                'guardian_id' => $guardian->id,
                's_gender' => $request->s_gender,
                'previous_school' => $request->previous_school,
                'sped_has' => $request->sped_has,
                'sped_desc' => $request->sped_desc,
            ];

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

            // ----- Create/Reset guardian user credentials + send email
            $emailTarget = trim((string) ($guardian->g_email ?: $guardian->m_email ?: $guardian->f_email ?: ''));
            $emailed = false;

            // Use mother first, fallback to father
            $parentLast = $guardian->m_lastname ?: $guardian->f_lastname;
            $parentFirst = $guardian->m_firstname ?: $guardian->f_firstname;

            $username = $this->uniqueUsername($this->buildUsernameBaseFromParent($parentLast, $parentFirst));
            $passwordPlain = $this->buildPasswordFromParent($parentLast);

            $displayName = $this->guardianDisplayName($guardian);

            $existingUser = User::where('guardian_id', $guardian->id)->first();
            if ($existingUser) {
                $username = $existingUser->username ?: $username;
                if ($this->hasColumn('users', 'password')) {
                    $existingUser->password = Hash::make($passwordPlain);
                }
                if ($this->hasColumn('users', 'email') && !$existingUser->email && $emailTarget) {
                    $existingUser->email = $emailTarget;
                }
                $existingUser->save();
            } else {
                $user = new User();
                $user->name = $displayName;
                if ($this->hasColumn('users', 'username'))
                    $user->username = $username;
                if ($this->hasColumn('users', 'password'))
                    $user->password = Hash::make($passwordPlain);
                if ($this->hasColumn('users', 'role'))
                    $user->role = 'guardian';
                if ($this->hasColumn('users', 'email'))
                    $user->email = $emailTarget ?: null;
                if ($this->hasColumn('users', 'guardian_id'))
                    $user->guardian_id = $guardian->id;
                $user->save();
            }

            if ($emailTarget !== '') {
                try {
                    Mail::to($emailTarget)->send(new ParentAccountCredentials(
                        guardianName: $displayName,
                        studentName: trim(($student->s_firstname ?? '') . ' ' . ($student->s_lastname ?? '')),
                        username: $username,
                        password: $passwordPlain,
                        appUrl: config('app.url')
                    ));
                    $emailed = true;
                } catch (\Throwable $e) {
                    Log::warning('Email send failed', ['error' => $e->getMessage(), 'guardian_id' => $guardian->id]);
                }
            } else {
                Log::info('No guardian email on file; skipping credentials email.', ['guardian_id' => $guardian->id]);
            }

            DB::commit();

            // Redirect to the proper list
            $role = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with(
                'success',
                ($type === 'old' ? 'Student updated. ' : 'Student saved. ')
                . ($emailed ? 'Credentials emailed to parent/guardian.' : 'No email on file.')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Student store failed', ['error' => $e->getMessage()]);
            $role = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with('error', 'Failed to enroll student. Check inputs/logs.');
        }
    }

    /** Route param is {lrn}; do not rely on PK */
    public function update(Request $request, $lrn)
    {
        $request->validate([
            's_firstname' => 'required|string',
            's_lastname' => 'required|string',
            's_address' => 'required|string',
            's_birthdate' => 'required|date',
            's_gradelvl' => 'nullable|string',
            's_citizenship' => 'nullable|string',
            's_religion' => 'nullable|string',
            'student_optional_fee_ids' => ['array'],
            'student_optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
        ]);

        try {
            $student = Student::where('lrn', $lrn)->firstOrFail();

            $old_base = (float) ($student->s_tuition_sum ?? 0);
            $old_opt = (float) ($student->s_optional_total ?? 0);
            $old_total = $old_base + $old_opt;
            $old_balance = (float) ($student->s_total_due ?? $old_total);
            $paid_so_far = max($old_total - $old_balance, 0);

            $grade = $request->s_gradelvl ?? $student->s_gradelvl;
            [$tuitionId, $baseTotal] = $this->resolveTuition($grade);

            $studentOptIds = collect($request->input('student_optional_fee_ids', []))->filter()->unique()->values();
            $studentOptSum = $studentOptIds->isNotEmpty()
                ? (float) OptionalFee::whereIn('id', $studentOptIds)->sum('amount')
                : 0.0;

            $new_total = round($baseTotal + $studentOptSum, 2);
            $new_balance = max($new_total - $paid_so_far, 0);
            $new_status = $new_balance <= 0 ? 'Paid' : ($paid_so_far > 0 ? 'Partial' : 'Unpaid');

            $updateData = [
                's_firstname' => $request->s_firstname,
                's_middlename' => $request->s_middlename,
                's_lastname' => $request->s_lastname,
                's_birthdate' => $request->s_birthdate,
                's_address' => $request->s_address,
                's_citizenship' => $request->s_citizenship,
                's_religion' => $request->s_religion,
                's_contact' => $request->s_contact,
                's_email' => $request->s_email,
                's_gradelvl' => $grade,
                'enrollment_status' => $request->enrollment_status ?? $student->enrollment_status,
                'payment_status' => $new_status,
                's_tuition_sum' => $baseTotal,
                's_optional_total' => round($studentOptSum, 2),
                's_total_due' => $new_balance,
                'tuition_id' => $tuitionId,
                's_gender' => $request->s_gender ?? $student->s_gender,
                'previous_school' => $request->previous_school ?? $student->previous_school,
                'sped_has' => $request->sped_has ?? $student->sped_has,
                'sped_desc' => $request->sped_desc ?? $student->sped_desc,
            ];

            $student->update($this->filterColumns('students', $updateData));

            $sync = [];
            foreach ($studentOptIds as $fid) {
                $sync[$fid] = [];
            }
            if (method_exists($student, 'optionalFees')) {
                $student->optionalFees()->sync($sync);
            }

            $role = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.students' : 'admin.students.index';
            return redirect()->route($route)->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            Log::error('Student update failed', ['error' => $e->getMessage(), 'lrn' => $lrn]);
            $role = Auth::user()?->role;
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
        if (!$gradeLevel)
            return [null, 0];

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
        $last = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $last));
        $first = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $first));
        $initial = $first !== '' ? substr($first, 0, 2) : 'xx'; // first 2 letters of first name, fallback 'xx'
        return ($last !== '' ? $last : 'parent') . $initial; // e.g., davisjo
    }

    private function uniqueUsername(string $base): string
    {
        $u = $base;
        $i = 2;
        while (User::where('username', $u)->exists()) {
            $u = $base . $i;
            $i++;
            if ($i > 9999)
                break;
        }
        return $u;
    }

    private function buildPasswordFromParent(?string $last): string
    {
        $last = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $last));
        return ($last !== '' ? $last : 'parent') . '123'; // e.g., davis123
    }
}
