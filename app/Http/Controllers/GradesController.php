<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Schoolyr;
use App\Models\Gradelvl;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    public function index(Request $request)
    {
$schoolyrs = \App\Models\Schoolyr::orderByDesc('id')->get();

        // Optional filters coming from the form
        $schoolyrId = $request->input('schoolyr_id');
        $gradeLevel = $request->input('grade_level'); // string like "Grade 1"
        $studentId = $request->input('student_id');

        // Grade levels for the filter
        $gradelvls = Gradelvl::orderBy('grade_level')->get();

        // Load students from DB with their real names + grade level (used by the view)
        $students = Student::select('id', 's_firstname', 's_middlename', 's_lastname', 's_gradelvl')
            ->when($gradeLevel, fn($q) => $q->where('s_gradelvl', $gradeLevel))
            ->orderBy('s_lastname')->orderBy('s_firstname')
            ->get();

        // Build rows (subjects/grades) if a student+school year is selected
        $rows = collect();
        $generalAverage = null;

        if ($schoolyrId && $studentId) {
            // Example: join grades + subjects; adapt column names if your schema differs
            $grades = Grade::with('subject')
                ->where('student_id', $studentId)
                ->where('schoolyr_id', $schoolyrId)
                ->get();

            $rows = $grades->map(function ($g) {
                $q = collect([$g->q1, $g->q2, $g->q3, $g->q4])->filter(fn($v) => $v !== null);
                $final = $g->final_grade ?? ($q->isNotEmpty() ? round($q->avg()) : null);

                // Derive DepEd-style outputs (adjust to your helpers if any)
                $remark = $final === null ? null : ($final >= 75 ? 'PASSED' : 'FAILED');
                [$desc, $abbr] = $final === null ? [null, null] : $this->depedDescriptor($final);

                return [
                    'subject' => optional($g->subject)->name ?? '—',
                    'q1' => $g->q1,
                    'q2' => $g->q2,
                    'q3' => $g->q3,
                    'q4' => $g->q4,
                    'final' => $final,
                    'remark' => $remark,
                    'descriptor' => $desc,
                    'descriptor_abbr' => $abbr,
                ];
            });

            if ($rows->isNotEmpty()) {
                $ga = $rows->pluck('final')->filter()->avg();
                $generalAverage = $ga ? round($ga) : null;
            }
        }

        return view('auth.admindashboard.grades', compact(
            'schoolyrs',
            'gradelvls',
            'students',
            'schoolyrId',
            'gradeLevel',
            'studentId',
            'rows',
            'generalAverage'
        ));
    }

    // Minimal descriptor helper (replace with your model helpers if you already have them)
    private function depedDescriptor(int $final): array
    {
        if ($final >= 90)
            return ['Outstanding', 'O'];
        if ($final >= 85)
            return ['Very Satisfactory', 'VS'];
        if ($final >= 80)
            return ['Satisfactory', 'S'];
        if ($final >= 75)
            return ['Fairly Satisfactory', 'FS'];
        return ['Did Not Meet Expectations', 'DNME'];
    }
}
