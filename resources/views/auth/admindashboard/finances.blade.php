@extends('layouts.admin')

@section('title', 'Finances')

@push('styles')
    {{-- Link your consolidated CSS --}}
    <link rel="stylesheet" href="{{ asset('css/app-dashboard.css') }}">
@endpush

@section('content')
@php
    $tuitions     = $tuitions     ?? collect();
    $optionalFees = $optionalFees ?? collect();
    $schoolyrs    = $schoolyrs    ?? collect();

    $latestUpdatedAt = optional($tuitions->sortByDesc('updated_at')->first())->updated_at?->format('Y-m-d') ?? '—';
@endphp

<div class="card section p-4">
    {{-- =========================
         Header: Intro | KPIs | Quick Actions
    ========================== --}}
    <div id="dashboard-header" class="mb-3">

        {{-- Intro --}}
        <div class="intro rounded">
            <div>
                <h5 class="mb-1">Finances</h5>
                <div class="text-muted small">Tuition, optional fees, and payments overview.</div>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-number">{{ $tuitions->count() }}</div>
                <div class="kpi-label">Tuition per Grade</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ $optionalFees->count() }}</div>
                <div class="kpi-label">Optional Fees</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ $schoolyrs->count() }}</div>
                <div class="kpi-label">School Years</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ $latestUpdatedAt }}</div>
                <div class="kpi-label">Last Tuition Update</div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="pay-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Quick Actions</h6>
                <a href="{{ route('admin.settings') }}" class="btn btn-sm btn-outline-secondary" title="Settings">
                    <i class="bi bi-gear"></i>
                </a>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="bi bi-cash-coin me-1"></i> Pay Student Balance
                </button>
            </div>
        </div>
    </div>

    {{-- ======================
         TUITION TABLE
    ======================= --}}
    <div class="card p-4 mb-4" id="tuition-section">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Tuition (per Grade Level)</h5>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="tuitionSearch" class="form-control form-control-sm search-inline"
                       placeholder="Search tuition...">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTuitionModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Tuition
                </button>
            </div>
        </div>

        @if($tuitions->isEmpty())
            <p class="text-muted">No tuition fees set yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tuitionTable">
                    <thead class="table-light">
                        <tr>
                            <th>Grade Level</th>
                            <th>Tuition (Monthly)</th>
                            <th>Tuition (Yearly)</th>
                            <th>Misc (Monthly)</th>
                            <th>Misc (Yearly)</th>
                            <th>Books (₱)</th>
                            <th>Total (₱)</th>
                            <th>School Year</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($tuitions as $t)
                        <tr>
                            <td>{{ $t->grade_level }}</td>
                            <td>{{ number_format((float) $t->tuition_monthly, 2) }}</td>
                            <td>{{ number_format((float) $t->tuition_yearly, 2) }}</td>
                            <td>{{ $t->misc_monthly === null ? '—' : number_format((float) $t->misc_monthly, 2) }}</td>
                            <td>{{ $t->misc_yearly === null ? '—' : number_format((float) $t->misc_yearly, 2) }}</td>
                            <td>{{ $t->books_amount === null ? '—' : number_format((float) $t->books_amount, 2) }}</td>
                            <td class="fw-semibold">{{ number_format((float) $t->total_yearly, 2) }}</td>
                            <td>{{ $t->school_year ?? '—' }}</td>
                            <td>{{ $t->updated_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editTuitionModal{{ $t->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form action="{{ route('tuitions.destroy', $t->id) }}" method="POST"
                                      class="d-inline js-confirm-delete" data-confirm="Delete this tuition record?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger js-delete-btn" aria-label="Delete tuition">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ======================
         OPTIONAL FEES TABLE
    ======================= --}}
    <div class="card p-4" id="optional-fees-section">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Optional Fees (Master List)</h5>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="feesSearch" class="form-control form-control-sm search-inline"
                       placeholder="Search fees...">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addOptionalFeeModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Fee
                </button>
            </div>
        </div>

        @if($optionalFees->isEmpty())
            <p class="text-muted mb-0">No optional fees set yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle" id="feesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 360px;">Name</th>
                            <th>Amount (₱)</th>
                            <th class="text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($optionalFees as $fee)
                        @php $formId = "fee-form-{$fee->id}"; @endphp
                        <tr>
                            <td>
                                <input type="text" name="name" class="form-control form-control-sm"
                                       value="{{ $fee->name }}" required form="{{ $formId }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm"
                                       value="{{ number_format($fee->amount, 2, '.', '') }}" required form="{{ $formId }}">
                            </td>
                            <td class="text-nowrap">
                                <form id="{{ $formId }}" action="{{ route('optionalfees.update', $fee->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('PUT')
                                    <button class="btn btn-warning btn-sm" type="submit">
                                        <i class="bi bi-save me-1"></i> Save
                                    </button>
                                </form>
                                <form action="{{ route('optionalfees.destroy', $fee->id) }}" method="POST"
                                      class="d-inline js-confirm-delete" data-confirm="Delete this optional fee?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm js-delete-btn">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ADD TUITION MODAL --}}
    <div class="modal fade" id="addTuitionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tuitions.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Tuition</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Grade --}}
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select name="grade_level" class="form-select" required>
                                <option value="">— Select Grade Level —</option>
                                <option value="Nursery">Nursery</option>
                                <option value="Kindergarten 1">Kindergarten 1</option>
                                <option value="Kindergarten 2">Kindergarten 2</option>
                                <option value="Grade 1">Grade 1</option>
                                <option value="Grade 2">Grade 2</option>
                                <option value="Grade 3">Grade 3</option>
                                <option value="Grade 4">Grade 4</option>
                                <option value="Grade 5">Grade 5</option>
                                <option value="Grade 6">Grade 6</option>
                            </select>
                        </div>

                        {{-- Tuition (monthly/yearly interchangeable) --}}
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Tuition (Monthly) ₱</label>
                                <input type="number" step="0.01" min="0" name="tuition_monthly" id="add_tuition_monthly"
                                       class="form-control">
                                <div class="form-text">Typing here auto-fills Yearly (×10).</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tuition (Yearly) ₱</label>
                                <input type="number" step="0.01" min="0" name="tuition_yearly" id="add_tuition_yearly"
                                       class="form-control">
                                <div class="form-text">Typing here auto-fills Monthly (÷10).</div>
                            </div>
                        </div>

                        {{-- Misc (monthly/yearly interchangeable) --}}
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <label class="form-label">Misc (Monthly) ₱ (optional)</label>
                                <input type="number" step="0.01" min="0" name="misc_monthly" id="add_misc_monthly"
                                       class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Misc (Yearly) ₱ (optional)</label>
                                <input type="number" step="0.01" min="0" name="misc_yearly" id="add_misc_yearly"
                                       class="form-control">
                            </div>
                        </div>

                        {{-- Books --}}
                        <div class="row g-2 mt-2">
                            <div class="col-5">
                                <label class="form-label">Books Amount ₱ — optional</label>
                                <input type="number" step="0.01" min="0" name="books_amount" id="add_books_amount"
                                       class="form-control">
                            </div>
                        </div>

                        {{-- School Year --}}
                        <div class="mb-3 mt-3">
                            <label class="form-label">School Year (optional)</label>
                            <select name="school_year" class="form-select">
                                <option value="">— None —</option>
                                @foreach($schoolyrs as $sy)
                                    <option value="{{ $sy->school_year }}">{{ $sy->school_year }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Computed preview total --}}
                        <div class="mb-2">
                            <label class="form-label">Computed Total (preview) ₱</label>
                            <input type="text" id="add_total_preview" class="form-control" readonly>
                            <div class="form-text">= Tuition Yearly + Misc Yearly + Books</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ADD OPTIONAL FEE MODAL --}}
    <div class="modal fade" id="addOptionalFeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('optionalfees.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Optional Fee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., ID / Insurance" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Amount (₱)</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Add Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- PAYMENT MODAL --}}
    @include('auth.admindashboard.partials.payment-modal')

    {{-- SHARED: ADD SCHEDULE MODAL (self-safe) --}}
    @include('auth.admindashboard.partials.add-schedule-modal')
</div>

{{-- EDIT TUITION MODALS --}}
@foreach($tuitions as $t)
    @include('auth.admindashboard.partials.edit-tuition-modal', [
        't' => $t,
        'schoolyrs' => $schoolyrs,
        'optionalFees' => $optionalFees
    ])
@endforeach
@endsection

@push('scripts')
    {{-- jQuery + DataTables + Bootstrap 5 adapter --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Delete confirms (tuition + fees)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-delete-btn');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form.js-confirm-delete');
            const msg  = form?.dataset?.confirm || "Delete this record?";
            Swal.fire({
                title: 'Are you sure?',
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
            }).then(res => { if (res.isConfirmed) form.submit(); });
        });
    </script>

    <script>
        // DataTables for Tuition & Fees tables
        $(function () {
            const tuitionDT = $('#tuitionTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                language: { emptyTable: "No tuition records found." },
                columnDefs: [{ targets: -1, orderable: false }]
            });
            $('#tuitionSearch').on('input', function () { tuitionDT.search(this.value).draw(); });

            const feesDT = $('#feesTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                language: { emptyTable: "No optional fees found." },
                columnDefs: [{ targets: -1, orderable: false }]
            });
            $('#feesSearch').on('input', function () { feesDT.search(this.value).draw(); });
        });
    </script>

    <script>
        // Interlinked Monthly/Yearly + total preview in Add Tuition modal
        document.addEventListener('DOMContentLoaded', function () {
            const MONTHS = 10;
            const tMon = document.getElementById('add_tuition_monthly');
            const tYear = document.getElementById('add_tuition_yearly');
            const mMon = document.getElementById('add_misc_monthly');
            const mYear = document.getElementById('add_misc_yearly');
            const books = document.getElementById('add_books_amount');
            const preview = document.getElementById('add_total_preview');

            function n(v) { const x = parseFloat(v); return isNaN(x) ? 0 : x; }
            let lock = false;

            function fromTMon()  { if (lock) return; lock = true; tYear.value = (n(tMon.value) * MONTHS).toFixed(2); calc(); lock = false; }
            function fromTYear() { if (lock) return; lock = true; tMon.value  = (n(tYear.value) / MONTHS).toFixed(2); calc(); lock = false; }
            function fromMMon()  { if (lock) return; lock = true; mYear.value = (n(mMon.value) * MONTHS).toFixed(2); calc(); lock = false; }
            function fromMYear() { if (lock) return; lock = true; mMon.value  = (n(mYear.value) / MONTHS).toFixed(2); calc(); lock = false; }

            function calc() {
                const ty = n(tYear.value);
                const my = n(mYear.value);
                const b  = n(books.value);
                preview.value = (ty + my + b).toFixed(2);
            }

            tMon?.addEventListener('input', fromTMon);
            tYear?.addEventListener('input', fromTYear);
            mMon?.addEventListener('input', fromMMon);
            mYear?.addEventListener('input', fromMYear);
            books?.addEventListener('input', calc);
        });
    </script>
@endpush
