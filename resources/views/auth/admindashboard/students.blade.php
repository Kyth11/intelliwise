{{-- resources/views/auth/admindashboard/students.blade.php --}}
@extends('layouts.admin')

@section('title', 'Students by Grade Level')

@push('styles')
    {{-- DataTables + Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    {{-- Page-only tiny tweaks (most styles live in your global CSS) --}}
    <style>
        .students-page .section-title { display:flex; gap:.5rem; align-items:baseline; }
        .students-page .search-wrap { display:flex; gap:.5rem; align-items:center; }
        .grade-card { border:1px solid rgba(0,0,0,.075); box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .student-table.table-sm td, .student-table.table-sm th { padding:.3rem .4rem; font-size:.8rem; }
        .badge-grade { font-size:.8rem; }
        .opt-fees-cell { max-width:380px; }
        .opt-fees-list { margin:0; padding-left:1rem; }
        .opt-fees-list li { line-height:1.2rem; }
        .filters .form-control, .filters .form-select { height: calc(1.5rem + .5rem + 2px); padding:.25rem .5rem; font-size:.8rem; }
    </style>
@endpush

@section('content')
<div class="card section p-4 students-page">
    @php
        /** @var \App\Models\Schoolyr|null $current */
        // Use $current from controller if provided; otherwise fall back
        $current = $current
            ?? \App\Models\Schoolyr::where('active', true)->first()
            ?? \App\Models\Schoolyr::orderBy('school_year')->first();
        // Students are already filtered by school year in the controller
    @endphp

    <!-- Header -->
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
        <div class="section-title d-flex flex-wrap gap-2 align-items-baseline">
            <h4 class="mb-0">
                Students
                @if($current)
                    from S.Y. {{ $current->school_year }}
                @endif
                (Grouped by Grade Level)
            </h4>
            <span class="text-muted ms-2">Browse, filter, edit, or archive students.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.students.create') }}" class="btn btn-success">
                <i class="bi bi-person-plus"></i> Enroll Student
            </a>
        </div>
    </div>

    {{-- ===== Build guardian dropdown options (unique per guardian id) ===== --}}
    @php
        $tuitions     = $tuitions     ?? collect();
        $gradelvls    = $gradelvls    ?? collect();
        $optionalFees = $optionalFees ?? collect();
        $students     = $students     ?? collect();

        $tuitionMap = collect($tuitions)->keyBy('grade_level');

        $guardianMap = [];
        foreach (($students ?? []) as $grade => $group) {
            foreach ($group as $s) {
                $g = $s->guardian ?? null;
                if (!$g || !isset($g->id)) continue;

                // Parents label
                $mFirst = trim(collect([data_get($g,'m_firstname'), data_get($g,'m_middlename')])->filter()->implode(' '));
                $mLast  = (string) data_get($g,'m_lastname', '');
                $fFirst = trim(collect([data_get($g,'f_firstname'), data_get($g,'f_middlename')])->filter()->implode(' '));
                $fLast  = (string) data_get($g,'f_lastname', '');
                $motherFull = trim(($mFirst ? $mFirst.' ' : '').$mLast);
                $fatherFull = trim(($fFirst ? $fFirst.' ' : '').$fLast);

                $parents = '—';
                if ($motherFull || $fatherFull) {
                    if ($motherFull && $fatherFull) {
                        if ($mLast && $fLast && strcasecmp($mLast,$fLast) === 0) {
                            $firstToUse = $fFirst ?: $mFirst;
                            $lastToUse  = $fLast ?: $mLast;
                            $parents = 'Mr. & Mrs. '.trim(($firstToUse ? $firstToUse.' ' : '').$lastToUse);
                        } else {
                            $parents = $motherFull.' & '.$fatherFull;
                        }
                    } else {
                        $parents = $fatherFull ?: $motherFull;
                    }
                }

                // Guardian label
                $guardianName = null;
                if (isset($g->guardian_name) && $g->guardian_name) {
                    $guardianName = $g->guardian_name;
                } elseif (isset($g->g_firstname) || isset($g->g_lastname)) {
                    $guardianName = trim(collect([$g->g_firstname ?? null, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' ')) ?: null;
                }

                $household = $parents;
                if ($guardianName && stripos($parents, $guardianName) === false) {
                    $household .= ' / '.$guardianName;
                }

                $guardianMap[$g->id] = $household ?: '—';
            }
        }
        $guardianOptions = collect($guardianMap)
            ->map(fn($label, $id) => ['id' => $id, 'label' => $label])
            ->sortBy('label', SORT_NATURAL|SORT_FLAG_CASE)
            ->values();
    @endphp

    {{-- ===== Filters ===== --}}
    <form class="filters row g-2 align-items-end mt-1 mb-2">
        <div class="col-auto">
            <label class="form-label mb-0 small">School Year</label>
            <select id="filterSchoolYear" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($schoolyrs as $sy)
                    <option value="{{ $sy->id }}" {{ ($current && $current->id == $sy->id) ? 'selected' : '' }}>
                        {{ $sy->school_year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Grade Level</label>
            <select id="filterGrade" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($gradelvls as $gl)
                    <option value="{{ $gl->grade_level }}">{{ $gl->grade_level }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Pay Status</label>
            <select id="filterPay" class="form-select form-select-sm">
                <option value="">All</option>
                <option>Paid</option>
                <option>Partial</option>
                <option>Unpaid</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Household / Guardian</label>
            <select id="filterGuardian" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($guardianOptions as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label mb-0 small">Search</label>
            <input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Type to filter…">
        </div>
    </form>

    {{-- ===== Grade groups ===== --}}
    @forelse($students as $grade => $group)
        <div class="card mt-2 p-2 grade-card" data-grade="{{ $grade }}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0">
                    <span class="badge bg-light text-dark border badge-grade">{{ $grade ?: '— No Grade —' }}</span>
                    <span class="text-muted">({{ $group->count() }})</span>
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle student-table">
                    <thead class="table-primary">
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Parents / Guardian</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th class="d-none">PayStatus</th>
                            <th class="d-none">GuardianId</th>
                            <th class="text-nowrap">Tools</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($group as $s)
                            @php
                                $row = $tuitionMap->get($s->s_gradelvl);

                                // Per-grade registration fee from tuition record (nullable)
                                $regFee = $row ? (float) ($row->registration_fee ?? 0) : 0.0;

                                // Base yearly from tuition or student override (includes registration fee if set in tuition)
                                $base = $row
                                    ? (float) $row->total_yearly
                                    : (($s->s_tuition_sum !== null && $s->s_tuition_sum !== '') ? (float) $s->s_tuition_sum : 0.0);

                                // Tuition display = base minus registration fee (non-negative)
                                $tuitionDisplay = max(0.0, $base - $regFee);

                                $optCollection = collect($s->optionalFees ?? []);
                                $filtered = $optCollection->filter(function ($f) {
                                    $scopeOk  = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
                                    $activeOk = !property_exists($f, 'active') || (bool)$f->active;
                                    return $scopeOk && $activeOk;
                                });

                                $opt = (float) $filtered->sum(function($f){
                                    $amt = $f->pivot->amount_override ?? $f->amount;
                                    return (float) $amt;
                                });

                                $optLabels = $filtered->map(function($f){
                                    $amt = (float) ($f->pivot->amount_override ?? $f->amount);
                                    return e($f->name) . ' (₱' . number_format($amt, 2) . ')';
                                })->values();

                                $optListHtml = $optLabels->isNotEmpty()
                                    ? '<ul class="opt-fees-list">'.collect($optLabels)->map(fn($l)=>'<li>'.$l.'</li>')->implode('').' </ul>'
                                    : '—';

                                $originalTotal = $base + $opt;

                                // Retrieve ONLY this student's payments via relation (should be student_id → lrn)
                                $paymentsForStudent = $s->payments()
                                    ->orderBy('created_at')
                                    ->get();

                                // Paid / balance based only on this student's data
                                if ($s->s_total_due !== null && $s->s_total_due !== '') {
                                    $currentBalance = max(0.0, (float) $s->s_total_due);
                                    $paid = max($originalTotal - $currentBalance, 0.0);
                                } else {
                                    $paidRecords = (float) ($paymentsForStudent->sum('amount') ?? 0);
                                    $paid = min($paidRecords, $originalTotal);
                                    $currentBalance = max(0.0, round($originalTotal - $paid, 2));
                                }

                                $derivedPay = $currentBalance <= 0.01 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');

                                // Household label
                                $g = $s->guardian;
                                $mFirst = trim(collect([data_get($g,'m_firstname'), data_get($g,'m_middlename')])->filter()->implode(' '));
                                $mLast  = (string) data_get($g,'m_lastname', '');
                                $fFirst = trim(collect([data_get($g,'f_firstname'), data_get($g,'f_middlename')])->filter()->implode(' '));
                                $fLast  = (string) data_get($g,'f_lastname', '');
                                $motherFull = trim(($mFirst ? $mFirst.' ' : '').$mLast);
                                $fatherFull = trim(($fFirst ? $fFirst.' ' : '').$fLast);

                                $parents = '—';
                                if ($motherFull || $fatherFull) {
                                    if ($motherFull && $fatherFull) {
                                        if ($mLast && $fLast && strcasecmp($mLast,$fLast) === 0) {
                                            $firstToUse = $fFirst ?: $mFirst;
                                            $lastToUse  = $fLast ?: $mLast;
                                            $parents = 'Mr. & Mrs. '.trim(($firstToUse ? $firstToUse.' ' : '').$lastToUse);
                                        } else {
                                            $parents = $motherFull.' & '.$fatherFull;
                                        }
                                    } else {
                                        $parents = $fatherFull ?: $motherFull;
                                    }
                                }

                                $guardianName = null;
                                if (isset($g) && isset($g->guardian_name) && $g->guardian_name) {
                                    $guardianName = $g->guardian_name;
                                }
                                if (!$guardianName && isset($g) && property_exists($g,'g_firstname')) {
                                    $legacy = trim(collect([$g->g_firstname, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' '));
                                    $guardianName = $legacy ?: null;
                                }

                                $household = $parents;
                                if ($guardianName && stripos($parents, $guardianName) === false) {
                                    $household .= ' / '.$guardianName;
                                }

                                $feeIdsCsv  = $filtered->pluck('id')->implode(',');
                                $guardianId = $s->guardian->id ?? '';

                                // Payment rows for the report modal (read only)
                                $paymentRows = $paymentsForStudent->map(function($p) {
                                    return [
                                        'date'    => optional($p->created_at)->format('Y-m-d'),
                                        'method'  => $p->payment_method,
                                        'status'  => $p->payment_status,
                                        'amount'  => (float) $p->amount,
                                        'balance' => (float) ($p->balance ?? 0),
                                    ];
                                });
                            @endphp
                            <tr data-lrn="{{ $s->lrn }}" data-paystatus="{{ $derivedPay }}" data-guardianid="{{ $guardianId }}">
                                <td>{{ $s->lrn }}</td>
                                <td>{{ $s->s_firstname }} {{ $s->s_middlename }} {{ $s->s_lastname }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') }}</td>
                                <td>{{ $household }}</td>
                                <td>{{ $s->s_contact ?? '—' }}</td>
                                <td>{{ $s->s_email ?? '—' }}</td>
                                <td class="d-none">{{ $derivedPay }}</td>
                                <td class="d-none">{{ $guardianId }}</td>
                                <td class="text-nowrap">
                                    {{-- View payments (read-only report) --}}
                                    <button type="button"
                                            class="btn btn-sm btn-info btn-view-payments"
                                            title="View payment record"
                                            data-student="{{ trim($s->s_firstname.' '.$s->s_middlename.' '.$s->s_lastname) }}"
                                            data-grade="{{ $s->s_gradelvl }}"
                                            data-total="{{ $originalTotal }}"
                                            data-paid="{{ $paid }}"
                                            data-balance="{{ $currentBalance }}"
                                            data-payments='@json($paymentRows)'>
                                        <i class="bi bi-receipt"></i>
                                    </button>

                                    {{-- Edit student --}}
                                    <button class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editStudentModal"
                                            data-lrn="{{ $s->lrn }}"
                                            data-firstname="{{ $s->s_firstname }}"
                                            data-middlename="{{ $s->s_middlename }}"
                                            data-lastname="{{ $s->s_lastname }}"
                                            data-gradelvl="{{ $s->s_gradelvl }}"
                                            data-birthdate="{{ $s->s_birthdate }}"
                                            data-address="{{ $s->s_address }}"
                                            data-contact="{{ $s->s_contact }}"
                                            data-email="{{ $s->s_email }}"
                                            data-guardian="{{ $household }}"
                                            data-guardianemail="{{ data_get($s->guardian,'g_email','') }}"
                                            data-status="{{ $s->enrollment_status }}"
                                            data-payment="{{ $s->payment_status }}"
                                            data-feeids="{{ $feeIdsCsv }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    {{-- Archive student --}}
                                    <form action="{{ route('admin.students.destroy', $s->lrn) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" title="Archive">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card mt-3 p-3"><p class="text-muted mb-0">No students found.</p></div>
    @endforelse
</div>

{{-- Payment Report Modal (read-only) --}}
<div class="modal fade" id="paymentReportModal" tabindex="-1" aria-labelledby="paymentReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentReportLabel">Payment Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Summary --}}
                <div class="mb-3">
                    <div><strong>Student:</strong> <span class="payment-student-name"></span></div>
                    <div><strong>Grade Level:</strong> <span class="payment-grade"></span></div>
                </div>

                <div class="row mb-3 g-2">
                    <div class="col-md-4">
                        <div class="border rounded p-2 small">
                            <div class="text-muted">Tuition + Fees Total</div>
                            <div class="fw-bold">₱<span class="payment-total">0.00</span></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 small">
                            <div class="text-muted">Total Paid</div>
                            <div class="fw-bold text-success">₱<span class="payment-paid">0.00</span></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 small">
                            <div class="text-muted">Remaining Balance</div>
                            <div class="fw-bold text-danger">₱<span class="payment-balance">0.00</span></div>
                        </div>
                    </div>
                </div>

                {{-- Detailed payment history --}}
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th style="width: 130px;">Amount (₱)</th>
                                <th style="width: 150px;">Balance After (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Filled dynamically by JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Student Modal (re-usable partial) --}}
@include('auth.admindashboard.partials.edit-student-modal', [
    'gradelvls'     => $gradelvls ?? collect(),
    'tuitions'      => $tuitions  ?? collect(),
    'optionalFees'  => $optionalFees ?? collect(),
])
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let studentTables = [];

        // Keep grade cards visibility in sync with current filters
        function updateCardVisibility() {
            const gradeSel    = document.getElementById('filterGrade').value;
            const paySel      = document.getElementById('filterPay').value;
            const guardianSel = document.getElementById('filterGuardian').value;
            const q           = document.getElementById('studentSearch').value;

            studentTables.forEach(({ dt, $card }) => {
                const matchesGrade = !gradeSel || $card.dataset.grade === gradeSel;
                if (!matchesGrade) {
                    $card.style.display = 'none';
                    return;
                }
                const anyRowFilter = !!(paySel || guardianSel || q);
                const hasRows = dt.rows({ filter: 'applied' }).any();
                $card.style.display = (anyRowFilter && !hasRows) ? 'none' : '';
            });
        }

        function applyGlobalSearch() {
            const q = document.getElementById('studentSearch').value;
            studentTables.forEach(({ dt }) => dt.search(q).draw());
            updateCardVisibility();
        }

        function applyGradeFilter() {
            updateCardVisibility();
        }

        function applyPayFilter() {
            const pay = document.getElementById('filterPay').value;
            const PAY_COL_INDEX = 6; // hidden PayStatus column
            studentTables.forEach(({ dt }) => {
                if (!pay) {
                    dt.column(PAY_COL_INDEX).search('').draw();
                } else {
                    dt.column(PAY_COL_INDEX).search('^' + pay + '$', true, false).draw();
                }
            });
            updateCardVisibility();
        }

        function applyGuardianFilter() {
            const gid = document.getElementById('filterGuardian').value;
            const GUARD_COL_INDEX = 7; // hidden GuardianId column
            studentTables.forEach(({ dt }) => {
                if (!gid) {
                    dt.column(GUARD_COL_INDEX).search('').draw();
                } else {
                    dt.column(GUARD_COL_INDEX).search('^' + gid + '$', true, false).draw();
                }
            });
            updateCardVisibility();
        }

        // Delete confirm
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.delete-btn');
            if (!btn) return;
            const form = btn.closest('form');
            Swal.fire({
                title: 'Are you sure to delete this student record?',
                text: "You can't undo this action.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, proceed',
                reverseButtons: true,
                background: '#fff',
                backdrop: false,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });

        // Payment report modal handler (read-only)
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-view-payments');
            if (!btn) return;

            const modalEl = document.getElementById('paymentReportModal');
            const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);

            const studentNameEl = modalEl.querySelector('.payment-student-name');
            const gradeEl       = modalEl.querySelector('.payment-grade');
            const totalEl       = modalEl.querySelector('.payment-total');
            const paidEl        = modalEl.querySelector('.payment-paid');
            const balanceEl     = modalEl.querySelector('.payment-balance');
            const tbody         = modalEl.querySelector('tbody');

            const student = btn.getAttribute('data-student') || '';
            const grade   = btn.getAttribute('data-grade') || '';
            const total   = parseFloat(btn.getAttribute('data-total') || '0') || 0;
            const paid    = parseFloat(btn.getAttribute('data-paid') || '0') || 0;
            const balance = parseFloat(btn.getAttribute('data-balance') || '0') || 0;

            studentNameEl.textContent = student;
            gradeEl.textContent       = grade || '—';

            const fmt = (n) => n.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            totalEl.textContent   = fmt(total);
            paidEl.textContent    = fmt(paid);
            balanceEl.textContent = fmt(balance);

            tbody.innerHTML = '';

            let payments = [];
            try {
                payments = JSON.parse(btn.getAttribute('data-payments') || '[]');
            } catch (_) {
                payments = [];
            }

            if (!payments.length) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 5;
                td.className = 'text-center text-muted';
                td.textContent = 'No payment records yet.';
                tr.appendChild(td);
                tbody.appendChild(tr);
            } else {
                payments.forEach(p => {
                    const tr = document.createElement('tr');

                    const dateTd = document.createElement('td');
                    dateTd.textContent = p.date || '';
                    tr.appendChild(dateTd);

                    const methodTd = document.createElement('td');
                    methodTd.textContent = p.method || '';
                    tr.appendChild(methodTd);

                    const statusTd = document.createElement('td');
                    statusTd.textContent = p.status || '';
                    tr.appendChild(statusTd);

                    const amountTd = document.createElement('td');
                    const amt = typeof p.amount === 'number' ? p.amount : parseFloat(p.amount || '0') || 0;
                    amountTd.textContent = fmt(amt);
                    tr.appendChild(amountTd);

                    const balTd = document.createElement('td');
                    const bal = typeof p.balance === 'number' ? p.balance : parseFloat(p.balance || '0') || 0;
                    balTd.textContent = fmt(bal);
                    tr.appendChild(balTd);

                    tbody.appendChild(tr);
                });
            }

            modal.show();
        });

        $(function () {
            $('.student-table').each(function () {
                const $table = $(this);
                const dt = $table.DataTable({
                    dom: 'lrtip',
                    pageLength: 5,
                    lengthMenu: [[5,10,25,50,-1],[5,10,25,50,'All']],
                    order: [],
                    language: { emptyTable: "No students in this grade." },
                    columnDefs: [
                        { targets: -1, orderable: false },                // Tools
                        { targets: [6,7], visible: false, searchable: true } // PayStatus + GuardianId
                    ]
                });

                studentTables.push({ dt, $card: $table.closest('.grade-card')[0] });
            });

            document.getElementById('studentSearch').addEventListener('input', applyGlobalSearch);
            document.getElementById('filterGrade').addEventListener('change', applyGradeFilter);
            document.getElementById('filterPay').addEventListener('change', applyPayFilter);
            document.getElementById('filterGuardian').addEventListener('change', applyGuardianFilter);

            // School year filter → reload page with ?schoolyr_id=...
            const sySelect = document.getElementById('filterSchoolYear');
            if (sySelect) {
                sySelect.addEventListener('change', function () {
                    const val = this.value;
                    const url = new URL(window.location.href);
                    if (val) {
                        url.searchParams.set('schoolyr_id', val);
                    } else {
                        // "All" → remove param; controller falls back to active/current year
                        url.searchParams.delete('schoolyr_id');
                    }
                    window.location.href = url.toString();
                });
            }

            applyGlobalSearch();
            applyPayFilter();
            applyGuardianFilter();
            applyGradeFilter();
        });
    </script>
@endpush
