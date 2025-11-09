{{-- resources/views/auth/admindashboard/partials/pay-student-modal.blade.php --}}
<div class="modal fade" id="payStudentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="paymentForm" method="POST" action="{{ route('admin.payments.store') }}" novalidate>
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Apply GCash Receipt</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          {{-- Hidden identifiers --}}
          <input type="hidden" name="guardian_id" id="pm_guardian_id">
          <input type="hidden" name="student_lrn"  id="pm_student_id">
          <input type="hidden" name="current_balance" id="pm_current_balance">
          <input type="hidden" name="payment_source"  id="pm_payment_source">

          {{-- Student (read-only) --}}
          <div class="mb-3">
            <label class="form-label">Student</label>
            <input type="text" id="pm_student_display" class="form-control" readonly>
          </div>

          {{-- Current balance (read-only) --}}
          <div class="mb-3">
            <label class="form-label">Current Balance</label>
            <input type="text" id="pm_balance_display" class="form-control" readonly>
          </div>

          {{-- Amount (read-only) --}}
          <div class="mb-3">
            <label class="form-label">Amount to Apply</label>
            <input type="number" step="0.01" min="0" name="amount" id="pm_amount" class="form-control" readonly>
          </div>

          {{-- Payment Method (locked to G-cash) --}}
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <input type="text" class="form-control" value="G-cash" readonly>
            <input type="hidden" name="payment_method" id="pm_method" value="G-cash">
          </div>

          {{-- Notes (editable) --}}
          <div class="mb-3">
            <label class="form-label">Reference / Notes (optional)</label>
            <input type="text" name="notes" id="pm_notes" class="form-control" placeholder="e.g., GCash Ref: 1234-5678">
          </div>

          <div class="small text-muted">
            Everything is locked except Notes. Adjustments, if any, can be done later from the student's ledger.
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" id="pm_apply_btn" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Apply Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
    