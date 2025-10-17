{{-- resources/views/faculty/grades/index.blade.php --}}
@extends('layouts.faculty')
@section('title','Faculty · Grades')

@push('styles')
<style>
    /* Page-specific tweaks that play nice with your base CSS */
    .table-report thead th { position: sticky; top: 0; background: var(--bs-body-bg); z-index: 1; }
    .qcell { width: 88px; }
    .finalcell { width: 110px; }
    .readonly { background: #f8f9fa; }
    .badge-lock { background: #ffe9e9; color:#a40000; border:1px solid #ffcccc; }
    .badge-open { background: #e9fff1; color:#0a7e2f; border:1px solid #c9f2d9; }

    @media print {
        .no-print { display:none!important; }
        @page { size: A4 portrait; margin: 12mm 12mm; }
        .table-report tr, .table-report td, .table-report th { page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')
@php
    // Build student lists for the dynamic dropdown
    $allStudentsList = collect($students)->map(function ($s) {
        $mid = trim((string)($s->s_middlename ?? ''));
        $name = trim(implode(' ', array_filter([$s->s_firstname ?? '', $mid, $s->s_lastname ?? ''])));
        return ['id'=>$s->id, 'name'=>($name ?: ('Student #'.$s->id)), 'grade'=>$s->s_gradelvl ?? ''];
    })->values();
    $studentsByGrade = $allStudentsList->groupBy('grade')->map->values();

    $selectedSchoolYr = $schoolyrId ?? request('schoolyr_id');
    $selectedGrade    = $gradeLevel ?? request('grade_level');
    $selectedStudent  = $studentId ?? request('student_id');

    // Show editor only when user clicked View (i.e., selections are present)
    $showEditor = filled($selectedSchoolYr) && filled($selectedGrade) && filled($selectedStudent);

    // Quarter open/closed flags provided by Admin (default: all open)
    $quartersOpen = array_merge(['q1'=>true,'q2'=>true,'q3'=>true,'q4'=>true], $quartersOpen ?? []);
    $hasAnyOpen = in_array(true, $quartersOpen, true);
@endphp

<div class="card section p-4">
    <!-- Header matches base layout patterns -->
    <div id="dashboard-header" class="mb-3">
        <div class="intro">
            <div>
                <h5 class="mb-1">Grades</h5>
                <div class="text-muted small">Enter quarterly grades and auto-compute final & GA.</div>
            </div>
        </div>

        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-number">{{ count($allStudentsList) }}</div>
                <div class="kpi-label">Students</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($gradelvls ?? [])->count() }}</div>
                <div class="kpi-label">Grade Levels</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($schoolyrs ?? [])->count() }}</div>
                <div class="kpi-label">School Years</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($quartersOpen)->filter()->count() }}/4</div>
                <div class="kpi-label">Open Quarters</div>
            </div>
        </div>

        <div class="pay-card p-3 text-center">
            <h6 class="mb-1">Quick Action</h6>
            <p class="text-muted mb-3 small">Print your current view.</p>
            <button class="btn btn-outline-dark btn-sm no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
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

    {{-- Filters (GET) --}}
    <div class="card p-3 mb-3">
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
                <select name="student_id" id="studentSelect" class="form-select" required>
                    <option value="">Select student</option>
                    {{-- populated by JS --}}
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i> View</button>
            </div>
        </form>
    </div>

    {{-- Quarter status banner --}}
    @if($showEditor)
        <div class="d-flex flex-wrap gap-2 mb-3">
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

    {{-- Editor (POST) --}}
    @if($showEditor)
    <form id="gradesForm" action="{{ route('faculty.grades.store') }}" method="POST" autocomplete="off">
        @csrf
        {{-- carry filters along --}}
        <input type="hidden" name="schoolyr_id"  id="hf_sy"    value="{{ $selectedSchoolYr }}">
        <input type="hidden" name="grade_level"  id="hf_grade" value="{{ $selectedGrade }}">
        <input type="hidden" name="student_id"   id="hf_stu"   value="{{ $selectedStudent }}">

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
                            <input type="hidden" name="subject_id[]" value="{{ $r['subject_id'] }}">
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
            <a class="btn btn-outline-secondary" href="{{ route('faculty.grades.index', [
                'schoolyr_id'=>$selectedSchoolYr,
                'grade_level'=>$selectedGrade,
                'student_id'=>$selectedStudent
            ]) }}">Reset</a>
        </div>
    </form>
    @else
        <div class="alert alert-info">
            Select a <strong>School Year</strong>, <strong>Grade Level</strong>, and <strong>Student</strong>, then click <strong>View</strong> to edit grades.
        </div>
    @endif
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

// Live compute per-row final/remark/descriptor + GA (25% each, blanks = 0, <=70 => 0)
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

document.addEventListener('DOMContentLoaded', function () {
    const gradeSel = document.getElementById('gradeLevelFilter');
    const stuSel   = document.getElementById('studentSelect');
    const sySel    = document.getElementById('schoolyrSelect');

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

    // Wire row listeners
    document.querySelectorAll('tr[data-row]').forEach(tr => {
        tr.querySelectorAll('input.q').forEach(inp => {
            inp.addEventListener('input', () => { rowCompute(tr); computeGA(); });
        });
        // initial compute
        rowCompute(tr);
    });
    computeGA();
});
</script>
@endpush
