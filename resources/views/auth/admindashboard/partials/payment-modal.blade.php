{{-- resources/views/auth/admindashboard/partials/payment-modal.blade.php --}}
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
                  $studentsPayload = $guardian->students->map(function($s){
                      return [
                          'id'        => $s->id,
                          'name'      => trim(($s->s_firstname ?? '').' '.($s->s_lastname ?? '')),
                          'total_due' => number_format($s->s_total_due ?? 0,2,'.','')
                      ];
                  })->values();
                @endphp
                <option value="{{ $guardian->id }}" data-students='@json($studentsPayload)'>
                  {{ $guardian->display_name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Student Selection --}}
          <div class="mb-3" id="studentSelectWrapper" style="display:none;">
            <label class="form-label">Select Student</label>
            <select name="student_id" id="paymentStudentSelect" class="form-select" required>
              <option value="">— Select Student —</option>
            </select>
          </div>

          {{-- Single Student Display --}}
          <div class="mb-3" id="singleStudentName" style="display:none;">
            <label class="form-label">Student</label>
            <input type="hidden" name="student_id" id="singleStudentId">
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
  const modalEl = document.getElementById('addPaymentModal');
  const paymentModal = bootstrap.Modal.getOrCreateInstance(modalEl);

  modalEl.addEventListener('hidden.bs.modal', () => {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  });

  function showStudentData(student) {
    if (!student) {
      studentPayables.style.display   = 'none';
      singleStudentName.style.display = 'none';
      studentSelectWrapper.style.display = 'none';
      currentBalance.value = '';
      currentBalanceDisplay.value = '';
      singleStudentId.value = '';
      singleStudentDisplay.value = '';
      return;
    }

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
      studentPayables.style.display = 'none';
    } else {
      // single student object
      currentBalance.value = parseFloat(student.total_due);
      currentBalanceDisplay.value = parseFloat(student.total_due).toFixed(2);
      singleStudentName.style.display = '';
      studentSelectWrapper.style.display = 'none';
      singleStudentId.value = student.id;
      singleStudentDisplay.value = student.name;
      studentPayables.style.display = '';
    }
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
    if (!sel.value) {
      studentPayables.style.display = 'none';
      return;
    }
    currentBalance.value = parseFloat(sel.dataset.totalDue);
    currentBalanceDisplay.value = parseFloat(sel.dataset.totalDue).toFixed(2);
    singleStudentId.value = sel.value;
    singleStudentDisplay.value = sel.textContent;
    studentPayables.style.display = '';
  });

  payForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const studentId = singleStudentId.value || studentSelect.value;
    const amount    = parseFloat(paymentAmount.value);
    const method    = paymentMethod.value;
    const balance   = parseFloat(currentBalance.value);

    if (!studentId || !amount || amount <= 0 || isNaN(balance) || amount > balance) {
      Swal.fire('Invalid Payment', 'Please check student and balance', 'error');
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
          student_id: studentId,
          amount: amount,
          payment_method: method,
          current_balance: balance
        })
      })
      .then(async res => {
        // Try JSON; if not JSON (e.g., controller redirects), just reload.
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
        Swal.fire('Payment Saved', 'Student balance updated', 'success');

        // Optional UI patch-up (if you have a row for this student)
        const row = document.querySelector(`tr[data-id="${studentId}"]`);
        if (row) {
          const paidCell    = row.querySelector('.text-success');
          const balanceCell = row.querySelector('.text-danger');
          const prevPaid = parseFloat(paidCell?.textContent?.replace(/,/g,'') || '0') || 0;
          if (paidCell)    paidCell.textContent = (prevPaid + amount).toFixed(2);
          if (balanceCell) balanceCell.textContent = Number(data.new_balance ?? Math.max(balance-amount,0)).toFixed(2);
        }

        payForm.reset();
        showStudentData(null);
      })
      .catch(() => Swal.fire('Error', 'Server error', 'error'));
    });
  });
});
</script>
