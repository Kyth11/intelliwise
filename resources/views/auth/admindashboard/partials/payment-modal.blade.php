{{-- resources/views/auth/admindashboard/partials/payment-modal.blade.php --}}
@php
    use Illuminate\Support\Arr;

    $tuitions  = $tuitions  ?? collect();
    $schoolyrs = $schoolyrs ?? collect();

    // Determine active school year (by "active" flag)
    $activeSy   = optional($schoolyrs->firstWhere('active', true));
    $activeSyId = $activeSy->id ?? null;
    $activeSyYr = $activeSy->school_year ?? null;

    // Limit tuition map to active S.Y. if available
    $tuitionMap = $activeSyYr
        ? $tuitions->where('school_year', $activeSyYr)->keyBy('grade_level')
        : $tuitions->keyBy('grade_level');
@endphp

<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="paymentForm" method="POST" action="{{ route('admin.payments.store') }}" novalidate>
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Pay Student Balance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          {{-- Guardian Selection --}}
          <div class="mb-3">
            <label class="form-label">Select Guardian</label>
            <select name="guardian_id" id="paymentGuardianSelect" class="form-select" required>
              <option value="">— Select Guardian —</option>

              @foreach($guardians as $guardian)
                @php
                    // Build student payload per guardian using grade-level tuition and active S.Y.
                    $studentsPayload = $guardian->students
                        // keep only students in active school year (if defined)
                        ->when($activeSyId, function ($q) use ($activeSyId) {
                            return $q->where('schoolyr_id', $activeSyId);
                        })
                        ->map(function ($s) use ($tuitionMap) {
                            // Grade-level tuition row for this student's grade
                            $row = $tuitionMap->get($s->s_gradelvl);

                            $regFee = $row ? (float) ($row->registration_fee ?? 0) : 0.0;

                            // Base yearly from tuition record or student override
                            if ($row) {
                                $base = (float) $row->total_yearly;
                            } elseif ($s->s_tuition_sum !== null && $s->s_tuition_sum !== '') {
                                $base = (float) $s->s_tuition_sum;
                            } else {
                                $base = 0.0;
                            }

                            // Optional fees for student (scope + active)
                            $optCollection = collect($s->optionalFees ?? []);
                            $filteredOpt = $optCollection->filter(function ($f) {
                                $scopeOk  = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
                                $activeOk = !property_exists($f, 'active') || (bool) $f->active;
                                return $scopeOk && $activeOk;
                            });

                            $opt = (float) $filteredOpt->sum(function ($f) {
                                $amt = $f->pivot->amount_override ?? $f->amount;
                                return (float) $amt;
                            });

                            $originalTotal = $base + $opt;

                            // Payments recorded so far
                            $paidRecords = (float) ($s->payments()->sum('amount') ?? 0);

                            // Current balance logic aligned with students.blade.php
                            if ($s->s_total_due !== null && $s->s_total_due !== '') {
                                $currentBalance = max(0.0, (float) $s->s_total_due);
                                $paid = max($originalTotal - $currentBalance, 0.0);
                            } else {
                                $paid          = min($paidRecords, $originalTotal);
                                $currentBalance = max(0.0, round($originalTotal - $paid, 2));
                            }

                            // Use LRN as ID for consistency across pages
                            $lrn  = $s->lrn;
                            $name = trim(Arr::join(array_filter([$s->s_firstname, $s->s_lastname]), ' '));

                            return [
                                'id'            => $lrn,                       // LRN
                                'name'          => $name,
                                'grade_level'   => $s->s_gradelvl,
                                'total_due'     => number_format($currentBalance, 2, '.', ''),
                                'original_total'=> number_format($originalTotal, 2, '.', ''),
                                'paid'          => number_format($paid, 2, '.', ''),
                            ];
                        })
                        ->filter(function ($payload) {
                            // drop students with no identifier
                            return !empty($payload['id']);
                        })
                        ->values();
                @endphp

                @if($studentsPayload->isNotEmpty())
                    <option value="{{ $guardian->id }}" data-students='@json($studentsPayload)'>
                      {{ $guardian->display_name }}
                    </option>
                @endif
              @endforeach
            </select>
          </div>

          {{-- Student Selection --}}
          <div class="mb-3" id="studentSelectWrapper" style="display:none;">
            <label class="form-label">Select Student</label>
            <select name="student_lrn" id="paymentStudentSelect" class="form-select" required>
              <option value="">— Select Student —</option>
            </select>
          </div>

          {{-- Single Student Display --}}
          <div class="mb-3" id="singleStudentName" style="display:none;">
            <label class="form-label">Student</label>
            <input type="hidden" name="student_lrn" id="singleStudentId">
            <input type="text" id="singleStudentDisplay" class="form-control" readonly>
          </div>

          {{-- Current Balance --}}
          <div class="mb-3" id="studentPayables" style="display:none;">
            <label class="form-label">Current Balance</label>
            <input type="text" id="currentBalanceDisplay" class="form-control" readonly>
            <input type="hidden" name="current_balance" id="currentBalance">
          </div>

          {{-- Amount --}}
          <div class="mb-3">
            <label class="form-label">Amount to Pay</label>
            <input type="number" step="0.01" min="0" name="amount" id="paymentAmount" class="form-control" required>
          </div>

          {{-- Payment Method --}}
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="paymentMethod" class="form-select" required>
              <option value="Cash">Cash</option>
              <option value="G-cash">G-cash</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" id="payBtn" class="btn btn-primary">Pay</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const guardianSelect        = document.getElementById('paymentGuardianSelect');
  const studentSelectWrapper  = document.getElementById('studentSelectWrapper');
  const studentSelect         = document.getElementById('paymentStudentSelect');
  const singleStudentName     = document.getElementById('singleStudentName');
  const singleStudentId       = document.getElementById('singleStudentId');
  const singleStudentDisplay  = document.getElementById('singleStudentDisplay');
  const studentPayables       = document.getElementById('studentPayables');
  const currentBalance        = document.getElementById('currentBalance');
  const currentBalanceDisplay = document.getElementById('currentBalanceDisplay');
  const paymentAmount         = document.getElementById('paymentAmount');
  const paymentMethod         = document.getElementById('paymentMethod');
  const payForm               = document.getElementById('paymentForm');

  // Bootstrap 5 modal instance
  const modalEl      = document.getElementById('addPaymentModal');
  const paymentModal = bootstrap.Modal.getOrCreateInstance(modalEl);

  modalEl.addEventListener('hidden.bs.modal', () => {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  });

  function resetStudentUI() {
    studentPayables.style.display       = 'none';
    singleStudentName.style.display     = 'none';
    studentSelectWrapper.style.display  = 'none';
    currentBalance.value                = '';
    currentBalanceDisplay.value         = '';
    singleStudentId.value               = '';
    singleStudentDisplay.value          = '';
    studentSelect.innerHTML             = '<option value="">— Select Student —</option>';
  }

  function showStudentData(student) {
    if (!student) {
      resetStudentUI();
      return;
    }

    // Multiple students under guardian
    if (Array.isArray(student.students) && student.students.length > 1) {
      studentSelectWrapper.style.display = '';
      singleStudentName.style.display    = 'none';
      studentSelect.innerHTML            = '<option value="">— Select Student —</option>';

      student.students.forEach(s => {
        const opt       = document.createElement('option');
        opt.value       = s.id;             // LRN
        opt.dataset.totalDue = s.total_due; // numeric string
        opt.textContent = s.name + (s.grade_level ? ' (' + s.grade_level + ')' : '');
        studentSelect.appendChild(opt);
      });

      studentPayables.style.display = 'none';
    } else {
      // Single student object
      const bal = parseFloat(student.total_due || '0') || 0;
      currentBalance.value        = bal;
      currentBalanceDisplay.value = bal.toFixed(2);
      singleStudentName.style.display    = '';
      studentSelectWrapper.style.display = 'none';
      singleStudentId.value              = student.id;   // LRN
      singleStudentDisplay.value         = student.name + (student.grade_level ? ' (' + student.grade_level + ')' : '');
      studentPayables.style.display      = '';
    }
  }

  guardianSelect.addEventListener('change', function () {
    const sel      = guardianSelect.options[guardianSelect.selectedIndex];
    const students = sel && sel.dataset.students ? JSON.parse(sel.dataset.students) : [];

    resetStudentUI();

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
    if (!sel.value) {
      studentPayables.style.display = 'none';
      currentBalance.value          = '';
      currentBalanceDisplay.value   = '';
      return;
    }

    const bal = parseFloat(sel.dataset.totalDue || '0') || 0;
    currentBalance.value        = bal;
    currentBalanceDisplay.value = bal.toFixed(2);
    singleStudentId.value       = sel.value;       // LRN
    singleStudentDisplay.value  = sel.textContent;
    studentPayables.style.display = '';
  });

  payForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const studentLrn = singleStudentId.value || studentSelect.value;
    const amount     = parseFloat(paymentAmount.value);
    const method     = paymentMethod.value;
    const balance    = parseFloat(currentBalance.value);

    if (!studentLrn) {
      Swal.fire('Invalid Payment', 'Please select a student.', 'error');
      return;
    }
    if (!amount || amount <= 0 || isNaN(amount)) {
      Swal.fire('Invalid Payment', 'Please enter a valid amount.', 'error');
      return;
    }
    if (isNaN(balance)) {
      Swal.fire('Invalid Payment', 'No current balance is available for this student.', 'error');
      return;
    }
    if (amount > balance + 0.01) {
      Swal.fire('Invalid Payment', 'Amount cannot be greater than current balance.', 'error');
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

      fetch("{{ route('admin.payments.store') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          student_lrn:     studentLrn,
          amount:          amount,
          payment_method:  method,
          current_balance: balance
        })
      })
      .then(async res => {
        // Try JSON; if not JSON (redirect), reload the page after closing modal
        try {
          const data = await res.json();
          return { ok: res.ok, data };
        } catch {
          return { ok: res.ok, reload: true };
        }
      })
      .then(({ ok, data, reload }) => {
        if (reload) {
          paymentModal.hide();
          window.location.reload();
          return;
        }

        if (!ok || !data?.success) {
          Swal.fire('Error', (data && data.message) || 'Payment failed', 'error');
          return;
        }

        paymentModal.hide();
        Swal.fire('Payment Saved', 'Student balance updated.', 'success');

        // Optional UI patch-up: update a row on any page that uses <tr data-lrn="LRN">
        const row = document.querySelector(`tr[data-lrn="${studentLrn}"]`);
        if (row) {
          const paidCell    = row.querySelector('.text-success');
          const balanceCell = row.querySelector('.text-danger');

          const prevPaid = parseFloat((paidCell && paidCell.textContent || '0').replace(/,/g,'')) || 0;
          const newPaid  = prevPaid + amount;
          const newBal   = typeof data.new_balance !== 'undefined'
              ? parseFloat(data.new_balance)
              : Math.max(balance - amount, 0);

          if (paidCell) {
            paidCell.textContent = newPaid.toFixed(2);
          }
          if (balanceCell) {
            balanceCell.textContent = newBal.toFixed(2);
          }
        }

        payForm.reset();
        resetStudentUI();
      })
      .catch(() => Swal.fire('Error', 'Server error.', 'error'));
    });
  });
});
</script>
