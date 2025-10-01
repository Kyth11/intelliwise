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
                        <small class="text-muted">This comes from the latest Tuition record for the chosen grade
                            level.</small>
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
                </div>

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
        // drop internal key
        foreach ($__tuitionMap as &$v) {
            unset($v['_ts']);
        }
        unset($v);
    }
@endphp

<script>
    const editStudentModal = document.getElementById('editStudentModal');

    // Tuition map from server: { "Grade 1": {id: 3, total_yearly: 25000}, ... }
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
    }

    editStudentModal.addEventListener('show.bs.modal', function (event) {
        let button = event.relatedTarget;

        let id = button.getAttribute('data-id');
        let firstname = button.getAttribute('data-firstname');
        let middlename = button.getAttribute('data-middlename');
        let lastname = button.getAttribute('data-lastname');
        let gradelvl = button.getAttribute('data-gradelvl');
        let birthdate = button.getAttribute('data-birthdate');
        let address = button.getAttribute('data-address');
        let contact = button.getAttribute('data-contact');
        let email = button.getAttribute('data-email');
        let guardian = button.getAttribute('data-guardian');
        let guardianEmail = button.getAttribute('data-guardianemail');
        let status = button.getAttribute('data-status');
        let payment = button.getAttribute('data-payment');

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

        // Tuition (auto from grade level)
        applyTuitionFromGrade(gradelvl);

        // When grade changes, refresh tuition mapping
        editStudentModal.querySelector('#edit-gradelvl').addEventListener('change', function () {
            applyTuitionFromGrade(this.value);
        });

        // ✅ Correct form action (admin namespace)
        let form = editStudentModal.querySelector('#editStudentForm');
        form.action = `/admin/students/${id}`;
    });
</script>
