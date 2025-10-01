<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Gradelvl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * Show students list (grouped by grade level).
     */
    public function index()
    {
        // Group students by their s_gradelvl for the grouped table UI
        $students   = Student::with('guardian')->get()->groupBy('s_gradelvl');

        // Needed by the edit-student modal partial
        $gradelvls  = Gradelvl::all();
        $tuitions   = Tuition::orderBy('updated_at', 'desc')->get();

        return view('auth.admindashboard.students', compact('students', 'gradelvls', 'tuitions'));
    }

    /**
     * Store new student. If guardian_id === 'new', create guardian (and optionally a User).
     */
    public function store(Request $request)
    {
        $request->validate([
            's_firstname' => 'required|string',
            's_lastname'  => 'required|string',
            's_address'   => 'required|string',
            's_birthdate' => 'required|date',
            's_gradelvl'  => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $guardian_id = null;

            if ($request->guardian_id === 'new') {
                $request->validate([
                    'g_firstname' => 'required|string',
                    'g_lastname'  => 'required|string',
                    'g_address'   => 'required|string',
                    'g_contact'   => 'required|string',
                    'g_email'     => 'nullable|email',
                ]);

                $guardian = Guardian::create([
                    'g_firstname'  => $request->g_firstname,
                    'g_middlename' => $request->g_middlename ?? null,
                    'g_lastname'   => $request->g_lastname,
                    'g_address'    => $request->g_address,
                    'g_contact'    => $request->g_contact,
                    'g_email'      => $request->g_email,
                ]);
                $guardian_id = $guardian->id;

                if ($request->has_login) {
                    $username = $request->username ?? Str::slug($guardian->g_firstname . '.' . $guardian->g_lastname);
                    $password = $request->password ?? Str::random(8);
                    $base = $username;
                    $i = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $base . $i;
                        $i++;
                    }
                    User::create([
                        'name'        => $guardian->g_firstname . ' ' . $guardian->g_lastname,
                        'username'    => $username,
                        'password'    => bcrypt($password),
                        'role'        => 'guardian',
                        'guardian_id' => $guardian->id,
                    ]);
                }
            } elseif (!empty($request->guardian_id)) {
                $guardian_id = $request->guardian_id;
            }

            // Resolve tuition from grade level
            [$tuitionId, $tuitionTotal] = $this->resolveTuition($request->s_gradelvl);

            Student::create([
                's_firstname'        => $request->s_firstname,
                's_middlename'       => $request->s_middlename ?? null,
                's_lastname'         => $request->s_lastname,
                's_address'          => $request->s_address,
                's_birthdate'        => $request->s_birthdate,
                's_contact'          => $request->s_contact ?? null,
                's_email'            => $request->s_email ?? null,
                's_gradelvl'         => $request->s_gradelvl,
                'enrollment_status'  => $request->enrollment_status ?? 'Enrolled',
                'payment_status'     => $request->payment_status ?? 'Not Paid',
                's_tuition_sum'      => $tuitionTotal,   // from tuitions table
                'tuition_id'         => $tuitionId,      // link to tuition row
                'guardian_id'        => $guardian_id,
            ]);

            DB::commit();
            return redirect()->route('admin.dashboard')->with('success', 'Student enrolled successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.dashboard')->with('error', 'Failed to enroll student. Please check your input.');
        }
    }

    /**
     * Update existing student.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            's_firstname' => 'required|string',
            's_lastname'  => 'required|string',
            's_address'   => 'required|string',
            's_birthdate' => 'required|date',
            's_gradelvl'  => 'nullable|string',
        ]);

        try {
            $student = Student::findOrFail($id);

            $grade = $request->s_gradelvl ?? $student->s_gradelvl;
            [$tuitionId, $tuitionTotal] = $this->resolveTuition($grade);

            $student->update([
                's_firstname'        => $request->s_firstname,
                's_middlename'       => $request->s_middlename,
                's_lastname'         => $request->s_lastname,
                's_address'          => $request->s_address,
                's_birthdate'        => $request->s_birthdate,
                's_contact'          => $request->s_contact,
                's_email'            => $request->s_email,
                's_gradelvl'         => $grade,
                'enrollment_status'  => $request->enrollment_status ?? $student->enrollment_status,
                'payment_status'     => $request->payment_status ?? $student->payment_status,
                's_tuition_sum'      => $tuitionTotal, // keep synced from tuitions table
                'tuition_id'         => $tuitionId,
            ]);

            return redirect()->route('admin.students')->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.students')->with('error', 'Failed to update student.');
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
