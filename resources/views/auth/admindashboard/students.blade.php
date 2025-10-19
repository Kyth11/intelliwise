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
    <!-- Header (kept consistent with your design language, no Quick Actions here) -->
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
        <div class="section-title">
            <h4 class="mb-0">Students (Grouped by Grade Level)</h4>
            <span class="text-muted ms-2">Browse, filter, edit, or archive students.</span>
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

    {{-- ===== Filters (kept) ===== --}}
    <form class="filters row g-2 align-items-end mt-1 mb-2">
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
                <option>Not Paid</option>
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
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Parents / Guardian</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Tuition (₱)</th>
                            <th class="opt-fees-cell">Optional Fees</th>
                            <th>Optional (₱)</th>
                            <th>Total Due (₱)</th>
                            <th>Paid (₱)</th>
                            <th>Balance (₱)</th>
                            {{-- hidden helper columns for filtering --}}
                            <th class="d-none">PayStatus</th>
                            <th class="d-none">GuardianId</th>
                            <th class="text-nowrap">Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group as $s)
                            @php
                                $row  = $tuitionMap->get($s->s_gradelvl);
                                $base = $row ? (float) $row->total_yearly
                                             : (($s->s_tuition_sum !== null && $s->s_tuition_sum !== '') ? (float) $s->s_tuition_sum : 0);

                                $optCollection = collect($s->optionalFees ?? []);
                                $filtered = $optCollection->filter(function ($f) {
                                    $scopeOk = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
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

                                // If s_total_due is stored, treat it as current balance; otherwise derive from payments
                                $paidRecords = (float) ($s->payments()->sum('amount') ?? 0);
                                if ($s->s_total_due !== null && $s->s_total_due !== '') {
                                    $currentBalance = max(0.0, (float) $s->s_total_due);
                                    $paid = max($originalTotal - $currentBalance, 0.0);
                                } else {
                                    $paid = min($paidRecords, $originalTotal);
                                    $currentBalance = max(0.0, round($originalTotal - $paid, 2));
                                }

                                $derivedPay = $currentBalance <= 0.01 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Not Paid');

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
                            @endphp

                            <tr data-id="{{ $s->id }}" data-paystatus="{{ $derivedPay }}" data-guardianid="{{ $guardianId }}">
                                <td>{{ $s->s_firstname }} {{ $s->s_middlename }} {{ $s->s_lastname }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') }}</td>
                                <td>{{ $household }}</td>
                                <td>{{ $s->s_contact ?? '—' }}</td>
                                <td>{{ $s->s_email ?? '—' }}</td>
                                <td>{{ number_format($base, 2) }}</td>
                                <td class="opt-fees-cell">{!! $optListHtml !!}</td>
                                <td>{{ number_format($opt, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($originalTotal, 2) }}</td>
                                <td class="text-success fw-semibold">{{ number_format($paid, 2) }}</td>
                                <td class="text-danger fw-semibold">{{ number_format($currentBalance, 2) }}</td>
                                {{-- hidden helper columns so DataTables can filter easily --}}
                                <td class="d-none">{{ $derivedPay }}</td>
                                <td class="d-none">{{ $guardianId }}</td>

                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editStudentModal"
                                            data-id="{{ $s->id }}"
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

                                    <form action="{{ route('admin.students.destroy', $s->id) }}" method="POST" class="d-inline delete-form">
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

{{-- Edit Student Modal (re-usable partial) --}}
@include('auth.admindashboard.partials.edit-student-modal', [
    'gradelvls'     => $gradelvls ?? collect(),
    'tuitions'      => $tuitions  ?? collect(),
    'optionalFees'  => $optionalFees ?? collect(),
])

{{-- SweetAlert2 (delete confirm) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
</script>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let studentTables = [];

        // Keep grade cards visibility in sync with current filters
        function updateCardVisibility() {
            const gradeSel    = document.getElementById('filterGrade').value;
            const paySel      = document.getElementById('filterPay').value;
            const guardianSel = document.getElementById('filterGuardian').value;
            const q           = document.getElementById('studentSearch').value;

            studentTables.forEach(({ dt, $card }) => {
                // Grade filter first
                const matchesGrade = !gradeSel || $card.dataset.grade === gradeSel;
                if (!matchesGrade) {
                    $card.style.display = 'none';
                    return;
                }
                // If any row-level filter/search is active, hide cards with no visible rows
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
            // grade cards are toggled in updateCardVisibility
            updateCardVisibility();
        }

        function applyPayFilter() {
            const pay = document.getElementById('filterPay').value;
            const PAY_COL_INDEX = 11; // hidden PayStatus column
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
            const GUARD_COL_INDEX = 12; // hidden GuardianId column
            studentTables.forEach(({ dt }) => {
                if (!gid) {
                    dt.column(GUARD_COL_INDEX).search('').draw();
                } else {
                    dt.column(GUARD_COL_INDEX).search('^' + gid + '$', true, false).draw();
                }
            });
            updateCardVisibility();
        }

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
                        { targets: -1, orderable: false },          // Tools
                        { targets: [11,12], visible: false, searchable: true } // PayStatus + GuardianId
                    ]
                });

                studentTables.push({ dt, $card: $table.closest('.grade-card')[0] });
            });

            // Wire filters
            document.getElementById('studentSearch').addEventListener('input', applyGlobalSearch);
            document.getElementById('filterGrade').addEventListener('change', applyGradeFilter);
            document.getElementById('filterPay').addEventListener('change', applyPayFilter);
            document.getElementById('filterGuardian').addEventListener('change', applyGuardianFilter);

            // Initial pass
            applyGlobalSearch();
            applyPayFilter();
            applyGuardianFilter();
            applyGradeFilter();
        });
    </script>
@endpush
