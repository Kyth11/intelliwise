{{-- resources/views/auth/admindashboard/reports.blade.php --}}
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
        .money-row { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:.75rem; margin-top:.75rem; }
        .money-box { border:1px dashed #cbd5e1; border-radius:.5rem; padding:.5rem .75rem; background:#f8fafc; }
        .money-box .label { font-size:.8rem; color:#64748b; }
        .money-box .amt { font-weight:700; font-size:1rem; }
        .payments-section { margin-top:.75rem; border:1px solid #e2e8f0; border-radius:.5rem; background:#fafafa; }
        .payments-title { padding:.5rem .75rem; border-bottom:1px solid #e2e8f0; background:#eef5ff; }
        .payments-scroll { max-height:220px; overflow:auto; }
        .payments-table { width:100%; border-collapse:collapse; font-size:.9rem; }
        .payments-table th, .payments-table td { border:1px solid #e5e7eb; padding:.4rem .5rem; }
        .payments-table thead th { background:#eef5ff; }
        .payments-footer { padding:.5rem .75rem; border-top:1px solid #e2e8f0; background:#fff; }
        .btn-print-card { white-space:nowrap; }
        .totals-row td { font-weight:600; border-top:2px solid #ddd; }
        @media (max-width:992px){
            .report-meta { grid-template-columns: 1fr 1fr; }
            .money-row { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width:576px){
            .report-meta { grid-template-columns: 1fr; }
            .money-row { grid-template-columns: 1fr; }
        }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 14mm 12mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .payments-scroll { max-height: none !important; overflow: visible !important; }
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
    $pageTotals  = $pageTotals  ?? ['tuition'=>0,'optional'=>0,'total_due'=>0,'paid'=>0,'balance'=>0];
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
                <option value="">All</option>
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
            <select class="form-select" id="statusSelect">
                <option value="">All</option>
                <option>Enrolled</option>
                <option>Not Enrolled</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label">Pay Status</label>
            <select class="form-select" id="payStatusSelect">
                <option value="">All</option>
                <option>Paid</option>
                <option>Partial</option>
                <option>Not Paid</option>
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

                        // Money math
                        $base = 0;
                        if (optional($s->tuition)->total_yearly !== null) {
                            $base = (float) $s->tuition->total_yearly;
                        } elseif ($s?->s_tuition_sum !== null && $s?->s_tuition_sum !== '') {
                            $base = (float) $s->s_tuition_sum;
                        }

                        $optCollection = collect($s->optionalFees ?? []);
                        $filtered = $optCollection->filter(function ($f) {
                            $scopeOk = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
                            $activeOk = !property_exists($f, 'active') || (bool) $f->active;
                            return $scopeOk && $activeOk;
                        });
                        $optional = (float) $filtered->sum(function($f){
                            $amt = $f->pivot->amount_override ?? $f->amount;
                            return (float) $amt;
                        });

                        $total = $base + $optional;

                        $paidRecords = (float) ($s?->payments()->sum('amount') ?? 0);
                        if ($s?->s_total_due !== null && $s?->s_total_due !== '') {
                            $balance = max(0, (float) $s->s_total_due);
                            $paid    = max($total - $balance, 0);
                        } else {
                            $paid    = min($paidRecords, $total);
                            $balance = max(0, round($total - $paid, 2));
                        }
                        $derivedPay = $balance <= 0.01 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Not Paid');

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
                             data-paystatus="{{ $derivedPay }}"
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

                                    <span class="badge {{ $derivedPay === 'Paid' ? 'bg-success' : ($derivedPay === 'Partial' ? 'bg-warning text-dark' : 'bg-danger') }} badge-pill">
                                        {{ $derivedPay }}
                                    </span>

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

                        <div class="money-row">
                            <div class="money-box"><div class="label">Tuition</div><div class="amt">{{ number_format($base, 2) }}</div></div>
                            <div class="money-box"><div class="label">Optional</div><div class="amt">{{ number_format($optional, 2) }}</div></div>
                            <div class="money-box"><div class="label">Total Due</div><div class="amt">{{ number_format($total, 2) }}</div></div>
                            <div class="money-box"><div class="label">Balance</div>
                                <div class="amt {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balance, 2) }}</div>
                            </div>
                        </div>

                        <section class="payments-section">
                            <div class="payments-title">
                                <div class="fw-semibold">Payments ({{ $s?->payments->count() ?? 0 }})</div>
                            </div>
                            <div class="payments-scroll">
                                @if(($s?->payments && $s->payments->count()))
                                    <table class="payments-table">
                                        <thead><tr><th style="width:110px">Date</th><th>Method</th><th>Status</th><th class="text-end" style="width:140px">Amount</th></tr></thead>
                                        <tbody>
                                            @foreach ($s->payments->sortBy('created_at') as $p)
                                                <tr>
                                                    <td>{{ optional($p->created_at)->format('Y-m-d') }}</td>
                                                    <td>{{ $p->payment_method }}</td>
                                                    <td>{{ $p->payment_status }}</td>
                                                    <td class="text-end">{{ number_format($p->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="text-muted small">No payments recorded.</div>
                                @endif
                            </div>
                            <div class="payments-footer">
                                <strong>Total Paid:</strong> {{ number_format($paid, 2) }} |
                                <strong>Remaining:</strong> {{ number_format($balance, 2) }}
                            </div>
                        </section>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card mt-3 p-3"><p class="text-muted mb-0">No enrollments found.</p></div>
    @endforelse

    {{-- PAGE TOTALS --}}
    <div class="card mt-3 p-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr class="totals-row">
                        <td class="text-end" style="width:70%"><strong>Page Totals:</strong></td>
                        <td class="text-end"><strong>Tuition:</strong> {{ number_format($pageTotals['tuition'] ?? 0, 2) }}</td>
                        <td class="text-end"><strong>Optional:</strong> {{ number_format($pageTotals['optional'] ?? 0, 2) }}</td>
                        <td class="text-end"><strong>Total Due:</strong> {{ number_format($pageTotals['total_due'] ?? 0, 2) }}</td>
                        <td class="text-end"><strong>Paid:</strong> {{ number_format($pageTotals['paid'] ?? 0, 2) }}</td>
                        <td class="text-end"><strong>Balance:</strong> {{ number_format($pageTotals['balance'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($enrollments && method_exists($enrollments, 'links'))
            <div class="mt-2">{{ $enrollments->links() }}</div>
        @endif
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
    const pay = document.getElementById('payStatusSelect')?.value || '';
    const enr = document.getElementById('statusSelect')?.value || '';

    const chosenGradeLabel = getSelectedGradeLabel();

    // per-card filter
    const cards = Array.from(document.querySelectorAll('.report-card'));
    cards.forEach(card => {
        const textOk   = !q   || card.innerText.toLowerCase().includes(q);
        const payOk    = !pay || (card.dataset.paystatus === pay);
        const enrollOk = !enr || (card.dataset.enrollstatus === enr);
        card.style.display = (textOk && payOk && enrollOk) ? '' : 'none';
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
document.getElementById('payStatusSelect')?.addEventListener('change', applyClientFilters);
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
            const base = @json(route('admin.reports.enrollments.students')); // <-- fixed
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

    // When Grade changes: reload Student list; also submit if you want server to filter by grade immediately.
    gradeSel.addEventListener('change', function () {
        const gid = gradeSel.value || '';
        studentSel.value = '';
        studentHidden.value = '';
        loadStudents(gid, '');
        form.requestSubmit(); // submit so server applies grade filter
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
        const scroll=content.querySelector('.payments-scroll');
        if (scroll){ scroll.style.maxHeight='none'; scroll.style.overflow='visible'; }
        const css=`
            @page { size: A4 portrait; margin: 16mm 14mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
            h1 { margin: 0 0 6px 0; font-size: 18px; color: #1f2937; }
            .sub { color: #6b7280; margin-bottom: 12px; }
            .report-card { border: 1px solid #ddd; border-radius: 8px; padding: 14px; box-shadow:none; }
            .money-row { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:10px; }
            .money-box { border:1px dashed #ccc; border-radius:8px; padding:8px; background:#f8fafc; }
            .payments-table { width:100%; border-collapse:collapse; font-size:12px; }
            .payments-table th,.payments-table td { border:1px solid #e5e7eb; padding:6px 8px; }
            .payments-table thead th { background:#eef5ff; }
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
            const scr=x.querySelector('.payments-scroll'); if(scr){ scr.style.maxHeight='none'; scr.style.overflow='visible'; }
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
            .money-row { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:10px; }
            .money-box { border:1px dashed #ccc; border-radius:8px; padding:8px; background:#f8fafc; }
            .payments-table { width:100%; border-collapse:collapse; font-size:12px; }
            .payments-table th,.payments-table td { border:1px solid #e5e7eb; padding:6px 8px; }
            .payments-table thead th { background:#eef5ff; }
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
