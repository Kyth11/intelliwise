@extends('layouts.admin')

@section('title', 'Students by Grade Level')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .students-page .section-title { display:flex; gap:.75rem; align-items:baseline; }
        .students-page .search-wrap { display:flex; gap:.5rem; align-items:center; }
        .students-page .search-wrap input { min-width:260px; }
        .students-page .grade-card { border:1px solid rgba(0,0,0,.075); box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .students-page .table thead th { position:sticky; top:0; background:var(--bs-body-bg); z-index:1; }
        .students-page .table td, .students-page .table th { vertical-align: middle; }
        .students-page .badge-grade { font-size:.825rem; }
        .opt-fees-cell { max-width: 420px; }
        .opt-fees-list { margin:0; padding-left: 1rem; }
        .opt-fees-list li { line-height: 1.25rem; }
    </style>
@endpush

@section('content')
<div class="card section p-4 students-page">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="section-title">
            <h4 class="mb-1">Students (Grouped by Grade Level)</h4>
            <span class="text-muted">Browse, edit, or archive students.</span>
        </div>

        <div class="search-wrap">
            <input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Search students...">
        </div>
    </div>

    @php
        $tuitionMap = collect($tuitions ?? collect())->keyBy('grade_level');
    @endphp

    @forelse($students as $grade => $group)
        <div class="card mt-3 p-3 grade-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">
                    <span class="badge bg-light text-dark border badge-grade">{{ $grade ?: '— No Grade —' }}</span>
                    <span class="text-muted">({{ $group->count() }})</span>
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle student-table">
                    <thead class="table-primary">
                        <tr>
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Parents / Guardian</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Tuition (Base, ₱)</th>
                            <th class="opt-fees-cell">Optional Fees (selected)</th>
                            <th>Optional (₱)</th>
                            <th>Total Due (₱)</th>
                            <th>Paid (₱)</th>
                            <th>Balance (₱)</th>
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

                                // current balance stored (s_total_due), fallback to original if null
                                $currentBalance = (float) ($s->s_total_due ?? $originalTotal);

                                // paid so far (for display)
                                $paid = max($originalTotal - $currentBalance, 0);

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

                                $feeIdsCsv = $filtered->pluck('id')->implode(',');
                            @endphp
                            <tr data-id="{{ $s->id }}">
                                <td>{{ $s->s_firstname }} {{ $s->s_middlename }} {{ $s->s_lastname }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') }}</td>
                                <td>{{ $household }}</td>
                                <td>{{ $s->s_contact ?? '—' }}</td>
                                <td>{{ $s->s_email ?? '—' }}</td>
                                <td>{{ number_format($base, 2) }}</td>

                                {{-- Optional Fees (list) --}}
                                <td class="opt-fees-cell">{!! $optListHtml !!}</td>

                                {{-- Optional Sum --}}
                                <td>{{ number_format($opt, 2) }}</td>

                                {{-- Original total (base + optionals) --}}
                                <td class="fw-semibold">{{ number_format($originalTotal, 2) }}</td>

                                {{-- Paid so far --}}
                                <td class="text-success fw-semibold">{{ number_format($paid, 2) }}</td>

                                {{-- Current balance --}}
                                <td class="text-danger fw-semibold">{{ number_format($currentBalance, 2) }}</td>

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

                                    <form action="{{ route('students.destroy', $s->id) }}" method="POST"
                                          class="d-inline delete-form">
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
        <div class="card mt-3 p-3">
            <p class="text-muted mb-0">No students found.</p>
        </div>
    @endforelse
</div>

{{-- Edit modal needs grade levels, tuitions, and optional fees --}}
@include('auth.admindashboard.partials.edit-student-modal', [
    'gradelvls'     => $gradelvls ?? collect(),
    'tuitions'      => $tuitions  ?? collect(),
    'optionalFees'  => $optionalFees ?? collect(),
])

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Global search across ALL grade-level DataTables (and hide empty cards)
    let studentTables = [];
    function attachGlobalFilter() {
        const q = document.getElementById('studentSearch').value;
        studentTables.forEach(function (pair) {
            const { dt, $card } = pair;
            dt.search(q).draw();
            const hasRows = dt.rows({ filter: 'applied' }).any();
            if (q && !hasRows) {
                $card.style.display = 'none';
            } else {
                $card.style.display = '';
            }
        });
    }

    document.getElementById('studentSearch').addEventListener('input', attachGlobalFilter);

    // Archive confirm
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
                        { targets: -1, orderable: false }
                    ]
                });

                studentTables.push({
                    dt: dt,
                    $card: $table.closest('.grade-card')[0]
                });
            });

            attachGlobalFilter();
        });
    </script>
@endpush
