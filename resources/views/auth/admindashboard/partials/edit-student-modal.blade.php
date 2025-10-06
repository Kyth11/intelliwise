<!-- ================== Student Modals ================== -->

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editStudentForm" method="POST" action="">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-3">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="tuition_id" id="edit-tuition-id">

                    <!-- Personal Information -->
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="s_firstname" id="edit-firstname" class="form-control"
                                placeholder="First Name" required>
                            <label for="edit-firstname">First Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="s_middlename" id="edit-middlename" class="form-control"
                                placeholder="Middle Name">
                            <label for="edit-middlename">Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="s_lastname" id="edit-lastname" class="form-control"
                                placeholder="Last Name" required>
                            <label for="edit-lastname">Last Name</label>
                        </div>
                    </div>

                    <!-- Grade Level (select) + Birthdate -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select name="s_gradelvl" id="edit-gradelvl" class="form-select">
                                @foreach(($gradelvls ?? []) as $g)
                                    <option value="{{ $g->grade_level }}">{{ $g->grade_level }}</option>
                                @endforeach
                            </select>
                            <label for="edit-gradelvl">Grade Level</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" name="s_birthdate" id="edit-birthdate" class="form-control"
                                placeholder="Birthdate">
                            <label for="edit-birthdate">Birthdate</label>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" name="s_address" id="edit-address" class="form-control"
                                placeholder="Address">
                            <label for="edit-address">Address</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="s_contact" id="edit-contact" class="form-control"
                                placeholder="Contact">
                            <label for="edit-contact">Contact</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" name="s_email" id="edit-email" class="form-control" placeholder="Email">
                            <label for="edit-email">Email</label>
                        </div>
                    </div>

                    <!-- Guardian (readonly) -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" id="edit-guardian" class="form-control" placeholder="Guardian" disabled>
                            <label for="edit-guardian">Guardian</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" id="edit-guardianemail" class="form-control"
                                placeholder="Guardian Email" disabled>
                            <label for="edit-guardianemail">Guardian Email</label>
                        </div>
                    </div>

                    <!-- Tuition + Status -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="s_tuition_sum" id="edit-tuition" class="form-control"
                                placeholder="Tuition" readonly>
                            <label for="edit-tuition">Tuition (auto from grade level)</label>
                        </div>
                        <small class="text-muted">This comes from the latest Tuition record for the chosen grade level.</small>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select name="payment_status" id="edit-payment" class="form-select">
                                <option value="Not Paid">Not Paid</option>
                                <option value="Paid">Paid</option>
                            </select>
                            <label for="edit-payment">Payment Status</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select name="enrollment_status" id="edit-status" class="form-select">
                                <option value="Enrolled">Enrolled</option>
                                <option value="Not Enrolled">Not Enrolled</option>
                            </select>
                            <label for="edit-status">Enrollment Status</label>
                        </div>
                    </div>

                    <!-- STUDENT OPTIONAL FEES (multi-select) -->
                    <div class="col-12">
                        <label class="form-label mt-2">Student Optional Fees</label>
                        <div class="border rounded p-2" style="max-height: 220px; overflow:auto;">
                            @php
                                $__fees = ($optionalFees ?? collect())->filter(function($f){
                                    // only show student-attachable fees (scope student/both) and active
                                    $scopeOk = !isset($f->scope) || in_array($f->scope, ['student','both']);
                                    return $scopeOk && (property_exists($f, 'active') ? $f->active : true);
                                });
                            @endphp

                            @forelse($__fees as $fee)
                                <div class="form-check">
                                    <input class="form-check-input edit-opt-fee" type="checkbox"
                                           id="stu_fee_{{ $fee->id }}"
                                           name="student_optional_fee_ids[]"
                                           value="{{ $fee->id }}"
                                           data-amount="{{ number_format($fee->amount, 2, '.', '') }}">
                                    <label class="form-check-label" for="stu_fee_{{ $fee->id }}">
                                        {{ $fee->name }} — ₱{{ number_format($fee->amount, 2) }}
                                    </label>
                                </div>
                            @empty
                                <div class="text-muted">No student-level optional fees available.</div>
                            @endforelse
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" id="edit-optional-total" class="form-control" placeholder="Optional Total" readonly>
                                    <label for="edit-optional-total">Selected Optional Total (₱)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" id="edit-total-due-preview" class="form-control" placeholder="Total Due" readonly>
                                    <label for="edit-total-due-preview">Total Due (Preview) (₱)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    // Build a map: grade_level => latest tuition row (id + total_yearly)
    $__tuitionMap = [];
    if (isset($tuitions)) {
        foreach ($tuitions as $row) {
            $gl = $row->grade_level;
            $ts = $row->updated_at ?? $row->created_at;
            if (!isset($__tuitionMap[$gl]) || strtotime($ts) > strtotime($__tuitionMap[$gl]['_ts'])) {
                $__tuitionMap[$gl] = [
                    'id' => $row->id,
                    'total_yearly' => (float) $row->total_yearly,
                    '_ts' => $ts,
                ];
            }
        }
        foreach ($__tuitionMap as &$v) { unset($v['_ts']); }
        unset($v);
    }
@endphp

<script>
    const editStudentModal = document.getElementById('editStudentModal');

    // Tuition map from server
    const TUITION_MAP = {!! json_encode($__tuitionMap ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};

    function applyTuitionFromGrade(gradeLevel) {
        const t = TUITION_MAP[gradeLevel];
        const tuitionInput = document.getElementById('edit-tuition');
        const tuitionIdInput = document.getElementById('edit-tuition-id');
        if (t) {
            tuitionInput.value = Number(t.total_yearly).toFixed(2);
            tuitionIdInput.value = t.id;
        } else {
            tuitionInput.value = '';
            tuitionIdInput.value = '';
        }
        recalcTotals();
    }

    function recalcTotals() {
        const base = parseFloat(document.getElementById('edit-tuition').value || '0') || 0;
        const opt = Array.from(editStudentModal.querySelectorAll('.edit-opt-fee:checked'))
            .reduce((sum, cb) => sum + (parseFloat(cb.dataset.amount || '0') || 0), 0);
        const optField = document.getElementById('edit-optional-total');
        const dueField = document.getElementById('edit-total-due-preview');
        if (optField) optField.value = opt.toFixed(2);
        if (dueField) dueField.value = (base + opt).toFixed(2);
    }

    editStudentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const id            = button.getAttribute('data-id');
        const firstname     = button.getAttribute('data-firstname');
        const middlename    = button.getAttribute('data-middlename');
        const lastname      = button.getAttribute('data-lastname');
        const gradelvl      = button.getAttribute('data-gradelvl');
        const birthdate     = button.getAttribute('data-birthdate');
        const address       = button.getAttribute('data-address');
        const contact       = button.getAttribute('data-contact');
        const email         = button.getAttribute('data-email');
        const guardian      = button.getAttribute('data-guardian');
        const guardianEmail = button.getAttribute('data-guardianemail');
        const status        = button.getAttribute('data-status');
        const payment       = button.getAttribute('data-payment');

        // Selected fee IDs (comma-separated): "1,3,5"
        const feeIdsRaw = button.getAttribute('data-feeids') || '';
        const feeIds = feeIdsRaw.split(',').map(s => s.trim()).filter(Boolean);

        // Populate fields
        editStudentModal.querySelector('#edit-id').value = id;
        editStudentModal.querySelector('#edit-firstname').value = firstname ?? '';
        editStudentModal.querySelector('#edit-middlename').value = middlename ?? '';
        editStudentModal.querySelector('#edit-lastname').value = lastname ?? '';
        editStudentModal.querySelector('#edit-gradelvl').value = gradelvl ?? '';
        editStudentModal.querySelector('#edit-birthdate').value = birthdate ?? '';
        editStudentModal.querySelector('#edit-address').value = address ?? '';
        editStudentModal.querySelector('#edit-contact').value = contact ?? '';
        editStudentModal.querySelector('#edit-email').value = email ?? '';
        editStudentModal.querySelector('#edit-guardian').value = guardian ?? '';
        editStudentModal.querySelector('#edit-guardianemail').value = guardianEmail ?? '';
        editStudentModal.querySelector('#edit-status').value = status ?? 'Enrolled';
        editStudentModal.querySelector('#edit-payment').value = payment ?? 'Not Paid';

        // Tick optional-fee checkboxes
        editStudentModal.querySelectorAll('.edit-opt-fee').forEach(cb => {
            cb.checked = feeIds.includes(cb.value);
        });

        // Tuition (auto from grade level)
        applyTuitionFromGrade(gradelvl);

        // Reassign handlers each open
        editStudentModal.querySelector('#edit-gradelvl').onchange = function () {
            applyTuitionFromGrade(this.value);
        };
        editStudentModal.querySelectorAll('.edit-opt-fee').forEach(cb => cb.onchange = recalcTotals);

        // Correct form action (admin namespace)
        const form = editStudentModal.querySelector('#editStudentForm');
        form.action = `/admin/students/${id}`;
    });
</script>
