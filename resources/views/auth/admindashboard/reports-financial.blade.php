@extends('layouts.admin')

@section('title', 'Financial Reports')

@push('styles')
    <style>
        .fin-summary-card { border:1px solid #e2e8f0; border-radius:.5rem; padding:.75rem 1rem; background:#f8fafc; }
        .fin-summary-card .label { font-size:.8rem; color:#64748b; }
        .fin-summary-card .value { font-weight:700; font-size:1.1rem; }
        @media print {
            .no-print { display:none !important; }
            @page { size:A4 portrait; margin:14mm 12mm; }
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
@endpush

@section('content')
@php
    $schoolyrs   = $schoolyrs   ?? collect();
    $gradelvls   = $gradelvls   ?? collect();
    $sy_id       = $sy_id       ?? null;
    $gradelvl_id = $gradelvl_id ?? null;
    $student_id  = $student_id  ?? null;
    $method      = $method      ?? '';
    $status      = $status      ?? '';
    $q           = $q           ?? '';
    $dateFrom    = $dateFrom    ?? '';
    $dateTo      = $dateTo      ?? '';
    $payments    = $payments    ?? null;
    $pageIn      = $pageIn      ?? 0;
    $totalIn     = $totalIn     ?? 0;
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Financial Report</h3>
        <div class="text-muted small">
            Incoming payments for the selected School Year.
        </div>
    </div>

    {{-- Filters --}}
    <form method="get" id="filtersForm" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label mb-0 small">School Year</label>
            <select name="sy_id" id="sySelect" class="form-select form-select-sm">
                @foreach ($schoolyrs as $sy)
                    <option value="{{ $sy->id }}" @selected((int) $sy_id === (int) $sy->id)>{{ $sy->school_year }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Grade Level</label>
            <select name="gradelvl_id" id="gradelvlSelect" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach ($gradelvls as $gl)
                    <option value="{{ $gl->id }}" @selected((int) $gradelvl_id === (int) $gl->id)>
                        {{ $gl->grade_level }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-auto" style="min-width:260px">
            <label class="form-label mb-0 small">Student</label>
            <select id="studentSelect" class="form-select form-select-sm">
                <option value="">— All Students —</option>
            </select>
            <input type="hidden" name="student_id" id="studentIdInput" value="{{ $student_id }}">
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Method</label>
            <select name="payment_method" id="methodSelect" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="Cash" @selected($method === 'Cash')>Cash</option>
                <option value="G-cash" @selected($method === 'G-cash')>G-cash</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Status</label>
            <select name="payment_status" id="statusSelect" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="Paid" @selected($status === 'Paid')>Paid</option>
                <option value="Partial" @selected($status === 'Partial')>Partial</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0 small">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Search</label>
            <input type="text" name="q" class="form-control form-control-sm"
                   value="{{ $q }}" placeholder="Name / contact / method…">
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm mt-3 mt-sm-0">Apply</button>
        </div>
    </form>

    {{-- Summary --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="fin-summary-card">
                <div class="label">Total on this page</div>
                <div class="value">₱{{ number_format($pageIn, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="fin-summary-card">
                <div class="label">Total for all results (current filters)</div>
                <div class="value">₱{{ number_format($totalIn, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" id="paymentsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:110px;">Date</th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-end" style="width:140px;">Amount (₱)</th>
                        <th class="text-end" style="width:140px;">Balance After (₱)</th>
                        <th style="width:120px;">School Year</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    @php
                        $s = $p->student ?? null;
                        $name = $s
                            ? trim(implode(' ', array_filter([$s->s_firstname, $s->s_middlename, $s->s_lastname])))
                            : '—';
                        $grade = $s?->gradelvl?->grade_level ?? $s?->s_gradelvl ?? '—';
                        $syText = $p->schoolyr?->school_year ?? '—';
                    @endphp
                    <tr>
                        <td>{{ optional($p->created_at)->format('Y-m-d') }}</td>
                        <td>{{ $name }}</td>
                        <td>{{ $grade }}</td>
                        <td>{{ $p->payment_method }}</td>
                        <td>{{ $p->payment_status }}</td>
                        <td class="text-end">{{ number_format((float) $p->amount, 2) }}</td>
                        <td class="text-end">{{ $p->balance === null ? '—' : number_format((float) $p->balance, 2) }}</td>
                        <td>{{ $syText }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No payments found for the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($payments && method_exists($payments, 'links'))
            <div class="mt-2">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // School Year: submit immediately on change
    document.getElementById('sySelect')?.addEventListener('change', function () {
        document.getElementById('filtersForm')?.requestSubmit();
    });

    // Dynamic student list (reuse enrollment students endpoint)
    (function () {
        const gradeSel      = document.getElementById('gradelvlSelect');
        const studentSel    = document.getElementById('studentSelect');
        const studentHidden = document.getElementById('studentIdInput');
        const preSelectedId = @json((string)($student_id ?? ''));

        function makeOpt(val, label, selected = false) {
            const o = document.createElement('option');
            o.value = val ?? '';
            o.textContent = label ?? '';
            if (selected) o.selected = true;
            return o;
        }

        async function loadStudents(gradeId, preId) {
            if (!studentSel) return;
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
            } catch (e) {
                // keep "All Students" only on error
            }
        }

        if (studentSel) {
            studentSel.addEventListener('change', function () {
                studentHidden.value = studentSel.value || '';
                document.getElementById('filtersForm')?.requestSubmit();
            });
        }

        if (gradeSel) {
            gradeSel.addEventListener('change', function () {
                const gid = gradeSel.value || '';
                loadStudents(gid, '');
            });
        }

        // initial load
        (function init() {
            const gid = gradeSel ? (gradeSel.value || '') : '';
            loadStudents(gid, preSelectedId);
        })();
    })();
</script>
@endpush
