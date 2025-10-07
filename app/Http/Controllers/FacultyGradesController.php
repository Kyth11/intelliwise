<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FacultyGradesController extends Controller
{
    public function index(Request $request)
    {
        $schoolyrs  = Schoolyr::orderByDesc('id')->get();
        $gradelvls  = Gradelvl::orderBy('grade_level')->get();

        $schoolyrId = $request->input('schoolyr_id');
        $gradeLevel = $request->input('grade_level');   // e.g. "Grade 1"
        $studentId  = $request->input('student_id');

        // Students (filtered by grade if provided)
        $students = Student::select('id', 's_firstname', 's_middlename', 's_lastname', 's_gradelvl')
            ->when($gradeLevel, fn ($q) => $q->where('s_gradelvl', $gradeLevel))
            ->orderBy('s_lastname')->orderBy('s_firstname')
            ->get();

        // Resolve Subject model and subject label
        $subjectModel = class_exists(\App\Models\Subjects::class)
            ? \App\Models\Subjects::class
            : (class_exists(\App\Models\Subjects::class) ? \App\Models\Subjects::class : null);

        $subjects = collect();
        if ($subjectModel) {
            $subjects = $subjectModel::query()
                ->when($gradeLevel, function ($q) use ($subjectModel, $gradeLevel) {
                    try {
                        $tmp = new $subjectModel;
                        $table = $tmp->getTable();
                        if (Schema::hasColumn($table, 'grade_level')) {
                            $q->where('grade_level', $gradeLevel);
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                })
                ->get()
                ->map(function ($s) {
                    $label = $s->name
                        ?? $s->title
                        ?? $s->subject_name
                        ?? ('Subject #'.$s->id);
                    $s->_label = $label;
                    return $s;
                });
        }

        // Use correct FK names that exist in DB
        $studentKey = Grade::studentKey();
        $subjectKey = Grade::subjectKey();

        // Existing grades for (student + school year)
        $existing = collect();
        if ($schoolyrId && $studentId) {
            $existing = Grade::where($studentKey, $studentId)
                ->where('schoolyr_id', $schoolyrId)
                ->get()
                ->keyBy(function ($g) use ($subjectKey) {
                    return $g->{$subjectKey};
                });
        }

        // Build rows for blade
        $rows = $subjects->map(function ($sub) use ($existing) {
            $g = $existing->get($sub->id);

            $final = $g?->final_grade;
            if ($final === null) {
                $qs = collect([$g?->q1, $g?->q2, $g?->q3, $g?->q4])->filter(fn ($v) => $v !== null);
                $final = $qs->count() ? (int) round($qs->avg()) : null;
            }
            $remark = $final === null ? null : ($final >= 75 ? 'PASSED' : 'FAILED');
            [$desc, $abbr] = $final === null ? [null, null] : $this->depedDescriptor($final);

            return [
                'subject_id'       => $sub->id,
                'subject_label'    => $sub->_label,
                'q1'               => $g?->q1,
                'q2'               => $g?->q2,
                'q3'               => $g?->q3,
                'q4'               => $g?->q4,
                'final'            => $final,
                'remark'           => $remark,
                'descriptor'       => $desc,
                'descriptor_abbr'  => $abbr,
            ];
        });

        // General average
        $generalAverage = null;
        if ($rows->count()) {
            $ga = $rows->pluck('final')->filter()->avg();
            $generalAverage = $ga ? (int) round($ga) : null;
        }

        return view('auth.facultydashboard.grades', compact(
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

    public function store(Request $request)
    {
        $request->validate([
            'schoolyr_id'  => ['required','integer','exists:schoolyrs,id'],
            'grade_level'  => ['required','string'],
            'student_id'   => ['required','integer','exists:students,id'],

            'subject_id'   => ['required','array','min:1'],
            'subject_id.*' => ['integer'],

            'q1'           => ['array'],
            'q1.*'         => ['nullable','integer','between:0,100'],
            'q2'           => ['array'],
            'q2.*'         => ['nullable','integer','between:0,100'],
            'q3'           => ['array'],
            'q3.*'         => ['nullable','integer','between:0,100'],
            'q4'           => ['array'],
            'q4.*'         => ['nullable','integer','between:0,100'],
        ]);

        $schoolyrId = (int) $request->schoolyr_id;
        $studentId  = (int) $request->student_id;
        $gradeLevel = (string) $request->grade_level;

        $gradelvlId = optional(Gradelvl::where('grade_level', $gradeLevel)->first())->id;
        $facultyId  = Auth::user()->faculty_id ?? null;

        $subjectIds = $request->input('subject_id', []);
        $q1 = $request->input('q1', []);
        $q2 = $request->input('q2', []);
        $q3 = $request->input('q3', []);
        $q4 = $request->input('q4', []);

        // FK names that actually exist in DB
        $studentKey = Grade::studentKey();
        $subjectKey = Grade::subjectKey();

        foreach ($subjectIds as $idx => $subjectId) {
            $g = Grade::firstOrNew([
                $studentKey  => $studentId,
                $subjectKey  => (int) $subjectId,
                'schoolyr_id'=> $schoolyrId,
            ]);

            // Update quarters (allow blank -> null)
            $g->q1 = array_key_exists($idx, $q1) ? ($q1[$idx] !== '' ? (int) $q1[$idx] : null) : $g->q1;
            $g->q2 = array_key_exists($idx, $q2) ? ($q2[$idx] !== '' ? (int) $q2[$idx] : null) : $g->q2;
            $g->q3 = array_key_exists($idx, $q3) ? ($q3[$idx] !== '' ? (int) $q3[$idx] : null) : $g->q3;
            $g->q4 = array_key_exists($idx, $q4) ? ($q4[$idx] !== '' ? (int) $q4[$idx] : null) : $g->q4;

            // Final = average of available quarters
            $qs = collect([$g->q1, $g->q2, $g->q3, $g->q4])->filter(fn ($v) => $v !== null);
            $final = $qs->count() ? (int) round($qs->avg()) : null;

            $g->final_grade = $final;
            $g->remark      = $final === null ? null : ($final >= 75 ? 'PASSED' : 'FAILED');

            $g->gradelvl_id = $gradelvlId;
            $g->faculty_id  = $facultyId;

            $g->save();
        }

        return back()->with('success', 'Grades saved.');
    }

    private function depedDescriptor(int $final): array
    {
        if ($final >= 90) return ['Outstanding', 'O'];
        if ($final >= 85) return ['Very Satisfactory', 'VS'];
        if ($final >= 80) return ['Satisfactory', 'S'];
        if ($final >= 75) return ['Fairly Satisfactory', 'FS'];
        return ['Did Not Meet Expectations', 'DNME'];
    }
}
