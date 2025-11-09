{{-- resources/views/auth/admindashboard/partials/edit-student-modal.blade.php --}}
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="editStudentForm"
            method="POST"
            action="{{ route('admin.students.update', ['lrn' => 'LRN_PLACEHOLDER']) }}">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">LRN</label>
              <input class="form-control" id="edit_lrn" name="lrn_display" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label">First name</label>
              <input class="form-control" id="edit_firstname" name="s_firstname" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Middle name</label>
              <input class="form-control" id="edit_middlename" name="s_middlename">
            </div>

            <div class="col-md-6">
              <label class="form-label">Last name</label>
              <input class="form-control" id="edit_lastname" name="s_lastname" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Birthdate</label>
              <input type="date" class="form-control" id="edit_birthdate" name="s_birthdate" required>
            </div>

            <div class="col-12">
              <label class="form-label">Address</label>
              <input class="form-control" id="edit_address" name="s_address" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Grade Level</label>
              <select class="form-select" id="edit_gradelvl" name="s_gradelvl">
                <option value="">—</option>
                <option value="Nursery">Nursery</option>
                <option value="Kindergarten 1">Kindergarten 1</option>
                <option value="Kindergarten 2">Kindergarten 2</option>
                @for ($i = 1; $i <= 6; $i++)
                    <option value="Grade {{ $i }}">Grade {{ $i }}</option>
                @endfor
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select class="form-select" id="edit_gender" name="s_gender">
                <option value="">—</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Prefer not to say">Prefer not to say</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Contact</label>
              <input class="form-control" id="edit_contact" name="s_contact">
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" id="edit_email" name="s_email" type="email">
            </div>

            <div class="col-md-6">
              <label class="form-label">Enrollment Status</label>
              <select class="form-select" id="edit_status" name="enrollment_status">
                <option value="Enrolled">Enrolled</option>
                <option value="Pending">Pending</option>
                <option value="Dropped">Dropped</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Payment Status</label>
              <select class="form-select" id="edit_payment" name="payment_status">
                <option value="Unpaid">Unpaid</option>
                <option value="Partial">Partial</option>
                <option value="Paid">Paid</option>
              </select>
            </div>

            @if (!empty($optionalFees) && $optionalFees->count())
            <div class="col-12">
              <label class="form-label">Optional Fees</label>
              <div class="row g-2">
                @foreach ($optionalFees as $fee)
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input opt-fee-checkbox"
                             type="checkbox"
                             id="edit_opt_fee_{{ $fee->id }}"
                             name="student_optional_fee_ids[]"
                             value="{{ $fee->id }}">
                      <label class="form-check-label" for="edit_opt_fee_{{ $fee->id }}">
                        {{ $fee->name }} — ₱{{ number_format($fee->amount, 2) }}
                      </label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
            @endif

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Save changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
    const modalEl = document.getElementById('editStudentModal');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        if (!btn) return;

        // Read data-* from the clicked "edit" button
        const lrn        = btn.getAttribute('data-lrn') || '';
        const firstname  = btn.getAttribute('data-firstname') || '';
        const middlename = btn.getAttribute('data-middlename') || '';
        const lastname   = btn.getAttribute('data-lastname') || '';
        const birthdate  = (btn.getAttribute('data-birthdate') || '').substring(0,10);
        const address    = btn.getAttribute('data-address') || '';
        const contact    = btn.getAttribute('data-contact') || '';
        const email      = btn.getAttribute('data-email') || '';
        const gradelvl   = btn.getAttribute('data-gradelvl') || '';
        const gender     = btn.getAttribute('data-gender') || '';
        const status     = btn.getAttribute('data-status') || 'Enrolled';
        const payment    = btn.getAttribute('data-payment') || 'Unpaid';
        const feeIdsCsv  = btn.getAttribute('data-feeids') || '';

        // Fill fields
        document.getElementById('edit_lrn').value = lrn;
        document.getElementById('edit_firstname').value = firstname;
        document.getElementById('edit_middlename').value = middlename;
        document.getElementById('edit_lastname').value = lastname;
        document.getElementById('edit_birthdate').value = birthdate;
        document.getElementById('edit_address').value = address;
        document.getElementById('edit_contact').value = contact;
        document.getElementById('edit_email').value = email;

        const glSel = document.getElementById('edit_gradelvl');
        if (glSel) glSel.value = gradelvl;

        const genderSel = document.getElementById('edit_gender');
        if (genderSel) genderSel.value = gender;

        const statusSel = document.getElementById('edit_status');
        if (statusSel) statusSel.value = status;

        const paySel = document.getElementById('edit_payment');
        if (paySel) paySel.value = payment;

        // Precheck optional fee checkboxes
        const picked = new Set(feeIdsCsv.split(',').map(s => s.trim()).filter(Boolean));
        document.querySelectorAll('#editStudentModal .opt-fee-checkbox').forEach(chk => {
            chk.checked = picked.has(String(chk.value));
        });

        // Fix the form action to include the {lrn} parameter
        const form = document.getElementById('editStudentForm');
        const template = form.getAttribute('action'); // contains .../LRN_PLACEHOLDER
        const newAction = template.replace('LRN_PLACEHOLDER', encodeURIComponent(lrn));
        form.setAttribute('action', newAction);
    });

    // When modal hides, restore the action to the template (so next open can replace again)
    modalEl.addEventListener('hidden.bs.modal', function () {
        const form = document.getElementById('editStudentForm');
        const raw = "{{ route('admin.students.update', ['lrn' => 'LRN_PLACEHOLDER']) }}";
        form.setAttribute('action', raw);
    });
})();
</script>
@endpush
