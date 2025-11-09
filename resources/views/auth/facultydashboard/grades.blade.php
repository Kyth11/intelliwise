{{-- resources/views/faculty/grades/index.blade.php --}}
@extends('layouts.faculty')
@section('title','Faculty · Grades')

@push('styles')
<style>
    /* ===== Table/editor tweaks (screen) ===== */
    .table-report thead th { position: sticky; top: 0; background: var(--bs-body-bg); z-index: 1; }
    .qcell { width: 88px; }
    .finalcell { width: 110px; }
    .readonly { background: #f8f9fa; }
    .badge-lock { background: #ffe9e9; color:#a40000; border:1px solid #ffcccc; }
    .badge-open { background: #e9fff1; color:#0a7e2f; border:1px solid #c9f2d9; }

    /* ===== Report (screen) ===== */
    .report-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; background: #fff; }
    .report-card .report-header h5 { margin: 0; }
    .report-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: .35rem 1rem;
        margin-top: .5rem;
    }
    .report-meta .kv { display: grid; grid-template-columns: 140px 1fr; gap: .35rem; }
    .report-meta .k { color: var(--bs-secondary-color, #6c757d); }
    .report-meta .v { font-weight: 600; }

    .report-table { table-layout: fixed; width: 100%; }
    .report-table th, .report-table td { padding: 6px 8px; }
    .report-table td { word-wrap: break-word; }

    /* ===== Print: fit A4, larger text ===== */
    @page {
        size: A4 portrait;
        margin: 10mm 15mm 12mm 12mm; /* top | right | bottom | left */
    }
    @media print {
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { background: #fff !important; }

        /* Hide everything by default */
        body * { visibility: hidden !important; }

        /* Show only the report */
        #reportCard, #reportCard * { visibility: visible !important; }

        /* Layout/fitting */
        #reportCard {
            position: static !important;
            margin: 0 auto !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box !important;
            overflow: visible !important;
            page-break-inside: avoid;
            font-size: 14px; /* base size up for print */
            line-height: 1.35;
        }

        /* Bigger header & meta */
        #reportCard .report-header h5 { font-size: 1.35rem; }
        #reportCard .report-header .text-muted { font-size: 0.95rem; }
        #reportCard .report-meta { gap: .4rem 1.2rem; }
        #reportCard .report-meta .kv { grid-template-columns: 130px 1fr; }
        #reportCard .report-meta .k { font-size: 0.95rem; }
        #reportCard .report-meta .v { font-size: 1rem; }

        /* Larger table text & touch more padding */
        .report-table { font-size: 13.5px; table-layout: fixed; width: 100%; }
        .report-table th, .report-table td { padding: 7px 10px; }
        .report-table th:first-child, .report-table td:first-child { min-width: 200px; }

        /* Keep table rows/headers together */
        .report-table tr, .report-table td, .report-table th { page-break-inside: avoid; }

        /* No UI chrome */
        .no-print { display: none !important; }

        /* Top-half layout when single report */
        #reportCard.print-halfpage { margin-bottom: 50vh !important; }
    }
</style>
@endpush

@section('content')
@php
    // ---------- Build student lists for the dynamic dropdown ----------
    $allStudentsList = collect($students)->map(function ($s) {
        $mid = trim((string)($s->s_middlename ?? ''));
        $name = trim(implode(' ', array_filter([$s->s_firstname ?? '', $mid, $s->s_lastname ?? ''])));
        return ['id'=>$s->id, 'name'=>($name ?: ('Student #'.$s->id)), 'grade'=>$s->s_gradelvl ?? ''];
    })->values();
    $studentsByGrade = $allStudentsList->groupBy('grade')->map->values();

    $selectedSchoolYr = $schoolyrId ?? request('schoolyr_id');
    $selectedGrade    = $gradeLevel ?? request('grade_level');
    $selectedStudent  = $studentId ?? request('student_id');

    $showEditor = filled($selectedSchoolYr) && filled($selectedGrade) && filled($selectedStudent);

    // Quarter flags (default open)
    $quartersOpen = array_merge(['q1'=>true,'q2'=>true,'q3'=>true,'q4'=>true], $quartersOpen ?? []);
    $hasAnyOpen = in_array(true, $quartersOpen, true);

    // ---------- Report (print) header details ----------
    $currentStudent = collect($students)->firstWhere('id', (int) $selectedStudent);
    $studentName    = $currentStudent
        ? trim(implode(' ', array_filter([$currentStudent->s_firstname ?? '', $currentStudent->s_middlename ?? '', $currentStudent->s_lastname ?? ''])))
        : null;

    $gradeForReport = ($selectedGrade ?: null) ?? ($currentStudent->s_gradelvl ?? null) ?? '—';

    $schoolYrModel  = collect($schoolyrs)->firstWhere('id', (int) $selectedSchoolYr);
    $schoolYrText   = $schoolYrModel->display_year
        ?? $schoolYrModel->school_year
        ?? ($selectedSchoolYr ? ('SY #' . $selectedSchoolYr) : '—');

    $printedOn = now()->format('Y-m-d');
@endphp

<div class="card section p-4">
    <!-- =========================
         Header: Intro | KPIs | Quick Action (Print)
    ========================== -->
    <div id="dashboard-header" class="mb-3">
        <div class="intro">
            <div>
                <h5 class="mb-1">Grades</h5>
                <div class="text-muted small">Enter quarterly grades and auto-compute final & GA.</div>
            </div>
        </div>

        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($quartersOpen)->filter()->count() }}/4</div>
                <div class="kpi-label">Open Quarters</div>
            </div>
        </div>

        <div class="pay-card p-3 text-center">
            <h6 class="mb-1">Quick Action</h6>
            <p class="text-muted mb-3 small">Print the grading sheet for your current view.</p>
            <button class="btn btn-outline-dark btn-sm no-print" id="printBtn">
                <i class="bi bi-printer me-1"></i> Print Grading Sheet
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Fix the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- =========================
         Filters (GET)
    ========================== --}}
    <div class="card p-3 mb-3 no-print">
        <form id="filterForm" class="row g-2 align-items-end" method="GET" action="{{ route('faculty.grades.index') }}">
            <div class="col-md-3">
                <label class="form-label">School Year</label>
                <select name="schoolyr_id" id="schoolyrSelect" class="form-select" required>
                    <option value="">Select school year</option>
                    @foreach($schoolyrs as $sy)
                        <option value="{{ $sy->id }}" {{ (int)$selectedSchoolYr === (int)$sy->id ? 'selected' : '' }}>
                            {{ $sy->display_year ?? ($sy->school_year ?? ('SY #'.$sy->id)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Grade Level</label>
                <select name="grade_level" id="gradeLevelFilter" class="form-select" required>
                    <option value="">Select grade level</option>
                    @foreach($gradelvls as $gl)
                        <option value="{{ $gl->grade_level }}" {{ (string)$selectedGrade === (string)$gl->grade_level ? 'selected' : '' }}>
                            {{ $gl->grade_level }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Student</label>
                <select name="student_lrn" id="studentSelect" class="form-select" required>
                    <option value="">Select student</option>
                    {{-- populated by JS --}}
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i> View</button>
            </div>
        </form>
    </div>

    {{-- =========================
         Quarter status banner
    ========================== --}}
    @if($showEditor)
        <div class="d-flex flex-wrap gap-2 mb-3 no-print">
            <span class="small">Quarter status:</span>
            <span class="badge rounded-pill {{ $quartersOpen['q1'] ? 'badge-open' : 'badge-lock' }}">Q1 {{ $quartersOpen['q1'] ? 'Open' : 'Closed' }}</span>
            <span class="badge rounded-pill {{ $quartersOpen['q2'] ? 'badge-open' : 'badge-lock' }}">Q2 {{ $quartersOpen['q2'] ? 'Open' : 'Closed' }}</span>
            <span class="badge rounded-pill {{ $quartersOpen['q3'] ? 'badge-open' : 'badge-lock' }}">Q3 {{ $quartersOpen['q3'] ? 'Open' : 'Closed' }}</span>
            <span class="badge rounded-pill {{ $quartersOpen['q4'] ? 'badge-open' : 'badge-lock' }}">Q4 {{ $quartersOpen['q4'] ? 'Open' : 'Closed' }}</span>
            @unless($hasAnyOpen)
                <span class="ms-2 text-danger small">All quarters are closed by Admin — editing is disabled.</span>
            @endunless
        </div>
    @endif

    {{-- =========================
         Editor (POST)
    ========================== --}}
    @if($showEditor)
    <form id="gradesForm" action="{{ route('faculty.grades.store') }}" method="POST" autocomplete="off" class="no-print">
        @csrf
        {{-- keep filters with the post --}}
        <input type="hidden" name="schoolyr_id"  id="hf_sy"    value="{{ $selectedSchoolYr }}">
        <input type="hidden" name="grade_level"  id="hf_grade" value="{{ $selectedGrade }}">
        <input type="hidden" name="student_lrn"   id="hf_stu"   value="{{ $selectedStudent }}">

        <div class="table-responsive">
            <table class="table table-bordered align-middle table-report">
                <thead class="table-light text-center">
                    <tr>
                        <th style="min-width:240px;">Learning Area</th>
                        <th class="qcell">Q1</th>
                        <th class="qcell">Q2</th>
                        <th class="qcell">Q3</th>
                        <th class="qcell">Q4</th>
                        <th class="finalcell">Final</th>
                        <th>Remarks</th>
                        <th>Descriptor</th>
                    </tr>
                </thead>
                <tbody id="gradesBody">
                @forelse($rows as $r)
                    <tr data-row>
                        <td>
                            {{ $r['subject_label'] }}
                            <input type="hidden" name="subject_id[]" value="{{ $r['subject_id'] }}" data-subject-id="{{ $r['subject_id'] }}">
                        </td>

                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q1[]" min="0" max="100"
                                   value="{{ $r['q1'] }}" placeholder="—" {{ $quartersOpen['q1'] ? '' : 'disabled' }}>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q2[]" min="0" max="100"
                                   value="{{ $r['q2'] }}" placeholder="—" {{ $quartersOpen['q2'] ? '' : 'disabled' }}>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q3[]" min="0" max="100"
                                   value="{{ $r['q3'] }}" placeholder="—" {{ $quartersOpen['q3'] ? '' : 'disabled' }}>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q4[]" min="0" max="100"
                                   value="{{ $r['q4'] }}" placeholder="—" {{ $quartersOpen['q4'] ? '' : 'disabled' }}>
                        </td>

                        {{-- Final (readonly UI, computed live); server recomputes anyway --}}
                        <td>
                            <input type="text" class="form-control form-control-sm readonly" data-final-display readonly
                                   value="{{ $r['final'] ?? '' }}">
                        </td>

                        <td class="text-center" data-remark>
                            {{ $r['final'] === null ? '—' : ($r['final'] >= 75 ? 'PASSED' : 'FAILED') }}
                        </td>

                        <td class="text-center" data-descriptor>
                            @if($r['descriptor'])
                                {{ $r['descriptor'] }} ({{ $r['descriptor_abbr'] }})
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No subjects found for this selection.</td></tr>
                @endforelse
                </tbody>

                @if($rows->count())
                <tfoot>
                    <tr class="table-light">
                        <th>General Average</th>
                        <th colspan="3"></th>
                        <th></th>
                        <th class="text-center" id="gaCell">{{ $generalAverage ?? '—' }}</th>
                        <th class="text-center" id="gaRemark">
                            {{ $generalAverage !== null ? ($generalAverage >= 75 ? 'PASSED' : 'FAILED') : '—' }}
                        </th>
                        <th class="text-center" id="gaDescr">
                            @php
                                $gaDesc = null; $gaAbbr = null;
                                if ($generalAverage !== null) {
                                    if ($generalAverage >= 90) { $gaDesc='Outstanding'; $gaAbbr='O'; }
                                    elseif ($generalAverage >= 85) { $gaDesc='Very Satisfactory'; $gaAbbr='VS'; }
                                    elseif ($generalAverage >= 80) { $gaDesc='Satisfactory'; $gaAbbr='S'; }
                                    elseif ($generalAverage >= 75) { $gaDesc='Fairly Satisfactory'; $gaAbbr='FS'; }
                                    else { $gaDesc='Did Not Meet Expectations'; $gaAbbr='DNME'; }
                                }
                            @endphp
                            {{ $gaDesc ? ($gaDesc.' ('.$gaAbbr.')') : '—' }}
                        </th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit" {{ (!$rows->count() || !$hasAnyOpen) ? 'disabled' : '' }}>
                <i class="bi bi-save me-1"></i> Save
            </button>
        </div>
    </form>
    @else
        <div class="alert alert-info">
            Select a <strong>School Year</strong>, <strong>Grade Level</strong>, and <strong>Student</strong>, then click <strong>View</strong> to edit grades.
        </div>
    @endif

    {{-- =========================
         REPORT CARD (Print target)
    ========================== --}}
    <div class="report-card mt-4" id="reportCard">
        <div class="report-header text-center mb-2">
            <h5 class="mb-0">Report on Learner’s Progress</h5>
            <div class="text-muted">Quarterly Grades and Final Rating (DepEd Format)</div>
        </div>

        {{-- Student details --}}
        <div class="report-meta">
            <div class="kv"><div class="k">Student</div><div class="v" id="rpStudent">{{ $studentName ?: '—' }}</div></div>
            <div class="kv"><div class="k">Grade Level</div><div class="v" id="rpGrade">{{ $gradeForReport }}</div></div>
            <div class="kv"><div class="k">School Year</div><div class="v" id="rpSY">{{ $schoolYrText }}</div></div>
            <div class="kv"><div class="k">Printed On</div><div class="v" id="rpPrinted">{{ $printedOn }}</div></div>
        </div>

        <div class="table-responsive mt-2">
            <table class="table table-bordered align-middle report-table">
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
                <tbody id="reportTbody">
                    @forelse($rows as $r)
                        <tr>
                            <td class="rp-subject">{{ $r['subject_label'] }}</td>
                            <td class="text-center rp-q1">{{ $r['q1'] ?? '—' }}</td>
                            <td class="text-center rp-q2">{{ $r['q2'] ?? '—' }}</td>
                            <td class="text-center rp-q3">{{ $r['q3'] ?? '—' }}</td>
                            <td class="text-center rp-q4">{{ $r['q4'] ?? '—' }}</td>
                            <td class="text-center fw-semibold rp-final">{{ $r['final'] ?? '—' }}</td>
                            <td class="text-center rp-remark {{ (($r['final'] ?? null) !== null && $r['final'] < 75) ? 'text-danger fw-semibold' : '' }}">
                                {{ ($r['final'] ?? null) === null ? '—' : (($r['final'] >= 75) ? 'PASSED' : 'FAILED') }}
                            </td>
                            <td class="text-center rp-descr">
                                @if($r['descriptor'])
                                    {{ $r['descriptor'] }} ({{ $r['descriptor_abbr'] }})
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Select a school year, grade, and student to view grades.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if(!empty($rows) && (method_exists($rows, 'isNotEmpty') ? $rows->isNotEmpty() : count($rows)))
                    <tfoot>
                        <tr class="table-light">
                            <th>General Average</th>
                            <th colspan="3"></th>
                            <th></th>
                            <th class="text-center" id="rpGA">{{ $generalAverage ?? '—' }}</th>
                            <th class="text-center" id="rpGARemark">
                                {{ ($generalAverage !== null ? ($generalAverage >= 75 ? 'PASSED' : 'FAILED') : '—') }}
                            </th>
                            <th class="text-center" id="rpGADescr">
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
            Notes: Final Grade is the rounded average of available quarters (Q1–Q4). Remarks: ≥75 PASSED; &lt;75 FAILED.
            Descriptors: O (90–100), VS (85–89), S (80–84), FS (75–79), DNME (&lt;75).
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const STUDENTS_BY_GRADE   = @json($studentsByGrade);
const ALL_STUDENTS        = @json($allStudentsList);
const PRESELECT_GRADE     = @json((string) ($selectedGrade ?? ''));
const PRESELECT_STUDENT   = @json((string) ($selectedStudent ?? ''));

// util
function opt(v, t, sel = false, disabled = false) {
    const o = document.createElement('option');
    o.value = v; o.textContent = t;
    if (sel) o.selected = true;
    if (disabled) o.disabled = true;
    return o;
}

// Populate students by grade (client-side)
function populateStudents(grade, selectedId) {
    const sel = document.getElementById('studentSelect');
    if (!sel) return;
    sel.innerHTML = '';

    if (grade && String(grade).trim() !== '') {
        const list = (STUDENTS_BY_GRADE && STUDENTS_BY_GRADE[grade]) ? STUDENTS_BY_GRADE[grade] : [];
        if (!list.length) {
            sel.appendChild(opt('', '— No students in this grade —', true, true));
            return;
        }
        sel.appendChild(opt('', 'Select student in this grade', !selectedId));
        list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
        return;
    }

    const list = ALL_STUDENTS || [];
    sel.appendChild(opt('', 'Select student', !selectedId));
    list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
}

// Live compute per-row final/remark/descriptor + GA
function rowCompute(tr) {
    const inputs = Array.from(tr.querySelectorAll('input.q'));
    const clamp = (n) => Math.min(100, Math.max(0, n));

    const q = [0,1,2,3].map(i => {
        const inp = inputs[i];
        if (!inp) return 0;
        const raw = (inp.value || '').trim();
        const n = raw === '' ? 0 : clamp(parseInt(raw, 10));
        return isNaN(n) ? 0 : n;
    });

    const finalFloat = (q[0] + q[1] + q[2] + q[3]) / 4;
    const final = (finalFloat <= 70) ? 0 : Math.round(finalFloat);

    const disp = tr.querySelector('[data-final-display]');
    if (disp) disp.value = final;

    const remarkCell = tr.querySelector('[data-remark]');
    const descrCell  = tr.querySelector('[data-descriptor]');

    if (remarkCell) remarkCell.textContent = final >= 75 ? 'PASSED' : 'FAILED';

    if (descrCell) {
        let desc = 'Did Not Meet Expectations', abbr = 'DNME';
        if (final >= 90) { desc='Outstanding'; abbr='O'; }
        else if (final >= 85) { desc='Very Satisfactory'; abbr='VS'; }
        else if (final >= 80) { desc='Satisfactory'; abbr='S'; }
        else if (final >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
        descrCell.textContent = `${desc} (${abbr})`;
    }
}

function computeGA() {
    const finals = Array.from(document.querySelectorAll('[data-final-display]'))
        .map(i => {
            const v = (i.value || '').trim();
            return v === '' ? null : parseInt(v, 10);
        })
        .filter(v => v !== null && !Number.isNaN(v));

    const gaCell = document.getElementById('gaCell');
    const gaRemark = document.getElementById('gaRemark');
    const gaDescr = document.getElementById('gaDescr');
    if (!gaCell) return;

    if (!finals.length) {
        gaCell.textContent = '—';
        if (gaRemark) gaRemark.textContent = '—';
        if (gaDescr)  gaDescr.textContent  = '—';
        return;
    }

    const avg = Math.round(finals.reduce((a,b)=>a+b,0) / finals.length);
    gaCell.textContent = avg;
    if (gaRemark) gaRemark.textContent = avg >= 75 ? 'PASSED' : 'FAILED';

    let desc = 'Did Not Meet Expectations', abbr='DNME';
    if (avg >= 90) { desc='Outstanding'; abbr='O'; }
    else if (avg >= 85) { desc='Very Satisfactory'; abbr='VS'; }
    else if (avg >= 80) { desc='Satisfactory'; abbr='S'; }
    else if (avg >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
    if (gaDescr) gaDescr.textContent = `${desc} (${abbr})`;
}

/* ===== Sync the REPORT (print view) with the EDITOR before printing ===== */
function syncReportFromEditor() {
    const editorRows = Array.from(document.querySelectorAll('#gradesBody tr[data-row]'));
    const reportRows = Array.from(document.querySelectorAll('#reportTbody tr'));

    // If counts mismatch, just bail silently; we'll print whatever server gave.
    if (!editorRows.length || editorRows.length !== reportRows.length) return;

    const clamp01 = (n) => Math.min(100, Math.max(0, n));
    const toInt = (v) => {
        const n = parseInt(String(v ?? '').trim(), 10);
        return Number.isNaN(n) ? null : clamp01(n);
    };

    let finals = [];

    for (let i=0; i<editorRows.length; i++) {
        const er = editorRows[i];
        const rr = reportRows[i];

        const qInputs = er.querySelectorAll('input.q');
        const getVal = (idx) => {
            const raw = qInputs[idx]?.value ?? '';
            const n = toInt(raw);
            return n === null ? '—' : n;
        };

        // Qs
        const q1 = getVal(0), q2 = getVal(1), q3 = getVal(2), q4 = getVal(3);

        rr.querySelector('.rp-q1')?.replaceChildren(document.createTextNode(q1));
        rr.querySelector('.rp-q2')?.replaceChildren(document.createTextNode(q2));
        rr.querySelector('.rp-q3')?.replaceChildren(document.createTextNode(q3));
        rr.querySelector('.rp-q4')?.replaceChildren(document.createTextNode(q4));

        const nums = [q1,q2,q3,q4].map(v => (v === '—' ? 0 : Number(v)));
        const avg  = (nums[0]+nums[1]+nums[2]+nums[3])/4;
        const fin  = (avg <= 70) ? 0 : Math.round(avg);
        finals.push(fin);

        const rpFinal  = rr.querySelector('.rp-final');
        const rpRemark = rr.querySelector('.rp-remark');
        const rpDescr  = rr.querySelector('.rp-descr');

        if (rpFinal)  rpFinal.textContent  = fin;
        if (rpRemark) {
            rpRemark.textContent = fin >= 75 ? 'PASSED' : 'FAILED';
            rpRemark.classList.toggle('text-danger', fin < 75);
            rpRemark.classList.toggle('fw-semibold', fin < 75);
        }
        if (rpDescr) {
            let desc='Did Not Meet Expectations', abbr='DNME';
            if (fin >= 90) { desc='Outstanding'; abbr='O'; }
            else if (fin >= 85) { desc='Very Satisfactory'; abbr='VS'; }
            else if (fin >= 80) { desc='Satisfactory'; abbr='S'; }
            else if (fin >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
            rpDescr.textContent = `${desc} (${abbr})`;
        }
    }

    // GA
    if (finals.length) {
        const ga = Math.round(finals.reduce((a,b)=>a+b,0)/finals.length);
        const rpGA       = document.getElementById('rpGA');
        const rpGARemark = document.getElementById('rpGARemark');
        const rpGADescr  = document.getElementById('rpGADescr');

        if (rpGA) rpGA.textContent = ga;
        if (rpGARemark) rpGARemark.textContent = ga >= 75 ? 'PASSED' : 'FAILED';
        if (rpGADescr) {
            let desc='Did Not Meet Expectations', abbr='DNME';
            if (ga >= 90) { desc='Outstanding'; abbr='O'; }
            else if (ga >= 85) { desc='Very Satisfactory'; abbr='VS'; }
            else if (ga >= 80) { desc='Satisfactory'; abbr='S'; }
            else if (ga >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
            rpGADescr.textContent = `${desc} (${abbr})`;
        }
    }
}

/* ===== Half-page layout when printing a single report ===== */
function applyHalfPageIfSingle() {
    const rc = document.getElementById('reportCard');
    if (!rc) return;
    // If there is only one report-card on page, occupy the top half
    const count = document.querySelectorAll('.report-card').length;
    rc.classList.toggle('print-halfpage', count === 1);
}

document.addEventListener('DOMContentLoaded', function () {
    const gradeSel = document.getElementById('gradeLevelFilter');
    const stuSel   = document.getElementById('studentSelect');
    const sySel    = document.getElementById('schoolyrSelect');
    const printBtn = document.getElementById('printBtn');

    // Initial student list (respect preselects)
    populateStudents(gradeSel?.value || PRESELECT_GRADE, PRESELECT_STUDENT);

    // When grade changes, rebuild student list and clear selection
    gradeSel?.addEventListener('change', function () {
        populateStudents(this.value, null);
    });

    // Keep hidden fields in sync for POST
    function syncHidden() {
        const hfSy = document.getElementById('hf_sy');
        const hfGr = document.getElementById('hf_grade');
        const hfSt = document.getElementById('hf_stu');
        if (hfSy) hfSy.value = sySel.value;
        if (hfGr) hfGr.value = gradeSel.value;
        if (hfSt) hfSt.value = stuSel.value;
    }
    [sySel, gradeSel, stuSel].forEach(el => el?.addEventListener('change', syncHidden));
    syncHidden();

    // Wire editor row listeners
    document.querySelectorAll('tr[data-row]').forEach(tr => {
        tr.querySelectorAll('input.q').forEach(inp => {
            inp.addEventListener('input', () => { rowCompute(tr); computeGA(); });
        });
        rowCompute(tr); // initial
    });
    computeGA();

    // Ensure report reflects editor values and layout at print time
    window.addEventListener('beforeprint', () => { syncReportFromEditor(); applyHalfPageIfSingle(); });
    window.addEventListener('afterprint',  () => { document.getElementById('reportCard')?.classList.remove('print-halfpage'); });

    printBtn?.addEventListener('click', () => {
        syncReportFromEditor();
        applyHalfPageIfSingle();
        window.print();
    });
});
</script>
@endpush
