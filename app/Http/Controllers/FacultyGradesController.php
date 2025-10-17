<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use App\Models\Student;
use App\Models\QuarterLock;
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

        /**
         * IMPORTANT: Do NOT filter students by $gradeLevel here.
         * We need all students so the client-side Grade dropdown
         * can re-populate the Student dropdown without a page reload.
         */
        $students = Student::select('id', 's_firstname', 's_middlename', 's_lastname', 's_gradelvl')
            ->orderBy('s_lastname')
            ->orderBy('s_firstname')
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

            // Server-side final (¼ per quarter; missing = 0; ≤70 => 0)
            $q1 = (int)($g?->q1 ?? 0);
            $q2 = (int)($g?->q2 ?? 0);
            $q3 = (int)($g?->q3 ?? 0);
            $q4 = (int)($g?->q4 ?? 0);

            $finalFloat = ($q1 + $q2 + $q3 + $q4) / 4;
            $final = ($finalFloat <= 70) ? 0 : (int) round($finalFloat);

            $remark = $final >= 75 ? 'PASSED' : 'FAILED';
            [$desc, $abbr] = $this->depedDescriptor($final);

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

        // General average (include zero finals; only skip null)
        $generalAverage = null;
        if ($rows->count()) {
            $gaVals = $rows->pluck('final')->filter(fn ($v) => $v !== null);
            $generalAverage = $gaVals->count() ? (int) round($gaVals->avg()) : null;
        }

        // Quarter flags from Admin (GLOBAL for now, or per SY/Grade if you extend)
        $quartersOpen = QuarterLock::flags($schoolyrId, $gradeLevel);

        return view('auth.facultydashboard.grades', compact(
            'schoolyrs',
            'gradelvls',
            'students',
            'schoolyrId',
            'gradeLevel',
            'studentId',
            'rows',
            'generalAverage',
            'quartersOpen'
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

        // Quarter lock enforcement
        $locks = QuarterLock::flags($schoolyrId, $gradeLevel);

        // FK names that actually exist in DB
        $studentKey = Grade::studentKey();
        $subjectKey = Grade::subjectKey();

        foreach ($subjectIds as $idx => $subjectId) {
            $g = Grade::firstOrNew([
                $studentKey   => $studentId,
                $subjectKey   => (int) $subjectId,
                'schoolyr_id' => $schoolyrId,
            ]);

            // Update quarters only if their quarter is OPEN; keep existing otherwise
            if ($locks['q1']) {
                $g->q1 = array_key_exists($idx, $q1) ? ($q1[$idx] !== '' ? (int) $q1[$idx] : null) : $g->q1;
            }
            if ($locks['q2']) {
                $g->q2 = array_key_exists($idx, $q2) ? ($q2[$idx] !== '' ? (int) $q2[$idx] : null) : $g->q2;
            }
            if ($locks['q3']) {
                $g->q3 = array_key_exists($idx, $q3) ? ($q3[$idx] !== '' ? (int) $q3[$idx] : null) : $g->q3;
            }
            if ($locks['q4']) {
                $g->q4 = array_key_exists($idx, $q4) ? ($q4[$idx] !== '' ? (int) $q4[$idx] : null) : $g->q4;
            }

            // Final = ¼ per quarter; missing treated as 0; if <=70 => 0
            $fq1 = (int)($g->q1 ?? 0);
            $fq2 = (int)($g->q2 ?? 0);
            $fq3 = (int)($g->q3 ?? 0);
            $fq4 = (int)($g->q4 ?? 0);

            $finalFloat = ($fq1 + $fq2 + $fq3 + $fq4) / 4;
            $final = ($finalFloat <= 70) ? 0 : (int) round($finalFloat);

            $g->final_grade = $final;
            $g->remark      = $final >= 75 ? 'PASSED' : 'FAILED';

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
