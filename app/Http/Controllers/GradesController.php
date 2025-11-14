<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Schoolyr;
use App\Models\Gradelvl;
use App\Models\Grade;
use App\Models\QuarterLock; // quarter open/close flags per SY + Grade Level
use Illuminate\Http\Request;

class GradesController extends Controller
{
    /**
     * Admin Grades page (view-only table + quarter access banner/toggles)
     */
    public function index(Request $request)
    {
        $schoolyrs   = Schoolyr::orderByDesc('id')->get();

        // Optional filters coming from the form
        $schoolyrId  = $request->input('schoolyr_id');
        $gradeLevel  = $request->input('grade_level');  // e.g., "Grade 1"
        $studentLrn  = $request->input('student_lrn');  // ✅ matches the view

        // Grade levels for the filter
        $gradelvls   = Gradelvl::orderBy('grade_level')->get();

        // Students list (filtered by grade level when selected)
        $students = Student::select('lrn', 's_firstname', 's_middlename', 's_lastname', 's_gradelvl')
            ->when($gradeLevel, fn ($q) => $q->where('s_gradelvl', $gradeLevel))
            ->orderBy('s_lastname')->orderBy('s_firstname')
            ->get();

        // Table rows + General Average
        $rows = collect();
        $generalAverage = null;

        if ($schoolyrId && $studentLrn) {
            $grades = Grade::with('subject')
                ->where('student_id', $studentLrn) // ✅ char FK to students.lrn
                ->where('schoolyr_id', $schoolyrId)
                ->get();

            $rows = $grades->map(function ($g) {
                // Prefer stored final_grade; else compute with new rule
                if ($g->final_grade !== null) {
                    $final = (int) $g->final_grade;
                } else {
                    $vals  = [
                        (int)($g->q1 ?? 0),
                        (int)($g->q2 ?? 0),
                        (int)($g->q3 ?? 0),
                        (int)($g->q4 ?? 0),
                    ];
                    $calc  = array_sum($vals) / 4;
                    $final = ($calc <= 70) ? 0 : (int) round($calc);
                }

                $remark = $final >= 75 ? 'PASSED' : 'FAILED';
                [$desc, $abbr] = $this->depedDescriptor($final);

                return [
                    // ✅ subjects table uses `subject_name`
                    'subject'          => optional($g->subject)->subject_name ?? '—',
                    'q1'               => $g->q1,
                    'q2'               => $g->q2,
                    'q3'               => $g->q3,
                    'q4'               => $g->q4,
                    'final'            => $final,
                    'remark'           => $remark,
                    'descriptor'       => $desc,
                    'descriptor_abbr'  => $abbr,
                ];
            });

            if ($rows->isNotEmpty()) {
                $ga = $rows->pluck('final')->avg();
                $generalAverage = $ga !== null ? (int) round($ga) : null;
            }
        }

        // Quarter flags for the selected School Year + Grade (default: all open)
        $quartersOpen = QuarterLock::flags($schoolyrId, $gradeLevel);

        // NOTE: adjust to your actual view path
        return view('auth.admindashboard.grades', compact(
            'schoolyrs',
            'gradelvls',
            'students',
            'schoolyrId',
            'gradeLevel',
            'studentLrn',
            'rows',
            'generalAverage',
            'quartersOpen'
        ));
    }

    /**
     * Save quarter open/close switches (applies to selected School Year + Grade Level)
     * Route name: admin.grades.quarters.save
     */
    public function saveQuarterAccess(Request $request)
    {
        $data = $request->validate([
            'schoolyr_id' => ['required', 'integer'],
            'grade_level' => ['required', 'string', 'max:32'],
        ]);

        $lock = QuarterLock::firstOrNew([
            'schoolyr_id' => $data['schoolyr_id'],
            'grade_level' => $data['grade_level'],
        ]);

        $lock->q1 = $request->boolean('q1');
        $lock->q2 = $request->boolean('q2');
        $lock->q3 = $request->boolean('q3');
        $lock->q4 = $request->boolean('q4');
        $lock->save();

        return back()->with('success', 'Quarter access settings updated.');
    }

    // DepEd descriptor helper
    private function depedDescriptor(int $final): array
    {
        if ($final >= 90) return ['Outstanding', 'O'];
        if ($final >= 85) return ['Very Satisfactory', 'VS'];
        if ($final >= 80) return ['Satisfactory', 'S'];
        if ($final >= 75) return ['Fairly Satisfactory', 'FS'];
        return ['Did Not Meet Expectations', 'DNME'];
    }
}
