@extends('layouts.admin')

@section('title', 'Grades')

@push('styles')
    <style>
        .report-card {
            background: var(--bs-body-bg);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .report-header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .report-header h5 {
            margin: 0;
        }

        .table-report thead th {
            position: sticky;
            top: 0;
            background: var(--bs-body-bg);
            z-index: 1;
        }

        @page {
            size: A4;
            margin: 14mm;
        }

        @media print {

            .sidebar,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .report-card {
                border: none;
            }

            .table-report {
                font-size: 12px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // For the filter selections
    // Selected values (server remembers the last selections)
    $selectedSchoolYr = $schoolyrId ?? request('schoolyr_id');
    $selectedGrade    = $gradeLevel ?? request('grade_level'); // <- new param
    $selectedStudent  = $studentId ?? request('student_id');

        // Build full-name list from real DB fields the controller provided
        $allStudentsList = collect($students)->map(function ($s) {
            $mid = trim((string) ($s->s_middlename ?? ''));
            $name = trim(implode(' ', array_filter([$s->s_firstname ?? '', $mid, $s->s_lastname ?? ''])));
            return [
                'id' => $s->id,
                'name' => $name !== '' ? $name : ('Student #' . $s->id),
                'grade' => $s->s_gradelvl ?? '',
            ];
        })->values();

        // If controller filtered by grade already, you still get proper grouping here
        $studentsByGrade = $allStudentsList->groupBy('grade')->map->values();

        // Grade levels for the dropdown
        $gradeLevels = collect($gradelvls ?? [])->pluck('grade_level')->values();
    @endphp

    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Grades</h3>
            <button class="btn btn-outline-secondary no-print" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>

        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-3"> <label class="form-label">School Year</label> <select name="schoolyr_id"
                    class="form-select">
                    <option value="">Select school year</option> @foreach($schoolyrs as $sy) <option value="{{ $sy->id }}"
                        {{ (int) $selectedSchoolYr === (int) $sy->id ? 'selected' : '' }}> {{ $sy->display_year }} </option>
                    @endforeach
                </select> </div>

            <div class="col-md-3">
                <label class="form-label">Grade Level</label>
                <select name="grade_level" id="gradeLevelFilter" class="form-select">
                    <option value="">All grade levels</option>
                    @foreach($gradeLevels as $gl)
                        <option value="{{ $gl }}" {{ (string) $selectedGrade === (string) $gl ? 'selected' : '' }}>
                            {{ $gl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Student</label>
                <select name="student_id" id="studentSelect" class="form-select">
                    <option value="">Select student</option>
                    {{-- options populated by JS so the list reflects the selected grade --}}
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100 no-print"><i class="bi bi-search"></i> View</button>
            </div>
        </form>

        <div class="report-card">
            <div class="report-header">
                <h5>Report on Learner’s Progress</h5>
                <div class="text-muted">Quarterly Grades and Final Rating (DepEd Format)</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle table-report">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="min-width: 220px;">Learning Areas</th>
                            <th>Q1</th>
                            <th>Q2</th>
                            <th>Q3</th>
                            <th>Q4</th>
                            <th>Final Grade</th>
                            <th>Remarks</th>
                            <th>Descriptor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $r['subject'] }}</td>
                                <td class="text-center">{{ $r['q1'] ?? '—' }}</td>
                                <td class="text-center">{{ $r['q2'] ?? '—' }}</td>
                                <td class="text-center">{{ $r['q3'] ?? '—' }}</td>
                                <td class="text-center">{{ $r['q4'] ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $r['final'] ?? '—' }}</td>
                                <td
                                    class="text-center {{ ($r['remark'] ?? '') === 'FAILED' ? 'text-danger fw-semibold' : '' }}">
                                    {{ $r['remark'] ?? '—' }}
                                </td>
                                <td class="text-center">
                                    {{ $r['descriptor'] ? $r['descriptor'] . ' (' . $r['descriptor_abbr'] . ')' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Select a school year and student to view grades.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="table-light">
                                <th>General Average</th>
                                <th colspan="3"></th>
                                <th></th>
                                <th class="text-center">{{ $generalAverage ?? '—' }}</th>
                                <th class="text-center">
                                    {{ ($generalAverage !== null ? ($generalAverage >= 75 ? 'PASSED' : 'FAILED') : '—') }}
                                </th>
                                <th class="text-center">
                                    @php
                                        $desc = null;
                                        $abbr = null;
                                        if ($generalAverage !== null) {
                                            if ($generalAverage >= 90) {
                                                $desc = 'Outstanding';
                                                $abbr = 'O';
                                            } elseif ($generalAverage >= 85) {
                                                $desc = 'Very Satisfactory';
                                                $abbr = 'VS';
                                            } elseif ($generalAverage >= 80) {
                                                $desc = 'Satisfactory';
                                                $abbr = 'S';
                                            } elseif ($generalAverage >= 75) {
                                                $desc = 'Fairly Satisfactory';
                                                $abbr = 'FS';
                                            } else {
                                                $desc = 'Did Not Meet Expectations';
                                                $abbr = 'DNME';
                                            }
                                        }
                                    @endphp
                                    {{ $desc ? ($desc . ' (' . $abbr . ')') : '—' }}
                                </th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="small text-muted mt-2">
                Notes: Final Grade is the rounded average of available quarters (Q1–Q4) if not explicitly stored.
                Remarks follow DepEd thresholds (≥75 PASSED; &lt;75 FAILED). Descriptors: O (90–100), VS (85–89),
                S (80–84), FS (75–79), DNME (&lt;75).
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const STUDENTS_BY_GRADE = @json($studentsByGrade);
        const ALL_STUDENTS = @json($allStudentsList);
        const PRESELECT_GRADE = @json((string) ($selectedGrade ?? ''));
        const PRESELECT_STUDENT = @json((string) ($selectedStudent ?? ''));

        function opt(v, t, sel = false, disabled = false) {
            const o = document.createElement('option');
            o.value = v; o.textContent = t;
            if (sel) o.selected = true;
            if (disabled) o.disabled = true;
            return o;
        }

        function populateStudents(grade, selectedId) {
            const sel = document.getElementById('studentSelect');
            sel.innerHTML = '';

            const hasGrade = !!(grade && String(grade).trim() !== '');

            // If a grade is selected, ONLY show students in that grade.
            if (hasGrade) {
                const list = (STUDENTS_BY_GRADE && STUDENTS_BY_GRADE[grade]) ? STUDENTS_BY_GRADE[grade] : [];

                if (!list.length) {
                    // No students in this grade → show a single disabled option
                    sel.appendChild(opt('', '— No students in this grade —', true, true));
                    return;
                }

                // There are students in this grade → show a prompt then the students
                sel.appendChild(opt('', 'Select student in this grade'));
                list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
                return;
            }

            // If NO grade is selected, show ALL students (or just the placeholder if none exist)
            const list = ALL_STUDENTS || [];
            sel.appendChild(opt('', 'Select student', !selectedId));
            list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const gradeSel = document.getElementById('gradeLevelFilter');

            // Initial paint using current selections from server
            populateStudents(gradeSel?.value || PRESELECT_GRADE, PRESELECT_STUDENT);

            // Rebuild when grade changes (clear selected student)
            gradeSel?.addEventListener('change', function () {
                populateStudents(this.value, null);
            });
        });
    </script>
@endpush
