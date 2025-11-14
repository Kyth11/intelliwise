<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * Reports page (guardian-view).
     */
    public function reports(Request $request)
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
                $label = null;
                if (is_object($g) && isset($g->subject) && is_object($g->subject)) {
                    $label = $g->subject->subject_name
                        ?? $g->subject->name
                        ?? $g->subject->title
                        ?? $g->subject->subject_code
                        ?? null;
                }
                if (!$label && isset($g->subject_name) && $g->subject_name !== '') {
                    $label = $g->subject_name;
                }
                if (!$label && isset($g->subject_code) && $g->subject_code !== '') {
                    $label = $g->subject_code;
                }
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
                                        ?? $try;
                                } else {
                                    $label = $try;
                                }
                            } else {
                                $label = $try;
                            }
                        }
                    } elseif (is_object($g->subject)) {
                        $label = $g->subject->subject_name
                            ?? $g->subject->name
                            ?? $g->subject->title
                            ?? $g->subject->subject_code
                            ?? null;
                    }
                }

                $subject = $label ?: 'Subject';

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
}
