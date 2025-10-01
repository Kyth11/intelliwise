<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('faculties.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Faculty Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <!-- Faculty Fields -->
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_firstname" class="form-control" required>
                            <label>First Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_middlename" class="form-control">
                            <label>Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_lastname" class="form-control" required>
                            <label>Last Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="f_contact" class="form-control">
                            <label>Contact</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="f_address" class="form-control">
                            <label>Address</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="email" name="f_email" class="form-control">
                            <label>Email</label>
                        </div>
                    </div>

                    <!-- Login Fields -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="username" class="form-control" required>
                            <label>Username</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating input-group">
                            <input type="password" name="password" id="addFacultyPassword" class="form-control"
                                required>
                            <label for="addFacultyPassword">Password</label>
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                data-target="addFacultyPassword">👁</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editFacultyForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Faculty Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="id" id="editFacultyId">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_firstname" id="editFacultyFirstname" class="form-control"
                                required>
                            <label>First Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_middlename" id="editFacultyMiddlename" class="form-control">
                            <label>Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="f_lastname" id="editFacultyLastname" class="form-control" required>
                            <label>Last Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="f_contact" id="editFacultyContact" class="form-control">
                            <label>Contact</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="f_address" id="editFacultyAddress" class="form-control">
                            <label>Address</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="email" name="f_email" id="editFacultyEmail" class="form-control">
                            <label>Email</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="username" id="editFacultyUsername" class="form-control" required>
                            <label>Username</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating input-group">
                            <input type="password" name="password" id="editFacultyPassword" class="form-control">
                            <label for="editFacultyPassword">New Password (leave blank to keep current)</label>
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                data-target="editFacultyPassword">👁</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ================== Guardian Modals ================== -->
<!-- Add Guardian Modal -->
<div class="modal fade" id="addGuardianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('guardians.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Guardian Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <!-- Guardian Fields -->
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_firstname" class="form-control" required>
                            <label>First Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_middlename" class="form-control">
                            <label>Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_lastname" class="form-control" required>
                            <label>Last Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="g_contact" class="form-control">
                            <label>Contact</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="g_address" class="form-control">
                            <label>Address</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="email" name="g_email" class="form-control">
                            <label>Email</label>
                        </div>
                    </div>

                    <!-- Login Fields -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="username" class="form-control" required>
                            <label>Username</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating input-group">
                            <input type="password" name="password" id="addGuardianPassword" class="form-control"
                                required>
                            <label for="addGuardianPassword">Password</label>
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                data-target="addGuardianPassword">👁</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Guardian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guardian Modal -->
<div class="modal fade" id="editGuardianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editGuardianForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Guardian Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="id" id="editGuardianId">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_firstname" id="editGuardianFirstname" class="form-control"
                                required>
                            <label>First Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_middlename" id="editGuardianMiddlename" class="form-control">
                            <label>Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="g_lastname" id="editGuardianLastname" class="form-control"
                                required>
                            <label>Last Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="g_contact" id="editGuardianContact" class="form-control">
                            <label>Contact</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="g_address" id="editGuardianAddress" class="form-control">
                            <label>Address</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="email" name="g_email" id="editGuardianEmail" class="form-control">
                            <label>Email</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="username" id="editGuardianUsername" class="form-control" required>
                            <label>Username</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating input-group">
                            <input type="password" name="password" id="editGuardianPassword" class="form-control">
                            <label for="editGuardianPassword">New Password (leave blank to keep current)</label>
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                data-target="editGuardianPassword">👁</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Guardian</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ================== Scripts ================== -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Faculty edit modal prefill
    const editFacultyModal = document.getElementById('editFacultyModal');
    if (editFacultyModal) {
        editFacultyModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const form = document.getElementById('editFacultyForm');
            form.action = "{{ url('admin/faculties') }}/" + button.dataset.id;

            document.getElementById('editFacultyId').value = button.dataset.id;
            document.getElementById('editFacultyFirstname').value = button.dataset.firstname;
            document.getElementById('editFacultyMiddlename').value = button.dataset.middlename;
            document.getElementById('editFacultyLastname').value = button.dataset.lastname;
            document.getElementById('editFacultyContact').value = button.dataset.contact;
            document.getElementById('editFacultyAddress').value = button.dataset.address;
            document.getElementById('editFacultyEmail').value = button.dataset.email;   // ✅ FIXED
            document.getElementById('editFacultyUsername').value = button.dataset.username;
        });
    }

    // Guardian edit modal prefill
    const editGuardianModal = document.getElementById('editGuardianModal');
    if (editGuardianModal) {
        editGuardianModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const form = document.getElementById('editGuardianForm');
            form.action = "{{ url('admin/guardians') }}/" + button.dataset.id;

            document.getElementById('editGuardianId').value = button.dataset.id;
            document.getElementById('editGuardianFirstname').value = button.dataset.firstname;
            document.getElementById('editGuardianMiddlename').value = button.dataset.middlename;
            document.getElementById('editGuardianLastname').value = button.dataset.lastname;
            document.getElementById('editGuardianContact').value = button.dataset.contact;
            document.getElementById('editGuardianAddress').value = button.dataset.address;
            document.getElementById('editGuardianEmail').value = button.dataset.email;   // ✅ FIXED
            document.getElementById('editGuardianUsername').value = button.dataset.username;
        });
    }

    // Password toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});

</script>
