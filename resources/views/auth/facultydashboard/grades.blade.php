@extends('layouts.faculty')
@section('title','Faculty · Grades')

@push('styles')
<style>
    .table-report thead th { position: sticky; top: 0; background: var(--bs-body-bg); z-index: 1; }
    .qcell { width: 88px; }
    .finalcell { width: 110px; }
    .readonly { background: #f8f9fa; }
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
@endphp

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Grades</h5>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>

    {{-- Flash --}}
    {{-- @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif --}}
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
    <form id="filterForm" class="row g-2 mb-3" method="GET" action="{{ route('faculty.grades.index') }}">
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

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100"><i class="bi bi-search"></i> View</button>
        </div>
    </form>

    {{-- Editor (POST) --}}
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
                                   value="{{ $r['q1'] }}" placeholder="—">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q2[]" min="0" max="100"
                                   value="{{ $r['q2'] }}" placeholder="—">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q3[]" min="0" max="100"
                                   value="{{ $r['q3'] }}" placeholder="—">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm q" name="q4[]" min="0" max="100"
                                   value="{{ $r['q4'] }}" placeholder="—">
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
                    <tr><td colspan="8" class="text-center text-muted">Select School Year, Grade Level, and Student to edit grades.</td></tr>
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
            <button class="btn btn-primary" type="submit" {{ !$rows->count() ? 'disabled' : '' }}>
                <i class="bi bi-save me-1"></i> Save
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('faculty.grades.index', [
                'schoolyr_id'=>$selectedSchoolYr,
                'grade_level'=>$selectedGrade,
                'student_id'=>$selectedStudent
            ]) }}">Reset</a>
        </div>
    </form>
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

// Populate students by grade
function populateStudents(grade, selectedId) {
    const sel = document.getElementById('studentSelect');
    sel.innerHTML = '';

    if (grade && String(grade).trim() !== '') {
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

// Live compute per-row final/remark/descriptor + GA
function rowCompute(tr) {
    const qs = Array.from(tr.querySelectorAll('input.q')).map(i => {
        const v = i.value.trim();
        return v === '' ? null : Math.min(100, Math.max(0, parseInt(v, 10)));
    }).filter(v => v !== null);

    const final = qs.length ? Math.round(qs.reduce((a,b)=>a+b,0) / qs.length) : null;

    const disp = tr.querySelector('[data-final-display]');
    disp.value = final ?? '';

    const remarkCell = tr.querySelector('[data-remark]');
    const descrCell  = tr.querySelector('[data-descriptor]');

    if (final === null) {
        remarkCell.textContent = '—';
        descrCell.textContent  = '—';
    } else {
        remarkCell.textContent = final >= 75 ? 'PASSED' : 'FAILED';
        let desc = 'Did Not Meet Expectations', abbr = 'DNME';
        if (final >= 90) { desc='Outstanding'; abbr='O'; }
        else if (final >= 85) { desc='Very Satisfactory'; abbr='VS'; }
        else if (final >= 80) { desc='Satisfactory'; abbr='S'; }
        else if (final >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
        descrCell.textContent = `${desc} (${abbr})`;
    }
}

// Recompute GA footer
function computeGA() {
    const finals = Array.from(document.querySelectorAll('[data-final-display]'))
        .map(i => i.value.trim() === '' ? null : parseInt(i.value, 10))
        .filter(v => v !== null);

    const gaCell = document.getElementById('gaCell');
    const gaRemark = document.getElementById('gaRemark');
    const gaDescr = document.getElementById('gaDescr');

    if (!gaCell) return;

    if (!finals.length) {
        gaCell.textContent = '—';
        gaRemark.textContent = '—';
        gaDescr.textContent = '—';
        return;
    }

    const avg = Math.round(finals.reduce((a,b)=>a+b,0) / finals.length);
    gaCell.textContent = avg;

    gaRemark.textContent = avg >= 75 ? 'PASSED' : 'FAILED';

    let desc = 'Did Not Meet Expectations', abbr='DNME';
    if (avg >= 90) { desc='Outstanding'; abbr='O'; }
    else if (avg >= 85) { desc='Very Satisfactory'; abbr='VS'; }
    else if (avg >= 80) { desc='Satisfactory'; abbr='S'; }
    else if (avg >= 75) { desc='Fairly Satisfactory'; abbr='FS'; }
    gaDescr.textContent = `${desc} (${abbr})`;
}

document.addEventListener('DOMContentLoaded', function () {
    const gradeSel = document.getElementById('gradeLevelFilter');
    const stuSel   = document.getElementById('studentSelect');
    const sySel    = document.getElementById('schoolyrSelect');

    // Initial student list
    populateStudents(gradeSel?.value || PRESELECT_GRADE, PRESELECT_STUDENT);

    // When grade changes, rebuild student list and clear selection
    gradeSel?.addEventListener('change', function () {
        populateStudents(this.value, null);
    });

    // When student changes, auto-submit GET to load their saved grades
    stuSel?.addEventListener('change', function () {
        if (!sySel.value || !gradeSel.value || !this.value) return;
        document.getElementById('filterForm').submit();
    });

    // Wire row listeners
    document.querySelectorAll('tr[data-row]').forEach(tr => {
        tr.querySelectorAll('input.q').forEach(inp => {
            inp.addEventListener('input', () => { rowCompute(tr); computeGA(); });
        });
        // initial compute
        rowCompute(tr);
    });
    computeGA();

    // Keep hidden fields in sync (so POST carries current selection)
    function syncHidden() {
        document.getElementById('hf_sy').value    = sySel.value;
        document.getElementById('hf_grade').value = gradeSel.value;
        document.getElementById('hf_stu').value   = stuSel.value;
    }
    [sySel, gradeSel, stuSel].forEach(el => el?.addEventListener('change', syncHidden));
    syncHidden();
});
</script>
@endpush
