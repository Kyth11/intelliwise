@extends('layouts.admin')

@section('title', 'Finances')

@push('styles')
    {{-- DataTables + Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

    <div class="card section p-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Finances</h4>
            <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                <i class="bi bi-cash-coin me-1"></i> Pay Student Balance
            </button>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTuitionModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Tuition
                    </button>
                </div>
            </div>




            @if($tuitions->isEmpty())
                <p class="text-muted">No tuition fees set yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tuitionTable" style="size ">
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
                                @php $gradeOptional = $t->optionalFees->sum('amount'); @endphp
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
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn"
                                                aria-label="Delete tuition">
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

            @if(($optionalFees ?? collect())->isEmpty())
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
                                    {{-- NAME (input targets the row form via "form" attr) --}}
                                    <td>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $fee->name }}"
                                            required form="{{ $formId }}">
                                    </td>

                                    {{-- AMOUNT --}}
                                    <td>
                                        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm"
                                            value="{{ number_format($fee->amount, 2, '.', '') }}" required form="{{ $formId }}">
                                    </td>


                                    {{-- ACTIONS: single SAVE form + DELETE --}}
                                    <td class="text-nowrap">
                                        <form id="{{ $formId }}" action="{{ route('optionalfees.update', $fee->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-warning btn-sm" type="submit">
                                                <i class="bi bi-save me-1"></i> Save
                                            </button>
                                        </form>

                                        <form action="{{ route('optionalfees.destroy', $fee->id) }}" method="POST"
                                            class="d-inline js-confirm-delete" data-confirm="Delete this optional fee?">
                                            @csrf
                                            @method('DELETE')
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

        {{-- ADD TUITION MODAL (no optional-fee picker here) --}}
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

                            {{-- Computed preview total (no optional-fees here) --}}
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
                                <input type="text" name="name" class="form-control" placeholder="e.g., ID / Insurance"
                                    required>
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
        // Single, unified delete confirm to match your global design
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form.delete-form');
                if (!form) return;

                const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
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
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Payment modal AJAX logic
        const guardianSelect       = document.getElementById('paymentGuardianSelect');
        const studentSelectWrapper = document.getElementById('studentSelectWrapper');
        const studentSelect        = document.getElementById('paymentStudentSelect');
        const singleStudentName    = document.getElementById('singleStudentName');
        const singleStudentId      = document.getElementById('singleStudentId');
        const singleStudentDisplay = document.getElementById('singleStudentDisplay');
        const studentPayables      = document.getElementById('studentPayables');
        const currentBalance       = document.getElementById('currentBalance');
        const currentBalanceDisplay= document.getElementById('currentBalanceDisplay');
        const paymentAmount        = document.getElementById('paymentAmount');
        const paymentMethod        = document.getElementById('paymentMethod');
        const payForm              = document.getElementById('paymentForm');

        function showStudentData(student) {
            if (!student) {
                studentPayables.style.display = 'none';
                singleStudentName.style.display = 'none';
                studentSelectWrapper.style.display = 'none';
                currentBalance.value = '';
                currentBalanceDisplay.value = '';
                singleStudentId.value = '';
                singleStudentDisplay.value = '';
                return;
            }

            currentBalance.value = parseFloat(student.total_due);
            currentBalanceDisplay.value = parseFloat(student.total_due).toFixed(2);

            if (Array.isArray(student.students) && student.students.length > 1) {
                studentSelectWrapper.style.display = '';
                singleStudentName.style.display = 'none';
                studentSelect.innerHTML = '<option value="">— Select Student —</option>';
                student.students.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.dataset.totalDue = s.total_due;
                    opt.textContent = s.name;
                    studentSelect.appendChild(opt);
                });
            } else {
                singleStudentName.style.display = '';
                studentSelectWrapper.style.display = 'none';
                singleStudentId.value = student.id;
                singleStudentDisplay.value = student.name;
            }

            studentPayables.style.display = '';
        }

        guardianSelect.addEventListener('change', function () {
            const sel = guardianSelect.options[guardianSelect.selectedIndex];
            const students = sel && sel.dataset.students ? JSON.parse(sel.dataset.students) : [];
            if (students.length === 1) {
                showStudentData(students[0]);
            } else if (students.length > 1) {
                showStudentData({ students });
            } else {
                showStudentData(null);
            }
        });

        studentSelect.addEventListener('change', function () {
            const sel = studentSelect.options[studentSelect.selectedIndex];
            if (!sel.value) return;
            currentBalance.value = parseFloat(sel.dataset.totalDue);
            currentBalanceDisplay.value = parseFloat(sel.dataset.totalDue).toFixed(2);
            singleStudentId.value = sel.value;
            singleStudentDisplay.value = sel.textContent;
        });

        payForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const studentId = singleStudentId.value || studentSelect.value;
            const amount    = parseFloat(paymentAmount.value);
            const method    = paymentMethod.value;
            const balance   = parseFloat(currentBalance.value);

            if (!studentId || !amount || amount <= 0 || amount > balance) {
                Swal.fire('Invalid Payment', 'Please check student and amount', 'error');
                return;
            }

            Swal.fire({
                title: 'Confirm Payment?',
                html: `<p>Amount: ₱${amount.toFixed(2)}</p>
                       <p>Current Balance: ₱${balance.toFixed(2)}</p>
                       <p>New Balance: ₱${Math.max(balance - amount,0).toFixed(2)}</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Pay',
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch("{{ route('payments.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        amount: amount,
                        payment_method: method,
                        current_balance: balance
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Payment Saved', 'Student balance updated', 'success');
                        // Update table Paid and Balance
                        const row = document.querySelector(`tr[data-id="${studentId}"]`);
                        if (row) {
                            const paidCell    = row.querySelector('.text-success');
                            const balanceCell = row.querySelector('.text-danger');
                            const prevPaid = parseFloat(paidCell.textContent.replace(/,/g,''));
                            paidCell.textContent = (prevPaid + amount).toFixed(2);
                            balanceCell.textContent = data.new_balance.toFixed(2);
                        }
                        payForm.reset();
                        $('#addPaymentModal').modal('hide');
                    } else {
                        Swal.fire('Error', data.message || 'Payment failed', 'error');
                    }
                }).catch(() => Swal.fire('Error', 'Server error', 'error'));
            });
        });
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

            function fromTMon() { if (lock) return; lock = true; if (tYear) tYear.value = (n(tMon.value) * MONTHS).toFixed(2); calc(); lock = false; }
            function fromTYear() { if (lock) return; lock = true; if (tMon) tMon.value = (n(tYear.value) / MONTHS).toFixed(2); calc(); lock = false; }
            function fromMMon() { if (lock) return; lock = true; if (mYear) mYear.value = (n(mMon.value) * MONTHS).toFixed(2); calc(); lock = false; }
            function fromMYear() { if (lock) return; lock = true; if (mMon) mMon.value = (n(mYear.value) / MONTHS).toFixed(2); calc(); lock = false; }

            function calc() {
                const ty = n(tYear?.value);
                const my = n(mYear?.value);
                const b = n(books?.value);
                if (preview) preview.value = (ty + my + b).toFixed(2);
            }

            tMon?.addEventListener('input', fromTMon);
            tYear?.addEventListener('input', fromTYear);
            mMon?.addEventListener('input', fromMMon);
            mYear?.addEventListener('input', fromMYear);
            books?.addEventListener('input', calc);
        });
    </script>
@endpush

@include('auth.admindashboard.partials.add-tuition-modal')
@foreach($tuitions as $t)
    @include('auth.admindashboard.partials.edit-tuition-modal', [
        't' => $t,
        'schoolyrs' => $schoolyrs,
        'optionalFees' => $optionalFees ?? collect()
    ])
@endforeach


