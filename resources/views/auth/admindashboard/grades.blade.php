{{-- resources/views/admin/grades/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Grades')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/app-dashboard.css') }}">

    <style>
        /* Sticky table header while scrolling the report */
        .table-report thead th {
            position: sticky;
            top: 0;
            background: var(--bs-body-bg);
            z-index: 1;
        }

        /* Keep the table compact and predictable so it fits A4 nicely */
        .table-report {
            table-layout: fixed;
            width: 100%;
        }

        .table-report th,
        .table-report td {
            padding: 6px 8px;
        }

        .table-report td {
            word-wrap: break-word;
        }

        /* Student details (KV grid) */
        .report-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap: .35rem 1rem;
            margin-top: .5rem;
        }

        .report-meta .kv {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: .35rem;
        }

        .report-meta .k {
            color: var(--bs-secondary-color, #6c757d);
        }

        .report-meta .v {
            font-weight: 600;
        }

        /* Quarter chips */
        .badge-lock {
            background: #ffe9e9;
            color: #a40000;
            border: 1px solid #ffcccc;
        }

        .badge-open {
            background: #e9fff1;
            color: #0a7e2f;
            border: 1px solid #c9f2d9;
        }

        /* A4-friendly printing */
        @page { size: A4 portrait; margin: 10mm 12mm; }

        @media print {
            html, body { background: #fff !important; }
            body * { visibility: hidden !important; }
            #reportCard, #reportCard * { visibility: visible !important; }
            #reportCard { position: static !important; margin: 0 auto !important; box-shadow: none !important; }
            .table-report { font-size: 11px; }
            .table-report tr, .table-report td, .table-report th { page-break-inside: avoid; }
            .report-meta .kv { grid-template-columns: 120px 1fr; }
        }
    </style>
@endpush

@section('content')
    @php
        // Selected values (server remembers last selections)
        $selectedSchoolYr = $schoolyrId ?? request('schoolyr_id');
        $selectedGrade    = $gradeLevel ?? request('grade_level');
        $selectedStudent  = $studentId ?? request('student_id');

        // Build full-name list (for Student selector)
        $allStudentsList = collect($students)->map(function ($s) {
            $mid  = trim((string) ($s->s_middlename ?? ''));
            $name = trim(implode(' ', array_filter([$s->s_firstname ?? '', $mid, $s->s_lastname ?? ''])));
            return ['id' => $s->id, 'name' => $name !== '' ? $name : ('Student #' . $s->id), 'grade' => $s->s_gradelvl ?? ''];
        })->values();

        $studentsByGrade = $allStudentsList->groupBy('grade')->map->values();
        $gradeLevels     = collect($gradelvls ?? [])->pluck('grade_level')->values();

        // Global quarter flags (true=open, false=closed)
        $quartersOpen = \App\Models\QuarterLock::flags();

        // KPI numbers for the header strip
        $kpiStudents     = $allStudentsList->count();
        $kpiGrades       = $gradeLevels->count();
        $kpiSchoolYears  = collect($schoolyrs ?? [])->count();
        $kpiOpenQuarters = collect($quartersOpen)->filter(fn($v) => $v === true)->count();

        // ----- Student details for the report header -----
        $currentStudent = collect($students)->firstWhere('id', (int) $selectedStudent);
        $studentName    = $currentStudent
            ? trim(implode(' ', array_filter([$currentStudent->s_firstname ?? '', $currentStudent->s_middlename ?? '', $currentStudent->s_lastname ?? ''])))
            : null;

        $gradeForReport = ($selectedGrade ?: null) ?? ($currentStudent->s_gradelv) ?? '—';

        $schoolYrModel  = collect($schoolyrs)->firstWhere('id', (int) $selectedSchoolYr);
        $schoolYrText   = $schoolYrModel->display_year
            ?? $schoolYrModel->school_year
            ?? ($selectedSchoolYr ? ('SY #' . $selectedSchoolYr) : '—');

        $printedOn = now()->format('Y-m-d');
    @endphp

    <div class="card section p-4">
        {{-- =========================
        Header: Intro | KPIs | Quick Actions
        ========================== --}}
        <div id="dashboard-header" class="mb-3">
            {{-- Intro --}}
            <div class="intro rounded">
                <div>
                    <h5 class="mb-1">Grades</h5>
                    <div class="text-muted small">Quarterly grades and final rating.</div>
                </div>
            </div>

            {{-- KPI strip --}}
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $kpiStudents }}</div>
                    <div class="kpi-label">Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $kpiGrades }}</div>
                    <div class="kpi-label">Grade Levels</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $kpiSchoolYears }}</div>
                    <div class="kpi-label">School Years</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $kpiOpenQuarters }}/4</div>
                    <div class="kpi-label">Open Quarters</div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="pay-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 qa-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="d-grid gap-2 qa-actions">
                    <button class="btn btn-outline-dark btn-sm no-print" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                    {{-- Quarter Access Control --}}
                    <button class="btn btn-outline-primary btn-sm no-print" data-bs-toggle="modal"
                        data-bs-target="#quarterAccessModal">
                        <i class="bi bi-unlock me-1"></i> Quarter Access Control
                    </button>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-3">
            <form id="filtersForm" class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label">School Year</label>
                    <select name="schoolyr_id" id="schoolyrSelect" class="form-select">
                        <option value="">Select school year</option>
                        @foreach($schoolyrs as $sy)
                            <option value="{{ $sy->id }}" {{ (int) $selectedSchoolYr === (int) $sy->id ? 'selected' : '' }}>
                                {{ $sy->display_year ?? $sy->school_year ?? ('SY #' . $sy->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
                        {{-- Populated by JS --}}
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100 no-print">
                        <i class="bi bi-search"></i> View
                    </button>
                </div>
            </form>
        </div>

        {{-- Quarter status chips (read-only banner; editing is in modal) --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-2">
                <div class="fw-semibold me-2">Quarter Access (GLOBAL):</div>
                <span class="badge rounded-pill {{ $quartersOpen['q1'] ? 'badge-open' : 'badge-lock' }}">Q1 {{ $quartersOpen['q1'] ? 'Open' : 'Closed' }}</span>
                <span class="badge rounded-pill {{ $quartersOpen['q2'] ? 'badge-open' : 'badge-lock' }}">Q2 {{ $quartersOpen['q2'] ? 'Open' : 'Closed' }}</span>
                <span class="badge rounded-pill {{ $quartersOpen['q3'] ? 'badge-open' : 'badge-lock' }}">Q3 {{ $quartersOpen['q3'] ? 'Open' : 'Closed' }}</span>
                <span class="badge rounded-pill {{ $quartersOpen['q4'] ? 'badge-open' : 'badge-lock' }}">Q4 {{ $quartersOpen['q4'] ? 'Open' : 'Closed' }}</span>

                <button class="btn btn-sm btn-outline-primary ms-auto no-print" data-bs-toggle="modal"
                    data-bs-target="#quarterAccessModal">
                    <i class="bi bi-pen me-1"></i> Edit
                </button>
            </div>
        </div>

        {{-- =========================
        Report (Print Target)
        ========================== --}}
        <div class="report-card" id="reportCard">
            <div class="report-header text-center mb-2">
                <h5 class="mb-0">Report on Learner’s Progress</h5>
                <div class="text-muted">Quarterly Grades and Final Rating (DepEd Format)</div>
            </div>

            {{-- Student Details block (prints with the report) --}}
            <div class="report-meta">
                <div class="kv"><div class="k">Student</div><div class="v">{{ $studentName ?: '—' }}</div></div>
                <div class="kv"><div class="k">Grade Level</div><div class="v">{{ $gradeForReport ?: '—' }}</div></div>
                <div class="kv"><div class="k">School Year</div><div class="v">{{ $schoolYrText }}</div></div>
                <div class="kv"><div class="k">Printed On</div><div class="v">{{ $printedOn }}</div></div>
            </div>

            <div class="table-responsive mt-2">
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
                                <td class="text-center {{ ($r['remark'] ?? '') === 'FAILED' ? 'text-danger fw-semibold' : '' }}">
                                    {{ $r['remark'] ?? '—' }}
                                </td>
                                <td class="text-center">
                                    {{ $r['descriptor'] ? $r['descriptor'] . ' (' . $r['descriptor_abbr'] . ')' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Select a school year and student to view grades.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if(!empty($rows) && (method_exists($rows, 'isNotEmpty') ? $rows->isNotEmpty() : count($rows)))
                        <tfoot>
                            <tr class="table-light">
                                <th>General Average</th>
                                <th colspan="3"></th>
                                <th></th>
                                <th class="text-center">{{ $generalAverage ?? '—' }}</th>
                                <th class="text-center">{{ ($generalAverage !== null ? ($generalAverage >= 75 ? 'PASSED' : 'FAILED') : '—') }}</th>
                                <th class="text-center">
                                    @php
                                        $desc = null; $abbr = null;
                                        if ($generalAverage !== null) {
                                            if ($generalAverage >= 90)      { $desc = 'Outstanding';            $abbr = 'O'; }
                                            elseif ($generalAverage >= 85) { $desc = 'Very Satisfactory';      $abbr = 'VS'; }
                                            elseif ($generalAverage >= 80) { $desc = 'Satisfactory';           $abbr = 'S'; }
                                            elseif ($generalAverage >= 75) { $desc = 'Fairly Satisfactory';    $abbr = 'FS'; }
                                            else                           { $desc = 'Did Not Meet Expectations'; $abbr = 'DNME'; }
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
                Remarks follow DepEd thresholds (≥75 PASSED; &lt;75 FAILED).
                Descriptors: O (90–100), VS (85–89), S (80–84), FS (75–79), DNME (&lt;75).
            </div>
        </div>
    </div>

    {{-- =========================
    MODAL: Quarter Access Control (GLOBAL)
    ========================== --}}
    <div class="modal fade" id="quarterAccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.grades.quarters.save') }}">
                @csrf
                <div class="modal-header" style="background: linear-gradient(90deg, #476DA3, #3C5F8E); color:#fff;">
                    <h5 class="modal-title">Quarter Access Control (GLOBAL)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Toggle which quarters are <strong>open for Faculty editing</strong>. This applies to
                        <strong>ALL grade levels and ALL students</strong> across the system.
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mq1" name="q1" {{ $quartersOpen['q1'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="mq1">Q1 Open</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mq2" name="q2" {{ $quartersOpen['q2'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="mq2">Q2 Open</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mq3" name="q3" {{ $quartersOpen['q3'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="mq3">Q3 Open</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mq4" name="q4" {{ $quartersOpen['q4'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="mq4">Q4 Open</label>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Faculty can enter grades only for the quarters marked <strong>Open</strong>. Closed quarters are
                        disabled.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const STUDENTS_BY_GRADE   = @json($studentsByGrade);
        const ALL_STUDENTS        = @json($allStudentsList);
        const PRESELECT_GRADE     = @json((string) ($selectedGrade ?? ''));
        const PRESELECT_STUDENT   = @json((string) ($selectedStudent ?? ''));

        function opt(v, t, sel = false, disabled = false) {
            const o = document.createElement('option');
            o.value = v; o.textContent = t;
            if (sel) o.selected = true;
            if (disabled) o.disabled = true;
            return o;
        }

        function populateStudents(grade, selectedId) {
            const sel = document.getElementById('studentSelect');
            if (!sel) return;
            sel.innerHTML = '';

            const hasGrade = !!(grade && String(grade).trim() !== '');
            if (hasGrade) {
                const list = (STUDENTS_BY_GRADE && STUDENTS_BY_GRADE[grade]) ? STUDENTS_BY_GRADE[grade] : [];
                if (!list.length) {
                    sel.appendChild(opt('', '— No students in this grade —', true, true));
                    return;
                }
                sel.appendChild(opt('', 'Select student in this grade'));
                list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
                return;
            }

            const list = ALL_STUDENTS || [];
            sel.appendChild(opt('', 'Select student', !selectedId));
            list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form     = document.getElementById('filtersForm');
            const sySel    = document.getElementById('schoolyrSelect');
            const gradeSel = document.getElementById('gradeLevelFilter');
            const stuSel   = document.getElementById('studentSelect');

            // Initial student list (respect preselects)
            populateStudents(gradeSel?.value || PRESELECT_GRADE, PRESELECT_STUDENT);

            // Auto-submit helper
            function submitIfReady() {
                if (!form) return;
                if (stuSel && stuSel.value) {
                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                }
            }

            // When School Year changes: submit if a student is already chosen
            sySel?.addEventListener('change', submitIfReady);

            // When Grade changes: rebuild students and wait for a student pick
            gradeSel?.addEventListener('change', function () {
                populateStudents(this.value, null);
            });

            // When Student changes: submit immediately
            stuSel?.addEventListener('change', submitIfReady);
        });
    </script>
@endpush
