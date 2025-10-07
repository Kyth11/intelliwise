<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Gradelvl;
use App\Models\OptionalFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ✅ use facade for clean IDE types
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * List students (grouped by grade level) — admin view.
     */
    public function index()
    {
        $students  = Student::with(['guardian','optionalFees'])->get()->groupBy('s_gradelvl');
        $gradelvls = Gradelvl::all();
        $tuitions  = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();
        $optionalFees = OptionalFee::orderBy('name')->get();

        $guardians = Guardian::all()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #'.$g->id));
            $g->display_name    = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        return view('auth.admindashboard.students', compact(
            'students', 'gradelvls', 'tuitions', 'guardians', 'optionalFees'
        ));
    }

    /**
     * Show enrollment form (faculty sees faculty layout; admin sees admin layout).
     */
    public function create()
    {
        $guardians = Guardian::all()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother.' & '.$father) : ($mother ?: ($father ?: 'Guardian #'.$g->id));
            $g->display_name    = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        $optionalFees = OptionalFee::where('active', true)->orderBy('name')->get();

        // ✅ IDE-friendly
        $role = Auth::user()?->role;
        $view = $role === 'faculty'
            ? 'auth.facultydashboard.enroll-student'
            : 'auth.admindashboard.enroll-student';

        return view($view, compact('guardians', 'optionalFees'));
    }

    /**
     * Store new student (works for admin & faculty; redirects correctly by role).
     */
    public function store(Request $request)
    {
        $request->validate([
            's_firstname'   => 'required|string',
            's_lastname'    => 'required|string',
            's_address'     => 'required|string',
            's_birthdate'   => 'required|date',
            's_gradelvl'    => 'required|string',
            's_citizenship' => 'nullable|string',
            's_religion'    => 'nullable|string',
            'student_optional_fee_ids'   => ['array'],
            'student_optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
        ]);

        DB::beginTransaction();
        try {
            $guardian_id = null;

            if ($request->guardian_id === 'new') {
                $hasMother = $request->filled('m_firstname') || $request->filled('m_lastname');
                $hasFather = $request->filled('f_firstname') || $request->filled('f_lastname');
                if (!$hasMother && !$hasFather) {
                    return back()->with('error', 'Please provide at least Mother or Father details.')->withInput();
                }

                $request->validate([
                    'g_address'    => 'nullable|string',
                    'm_firstname'  => 'nullable|string',
                    'm_middlename' => 'nullable|string',
                    'm_lastname'   => 'nullable|string',
                    'm_contact'    => 'nullable|string',
                    'm_email'      => 'nullable|email',
                    'f_firstname'  => 'nullable|string',
                    'f_middlename' => 'nullable|string',
                    'f_lastname'   => 'nullable|string',
                    'f_contact'    => 'nullable|string',
                    'f_email'      => 'nullable|email',
                    'username'     => 'nullable|string|unique:users,username',
                    'password'     => 'nullable|string|min:6',
                ]);

                $guardian = Guardian::create([
                    'g_address'    => $request->g_address,
                    'g_contact'    => $request->m_contact ?: ($request->f_contact ?: null),
                    'g_email'      => $request->m_email   ?: ($request->f_email   ?: null),
                    'm_firstname'  => $request->m_firstname,
                    'm_middlename' => $request->m_middlename,
                    'm_lastname'   => $request->m_lastname,
                    'm_contact'    => $request->m_contact,
                    'm_email'      => $request->m_email,
                    'f_firstname'  => $request->f_firstname,
                    'f_middlename' => $request->f_middlename,
                    'f_lastname'   => $request->f_lastname,
                    'f_contact'    => $request->f_contact,
                    'f_email'      => $request->f_email,
                ]);
                $guardian_id = $guardian->id;

                if ($request->has('has_login')) {
                    $motherName  = trim(collect([$guardian->m_firstname, $guardian->m_lastname])->filter()->implode(' '));
                    $fatherName  = trim(collect([$guardian->f_firstname, $guardian->f_lastname])->filter()->implode(' '));
                    $displayName = $motherName && $fatherName ? ($motherName.' & '.$fatherName)
                                  : ($motherName ?: ($fatherName ?: 'Guardian Account'));

                    $username = $request->username ?: Str::slug(($guardian->m_lastname ?: $guardian->f_lastname) . '.parents');
                    $password = $request->password ?: Str::random(8);
                    $base = $username; $i = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $base . $i; $i++;
                    }

                    User::create([
                        'name'        => $displayName,
                        'username'    => $username,
                        'password'    => bcrypt($password),
                        'role'        => 'guardian',
                        'guardian_id' => $guardian->id,
                    ]);
                }
            } elseif (!empty($request->guardian_id)) {
                $guardian_id = $request->guardian_id;
            }

            [$tuitionId, $baseTotal] = $this->resolveTuition($request->s_gradelvl);

            $studentOptIds = collect($request->input('student_optional_fee_ids', []))->filter()->unique()->values();
            $studentOptSum = $studentOptIds->isNotEmpty()
                ? (float) OptionalFee::whereIn('id', $studentOptIds)->sum('amount')
                : 0.0;

            $student = Student::create([
                's_firstname'        => $request->s_firstname,
                's_middlename'       => $request->s_middlename,
                's_lastname'         => $request->s_lastname,
                's_birthdate'        => $request->s_birthdate,
                's_address'          => $request->s_address,
                's_citizenship'      => $request->s_citizenship,
                's_religion'         => $request->s_religion,
                's_contact'          => $request->s_contact,
                's_email'            => $request->s_email,
                's_gradelvl'         => $request->s_gradelvl,
                'enrollment_status'  => $request->enrollment_status ?? 'Enrolled',
                'payment_status'     => $request->payment_status ?? 'Not Paid',
                's_tuition_sum'      => $baseTotal,
                's_optional_total'   => round($studentOptSum, 2),
                's_total_due'        => round($baseTotal + $studentOptSum, 2),
                'tuition_id'         => $tuitionId,
                'guardian_id'        => $guardian_id,
            ]);

            if ($studentOptIds->isNotEmpty()) {
                $attach = [];
                foreach ($studentOptIds as $fid) {
                    $attach[$fid] = ['amount_override' => null];
                }
                $student->optionalFees()->attach($attach);
            }

            DB::commit();

            // ✅ Role-aware redirect (no 403 for faculty)
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.dashboard' : 'admin.dashboard';
            return redirect()->route($route)->with('success', 'Student enrolled successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.dashboard' : 'admin.dashboard';
            return redirect()->route($route)->with('error', 'Failed to enroll student. Please check your input.');
        }
    }

    /**
     * Update existing student.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            's_firstname'   => 'required|string',
            's_lastname'    => 'required|string',
            's_address'     => 'required|string',
            's_birthdate'   => 'required|date',
            's_gradelvl'    => 'nullable|string',
            's_citizenship' => 'nullable|string',
            's_religion'    => 'nullable|string',
            'student_optional_fee_ids'   => ['array'],
            'student_optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
        ]);

        try {
            $student = Student::findOrFail($id);

            $grade = $request->s_gradelvl ?? $student->s_gradelvl;
            [$tuitionId, $baseTotal] = $this->resolveTuition($grade);

            $studentOptIds = collect($request->input('student_optional_fee_ids', []))->filter()->unique()->values();
            $studentOptSum = $studentOptIds->isNotEmpty()
                ? (float) OptionalFee::whereIn('id', $studentOptIds)->sum('amount')
                : 0.0;

            $student->update([
                's_firstname'       => $request->s_firstname,
                's_middlename'      => $request->s_middlename,
                's_lastname'        => $request->s_lastname,
                's_birthdate'       => $request->s_birthdate,
                's_address'         => $request->s_address,
                's_citizenship'     => $request->s_citizenship,
                's_religion'        => $request->s_religion,
                's_contact'         => $request->s_contact,
                's_email'           => $request->s_email,
                's_gradelvl'        => $grade,
                'enrollment_status' => $request->enrollment_status ?? $student->enrollment_status,
                'payment_status'    => $request->payment_status ?? $student->payment_status,
                's_tuition_sum'     => $baseTotal,
                's_optional_total'  => round($studentOptSum, 2),
                's_total_due'       => round($baseTotal + $studentOptSum, 2),
                'tuition_id'        => $tuitionId,
            ]);

            $sync = [];
            foreach ($studentOptIds as $fid) {
                $sync[$fid] = ['amount_override' => null];
            }
            $student->optionalFees()->sync($sync);

            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.dashboard' : 'admin.students';
            return redirect()->route($route)->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            $role  = Auth::user()?->role;
            $route = $role === 'faculty' ? 'faculty.dashboard' : 'admin.students';
            return redirect()->route($route)->with('error', 'Failed to update student.');
        }
    }

    /**
     * Delete student (archive).
     */
    public function destroy($id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();
            return redirect()->back()->with('success', 'Student archived successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to archive student.');
        }
    }

    /**
     * Pick the latest Tuition row for a given grade level.
     * Returns [tuition_id|null, total_yearly|0].
     */
    private function resolveTuition(?string $gradeLevel): array
    {
        if (!$gradeLevel) {
            return [null, 0];
        }

        $t = Tuition::where('grade_level', $gradeLevel)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        return [$t?->id, $t?->total_yearly ?? 0];
    }
}
