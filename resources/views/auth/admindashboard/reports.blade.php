@extends('layouts.admin')

@section('title', 'Enrollment Reports')

@push('styles')
    <style>
        .grade-section { margin-top: 1rem; }
        .grade-section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.25rem; }
        .grade-title { display:flex; gap:.5rem; align-items:center; font-weight:600; }
        .report-list { display:flex; flex-direction:column; gap:1rem; }
        .report-card { border: 1px solid rgba(0,0,0,.08); border-radius:.75rem; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06); padding: 1rem; }
        .report-card-header { display:flex; justify-content:space-between; gap:1rem; }
        .report-card h5 { margin:0; font-weight:700; }
        .status-chips .badge { border-radius:9999px; padding:.25rem .6rem; font-weight:700; }
        .report-meta { display:grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap:.5rem .75rem; }
        .report-meta .kv { display:flex; gap:.5rem; }
        .report-meta .k { min-width:120px; color:#64748b; font-weight:600; }
        .report-meta .v { white-space:pre-wrap; }
        .totals-row td { font-weight:600; border-top:2px solid #ddd; }
        @media (max-width:992px){
            .report-meta { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width:576px){
            .report-meta { grid-template-columns: 1fr; }
        }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 14mm 12mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
@endpush

@section('content')
@php
    // Defensive defaults
    $schoolyrs   = $schoolyrs   ?? collect();
    $gradelvls   = $gradelvls   ?? collect();
    $sy_id       = $sy_id       ?? request('sy_id');
    $student_id  = $student_id  ?? request('student_id');
    $gradelvl_id = $gradelvl_id ?? request('gradelvl_id');
    $grouped     = $grouped     ?? collect();
    $enrollments = $enrollments ?? null;
@endphp

<div class="container-fluid py-3 students-page">

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div class="section-title">
            <h3 class="mb-0">Enrollment Reports</h3>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="search-wrap">
                <input type="text" id="pageSearch" class="form-control form-control-sm"
                       placeholder="Quick search in page (name/contact/email)…">
            </div>
            <div class="toolbar d-flex gap-6">
                <a id="printLink" class="btn btn-outline-secondary btn-sm" href="#">
                    <i class="bi bi-printer"></i> Print All
                </a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="get" class="filters row g-2 align-items-end mb-3" id="filtersForm">
        <div class="col-auto">
            <label class="form-label">School Year</label>
            <select name="sy_id" id="sySelect" class="form-select">
                @foreach ($schoolyrs as $sy)
                    <option value="{{ $sy->id }}" @selected((int) $sy_id === (int) $sy->id)>{{ $sy->school_year }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label">Grade Level</label>
            {{-- value = gradelvls.id for server, data-label = grade text for client filter --}}
            <select class="form-select" id="gradelvlSelect" name="gradelvl_id">
                <option value="" data-label="">All</option>
                @foreach ($gradelvls as $gl)
                    <option value="{{ $gl->id }}"
                            data-label="{{ $gl->grade_level }}"
                            @selected((int) $gradelvl_id === (int) $gl->id)>
                        {{ $gl->grade_level }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Student (native select; repopulated via AJAX; always includes “All”) --}}
        <div class="col-auto" style="min-width:300px">
            <label class="form-label">Student</label>
            <select id="studentSelect" class="form-select">
                <option value="">— All Students —</option>
            </select>
            <input type="hidden" name="student_id" id="studentIdInput" value="{{ $student_id }}">
        </div>

        {{-- Client-only filters --}}
        <div class="col-auto">
            <label class="form-label">Enroll Status</label>
            <select class="form-select" id="statusSelect" name="status">
                <option value="">All</option>
                <option value="Enrolled" @selected(request('status') === 'Enrolled')>Enrolled</option>
                <option value="Not Enrolled" @selected(request('status') === 'Not Enrolled')>Not Enrolled</option>
            </select>
        </div>
    </form>

    {{-- GROUPED SECTIONS BY GRADE --}}
    @php $byGrade = $grouped; @endphp

    @forelse($byGrade as $grade => $rows)
        <section class="grade-section" data-grade-name="{{ $grade ?: '— No Grade —' }}">
            <div class="grade-section-header">
                <div class="grade-title">
                    <span class="badge bg-light text-dark border">{{ $grade ?: '— No Grade —' }}</span>
                    <span class="count">({{ $rows->count() }})</span>
                </div>
            </div>

            <div class="report-list">
                @foreach ($rows as $en)
                    @php
                        $s = $en->student ?? null;
                        $g = ($en->guardian ?? null) ?: ($s?->guardian);
                        $syText = $en->schoolyr?->school_year ?? '—';

                        // Names / contacts
                        $mFirst = trim(collect([data_get($g,'m_firstname'), data_get($g,'m_middlename')])->filter()->implode(' '));
                        $mLast  = (string) data_get($g,'m_lastname', '');
                        $fFirst = trim(collect([data_get($g,'f_firstname'), data_get($g,'f_middlename')])->filter()->implode(' '));
                        $fLast  = (string) data_get($g,'f_lastname', '');
                        $motherFull = trim(($mFirst ? $mFirst.' ' : '').$mLast);
                        $fatherFull = trim(($fFirst ? $fFirst.' ' : '').$fLast);

                        $guardianPresent = null;
                        if ($g && isset($g->guardian_name) && $g->guardian_name) {
                            $guardianPresent = $g->guardian_name;
                        } elseif ($g && (isset($g->g_firstname) || isset($g->g_lastname))) {
                            $guardianPresent = trim(collect([$g->g_firstname ?? null, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' '));
                        }

                        $pgLines = [];
                        $pgLines[] = 'Father: ' . ($fatherFull ?: '—');
                        $pgLines[] = 'Mother: ' . ($motherFull ?: '—');
                        $pgLines[] = 'Guardian: ' . ($guardianPresent ?: '—');
                        $parentsGuardianDisplay = implode("\n", $pgLines);

                        $guardianContact = $g?->g_contact ?: ($g?->m_contact ?: ($g?->f_contact ?: ($g?->g_email ?: '—')));
                        $cardId = 'rep-card-' . $en->id;
                        $enrollStatus = ($en->status ?? '') === 'Enrolled' ? 'Enrolled' : 'Not Enrolled';
                    @endphp

                    <article class="report-card searchable-card"
                             id="{{ $cardId }}"
                             data-id="{{ $en->id }}"
                             data-enrollstatus="{{ $enrollStatus }}">
                        <div class="report-card-header">
                            <div>
                                <h5 title="{{ $s?->full_name ?? '—' }}">{{ $s?->full_name ?? '—' }}</h5>

                                <div class="status-chips mt-1">
                                    @if($enrollStatus === 'Enrolled')
                                        <span class="badge bg-success badge-pill">Enrolled</span>
                                    @else
                                        <span class="badge bg-secondary badge-pill">Not Enrolled</span>
                                    @endif

                                    <span class="badge bg-light text-dark border">{{ $syText }}</span>
                                </div>

                                <div class="report-meta mt-2">
                                    <div class="kv">
                                        <div class="k">Student Address</div>
                                        <div class="v">{{ $s?->s_address ?? '—' }}</div>
                                    </div>
                                    <div class="kv">
                                        <div class="k">Birthdate</div>
                                        <div class="v">{{ $s?->s_birthdate ? \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') : '—' }}</div>
                                    </div>
                                    <div class="kv">
                                        <div class="k">Parents / Guardian</div>
                                        <div class="v">{{ $parentsGuardianDisplay }}</div>
                                    </div>
                                    <div class="kv">
                                        <div class="k">Household Contact</div>
                                        <div class="v">{{ $guardianContact }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-print-card" data-card-id="{{ $cardId }}">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card mt-3 p-3"><p class="text-muted mb-0">No enrollments found.</p></div>
    @endforelse

    {{-- PAGE SUMMARY (no financial totals) --}}
    <div class="card mt-3 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <strong>Total enrollments on this page:</strong>
                {{ $enrollments ? $enrollments->count() : 0 }}
            </div>
            @if($enrollments && method_exists($enrollments, 'links'))
                <div class="mt-2">
                    {{ $enrollments->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
/* ================== Helpers ================== */
function getSelectedGradeLabel() {
    const sel = document.getElementById('gradelvlSelect');
    if (!sel || sel.selectedIndex < 0) return '';
    return sel.options[sel.selectedIndex].getAttribute('data-label') || '';
}

/* ===== Client filters (instant) ===== */
function applyClientFilters() {
    const q   = (document.getElementById('pageSearch')?.value || '').toLowerCase();
    const enr = document.getElementById('statusSelect')?.value || '';

    const chosenGradeLabel = getSelectedGradeLabel();

    // per-card filter
    const cards = Array.from(document.querySelectorAll('.report-card'));
    cards.forEach(card => {
        const textOk   = !q   || card.innerText.toLowerCase().includes(q);
        const enrollOk = !enr || (card.dataset.enrollstatus === enr);
        card.style.display = (textOk && enrollOk) ? '' : 'none';
    });

    // section visibility (grade match + has visible cards)
    const sections = Array.from(document.querySelectorAll('.grade-section'));
    sections.forEach(sec => {
        const gradeName   = sec.getAttribute('data-grade-name') || '';
        const matchesGrade = !chosenGradeLabel || gradeName === chosenGradeLabel;
        const hasVisible   = !!sec.querySelector('.report-card:not([style*="display: none"])');
        sec.style.display  = (matchesGrade && hasVisible) ? '' : 'none';
    });
}

// Wire client filters
document.getElementById('pageSearch')?.addEventListener('input', applyClientFilters);
document.getElementById('statusSelect')?.addEventListener('change', applyClientFilters);

/* ===== Server bits: School Year change submits immediately ===== */
(function () {
    const form = document.getElementById('filtersForm');
    document.getElementById('sySelect')?.addEventListener('change', () => form.requestSubmit());
})();

/* ========= Student dropdown (native select; repopulates by Grade) ========= */
(function () {
    const form          = document.getElementById('filtersForm');
    const gradeSel      = document.getElementById('gradelvlSelect');   // value = grade ID
    const studentSel    = document.getElementById('studentSelect');    // native select
    const studentHidden = document.getElementById('studentIdInput');   // keeps ?student_id=
    const preSelectedId = @json((string) ($student_id ?? ''));

    function makeOpt(val, label, selected = false) {
        const o = document.createElement('option');
        o.value = val ?? '';
        o.textContent = label ?? '';
        if (selected) o.selected = true;
        return o;
    }

    async function loadStudents(gradeId, preId) {
        studentSel.innerHTML = '';
        studentSel.appendChild(makeOpt('', '— All Students —', !preId));

        try {
            const base = @json(route('admin.reports.enrollments.students'));
            const url = new URL(base, window.location.origin);
            if (gradeId) url.searchParams.set('gradelvl_id', gradeId);
            const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            (Array.isArray(data) ? data : []).forEach(s => {
                studentSel.appendChild(makeOpt(String(s.id), String(s.name), preId && String(s.id) === String(preId)));
            });
        } catch {
            // keep only "All Students" on error
        }
    }

    // When Student changes, update hidden and submit
    studentSel.addEventListener('change', function () {
        studentHidden.value = studentSel.value || '';
        form.requestSubmit();
    });

    // When Grade changes: reload Student list; also submit so server applies grade filter
    gradeSel.addEventListener('change', function () {
        const gid = gradeSel.value || '';
        studentSel.value = '';
        studentHidden.value = '';
        loadStudents(gid, '');
        form.requestSubmit();
        if (typeof applyClientFilters === 'function') applyClientFilters();
    });

    // Initial populate
    (function init() {
        const gid = gradeSel ? (gradeSel.value || '') : '';
        loadStudents(gid, preSelectedId);
    })();
})();

/* ===== Initial client filter pass ===== */
applyClientFilters();

/* ================== Printing helpers ================== */
function printHTML(html) {
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('aria-hidden', 'true');
    document.body.appendChild(iframe);

    const win = iframe.contentWindow;
    const doc = iframe.contentDocument || win.document;

    let printed = false;
    function finishOnce() {
        if (printed) return;
        printed = true;
        try { win.focus(); win.print(); }
        finally { setTimeout(() => iframe.remove(), 500); }
    }

    iframe.addEventListener('load', finishOnce, { once: true });

    doc.open();
    doc.write(html);
    doc.close();

    setTimeout(finishOnce, 1200);
}

// Per-card Print
(function(){
    function buildCardPrintHTML(cardEl){
        const title='Enrollment Report';
        const now=new Date().toLocaleString();
        const content=cardEl.cloneNode(true);
        content.querySelectorAll('.btn-print-card').forEach(b => b.remove());

        const css=`
            @page { size: A4 portrait; margin: 16mm 14mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
            h1 { margin: 0 0 6px 0; font-size: 18px; color: #1f2937; }
            .sub { color: #6b7280; margin-bottom: 12px; }
            .report-card { border: 1px solid #ddd; border-radius: 8px; padding: 14px; box-shadow:none; }
            .report-meta { margin-top: 8px; }
        `;
        return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>${css}</style></head>
        <body>
          <header><h1>${title}</h1><div class="sub">Generated: ${now}</div></header>
          ${content.outerHTML}
        </body></html>`;
    }

    document.addEventListener('click', e=>{
        const btn=e.target.closest('.btn-print-card'); if(!btn) return;
        const card=document.getElementById(btn.getAttribute('data-card-id')); if(!card) return;
        const html=buildCardPrintHTML(card);
        printHTML(html);
    });
})();

// Bulk Print (visible cards only)
(function(){
    function buildBulkPrintHTML() {
        const title='Enrollment Reports';
        const now=new Date().toLocaleString();

        const sections=[...document.querySelectorAll('.grade-section')].filter(s=>s.style.display!=='none');
        const cards=[];
        sections.forEach(sec=>{
            cards.push(...[...sec.querySelectorAll('.report-card')].filter(c=>c.style.display!=='none'));
        });

        const clones=cards.map(c=>{
            const x=c.cloneNode(true);
            x.querySelectorAll('.btn-print-card').forEach(b => b.remove());
            return `<div class="card-wrap">${x.outerHTML}</div>`;
        }).join('');

        const css=`
            @page { size: A4 portrait; margin: 14mm 12mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { font-family: Arial, sans-serif; font-size: 11.5px; color:#111827; }
            h1 { margin: 0 0 8px; font-size: 18px; }
            .sub { color:#6b7280; margin-bottom: 12px; }
            .card-wrap { break-inside: avoid; page-break-inside: avoid; margin: 0 0 10px; }
            .report-card { border:1px solid #ddd; border-radius:8px; padding:12px; box-shadow:none; }
        `;

        return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>${css}</style></head>
        <body>
          <header><h1>${title}</h1><div class="sub">Generated: ${now} · Showing ${cards.length} record(s)</div></header>
          ${clones || '<div class="sub">No records to print.</div>'}
        </body></html>`;
    }

    document.getElementById('printLink')?.addEventListener('click', function(e){
        e.preventDefault();
        const html=buildBulkPrintHTML();
        printHTML(html);
    });
})();
</script>
@endpush
