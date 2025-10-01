<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Enroll New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Success/Error Messages --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <h6>Student Information</h6>

                        <!-- First Name -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="s_firstname" id="s_firstname" class="form-control"
                                       placeholder="First Name" required>
                                <label for="s_firstname">First Name</label>
                            </div>
                        </div>

                        <!-- Middle Name -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="s_middlename" id="s_middlename"
                                       class="form-control" placeholder="Middle Name">
                                <label for="s_middlename">Middle Name (if applicable)</label>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="s_lastname" id="s_lastname" class="form-control"
                                       placeholder="Last Name" required>
                                <label for="s_lastname">Last Name</label>
                            </div>
                        </div>

                        <!-- Birthdate -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" name="s_birthdate" id="s_birthdate" class="form-control"
                                       placeholder="Birthdate" required>
                                <label for="s_birthdate">Birthdate</label>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="s_address" id="s_address" class="form-control"
                                       placeholder="Address" required>
                                <label for="s_address">Address</label>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="s_contact" id="s_contact" class="form-control"
                                       placeholder="Contact Number (optional)">
                                <label for="s_contact">Contact Number (optional)</label>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" name="s_email" id="s_email" class="form-control"
                                       placeholder="Email (optional)">
                                <label for="s_email">Email (optional)</label>
                            </div>
                        </div>

                        <h6 class="mt-3">Guardian Information</h6>

                        <!-- Select Existing Guardian -->
                        <div class="col-md-12">
                            <div class="form-floating">
                                <select name="guardian_id" id="guardian_id" class="form-select">
                                    <option value="">-</option>
                                    @foreach($guardians as $guardian)
                                        <option value="{{ $guardian->id }}">
                                            {{ $guardian->g_firstname }} {{ $guardian->g_lastname }} -
                                            {{ $guardian->g_contact }}
                                        </option>
                                    @endforeach
                                    <option value="new">➕ Add New Guardian</option>
                                </select>
                                <label for="guardian_id">Select Guardian</label>
                            </div>
                        </div>

                        <!-- New Guardian Fields (hidden by default) -->
                        <div id="newGuardianFields" class="row g-3 d-none mt-2">
                            <h6>New Guardian Information</h6>

                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="g_firstname" class="form-control"
                                           placeholder="Guardian First Name" required>
                                    <label>First Name</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="g_lastname" class="form-control"
                                           placeholder="Guardian Last Name" required>
                                    <label>Last Name</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="g_address" class="form-control"
                                           placeholder="Guardian Address" required >
                                    <label>Address</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="g_contact" class="form-control"
                                           placeholder="Guardian Contact" required>
                                    <label>Contact</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" name="g_email" class="form-control"
                                           placeholder="Guardian Email">
                                    <label>Email (optional)</label>
                                </div>
                            </div>

                            <!-- Checkbox to toggle Guardian Login -->
                            <div class="col-md-12 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hasLogin" name="has_login">
                                    <label class="form-check-label" for="hasLogin">
                                        Create Guardian Login Account
                                    </label>
                                </div>
                            </div>

                            <!-- Guardian Login Fields (hidden by default) -->
                            <div id="guardianLoginFields" class="row g-3 d-none mt-2">
                                <h6>Guardian Login</h6>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="username" class="form-control" placeholder="Username">
                                        <label>Username</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating input-group">
                                        <input type="password" name="password" id="guardianPassword" class="form-control" placeholder="Password">
                                        <label for="guardianPassword">Password</label>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="guardianPassword">👁</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-3">Enrollment Details</h6>

                        <!-- Grade Level -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="s_gradelvl" id="s_gradelvl" class="form-select" required>
                                    <option value="">-</option>
                                    <option value="Pre-Schooler">Pre-Schooler</option>
                                    <option value="Nursery">Nursery</option>
                                    <option value="Kindergarten">Kindergarten</option>
                                    <option value="Grade 1">Grade 1</option>
                                    <option value="Grade 2">Grade 2</option>
                                    <option value="Grade 3">Grade 3</option>
                                    <option value="Grade 4">Grade 4</option>
                                    <option value="Grade 5">Grade 5</option>
                                    <option value="Grade 6">Grade 6</option>
                                </select>
                                <label for="s_gradelvl">Grade Level</label>
                            </div>
                        </div>

                        <!-- Enrollment Status -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="enrollment_status" id="enrollment_status" class="form-select">
                                    <option value="Enrolled" selected>Enrolled</option>
                                    <option value="Not Enrolled">Not Enrolled</option>
                                </select>
                                <label for="enrollment_status">Enrollment Status</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i> Save Student
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const guardianSelect = document.getElementById('guardian_id');
    const newGuardianFields = document.getElementById('newGuardianFields');
    const hasLoginCheckbox = document.getElementById('hasLogin');
    const guardianLoginFields = document.getElementById('guardianLoginFields');

    guardianSelect.addEventListener('change', function () {
        if (this.value === 'new') {
            newGuardianFields.classList.remove('d-none');
        } else {
            newGuardianFields.classList.add('d-none');
            newGuardianFields.querySelectorAll('input').forEach(input => input.value = '');
            hasLoginCheckbox.checked = false;
            guardianLoginFields.classList.add('d-none');
        }
    });

    hasLoginCheckbox.addEventListener('change', function () {
        if (this.checked) {
            guardianLoginFields.classList.remove('d-none');
        } else {
            guardianLoginFields.classList.add('d-none');
            guardianLoginFields.querySelectorAll('input').forEach(input => input.value = '');
        }
    });

    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});
</script>


            <script>
                const editStudentModal = document.getElementById('editStudentModal');
                editStudentModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;

                    // Get data attributes
                    const id = button.getAttribute('data-id');
                    const firstname = button.getAttribute('data-firstname');
                    const middlename = button.getAttribute('data-middlename');
                    const lastname = button.getAttribute('data-lastname');
                    const gradelvl = button.getAttribute('data-gradelvl');
                    const birthdate = button.getAttribute('data-birthdate');
                    const address = button.getAttribute('data-address');
                    const contact = button.getAttribute('data-contact');
                    const email = button.getAttribute('data-email');
                    const guardianfirstname = button.getAttribute('data-guardianfirstname');
                    const guardianlastname = button.getAttribute('data-guardianlastname');
                    const guardiancontact = button.getAttribute('data-guardiancontact');
                    const guardianemail = button.getAttribute('data-guardianemail');
                    const tuition = button.getAttribute('data-tuition');
                    const status = button.getAttribute('data-status');
                    const payment = button.getAttribute('data-payment');

                    // Fill form
                    document.getElementById('editStudentForm').action = `/students/${id}`;
                    document.getElementById('edit_firstname').value = firstname;
                    document.getElementById('edit_middlename').value = middlename;
                    document.getElementById('edit_lastname').value = lastname;
                    document.getElementById('edit_gradelvl').value = gradelvl;
                    document.getElementById('edit_birthdate').value = birthdate;
                    document.getElementById('edit_address').value = address;
                    document.getElementById('edit_contact').value = contact;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_guardianfirstname').value = guardianfirstname;
                    document.getElementById('edit_guardianlastname').value = guardianlastname;
                    document.getElementById('edit_guardiancontact').value = guardiancontact;
                    document.getElementById('edit_guardianemail').value = guardianemail;
                    document.getElementById('edit_tuition').value = tuition;
                    document.getElementById('edit_status').value = status;
                    document.getElementById('edit_payment').value = payment;
                });
            </script>
