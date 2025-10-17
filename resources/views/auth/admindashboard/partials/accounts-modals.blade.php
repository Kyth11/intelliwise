{{-- resources/views/auth/admindashboard/partials/accounts-modals.blade.php --}}

{{-- =========================
     ADD FACULTY MODAL
   ========================= --}}
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addFacultyForm" action="{{ route('faculties.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #476DA3, #3C5F8E); color:#fff;">
                    <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i> Add Faculty Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="f_firstname" value="{{ old('f_firstname') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Middle Name</label>
                            <input type="text" class="form-control" name="f_middlename" value="{{ old('f_middlename') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="f_lastname" value="{{ old('f_lastname') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Contact</label>
                            <input type="text" class="form-control" name="f_contact" value="{{ old('f_contact') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Address</label>
                            <input type="text" class="form-control" name="f_address" value="{{ old('f_address') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Email</label>
                            <input type="email" class="form-control" name="f_email" value="{{ old('f_email') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" value="{{ old('username') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="af_password" autocomplete="new-password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" id="af_toggle_pwd" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 6 characters.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Create Faculty
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- =========================
     ADD GUARDIAN MODAL
   ========================= --}}
<div class="modal fade" id="addGuardianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="addGuardianForm" action="{{ route('guardians.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #476DA3, #3C5F8E); color:#fff;">
                    <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i> Add Guardian Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Primary Guardian (preferred) --}}
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-vcard"></i>
                            <span class="fw-semibold">Primary Guardian Details</span>
                            <span class="text-muted small">(required)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="g_firstname" value="{{ old('g_firstname') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Middle Name</label>
                                <input type="text" class="form-control" name="g_middlename" value="{{ old('g_middlename') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="g_lastname" value="{{ old('g_lastname') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Contact</label>
                                <input type="text" class="form-control" name="g_contact" value="{{ old('g_contact') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Address</label>
                                <input type="text" class="form-control" name="g_address" value="{{ old('g_address') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small">Email</label>
                                <input type="email" class="form-control" name="g_email" value="{{ old('g_email') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Optional Mother --}}
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-gender-female"></i>
                            <span class="fw-semibold">Mother (optional)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">First Name</label>
                                <input type="text" class="form-control" name="m_firstname" value="{{ old('m_firstname') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Middle Name</label>
                                <input type="text" class="form-control" name="m_middlename" value="{{ old('m_middlename') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Last Name</label>
                                <input type="text" class="form-control" name="m_lastname" value="{{ old('m_lastname') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Contact</label>
                                <input type="text" class="form-control" name="m_contact" value="{{ old('m_contact') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" class="form-control" name="m_email" value="{{ old('m_email') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Optional Father --}}
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-gender-male"></i>
                            <span class="fw-semibold">Father (optional)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">First Name</label>
                                <input type="text" class="form-control" name="f_firstname" value="{{ old('f_firstname') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Middle Name</label>
                                <input type="text" class="form-control" name="f_middlename" value="{{ old('f_middlename') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Last Name</label>
                                <input type="text" class="form-control" name="f_lastname" value="{{ old('f_lastname') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Contact</label>
                                <input type="text" class="form-control" name="f_contact" value="{{ old('f_contact') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" class="form-control" name="f_email" value="{{ old('f_email') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Account credentials --}}
                    <div class="border rounded-3 p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-lock"></i>
                            <span class="fw-semibold">Account Credentials</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" value="{{ old('username') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="ag_password" autocomplete="new-password" minlength="6" required>
                                    <button class="btn btn-outline-secondary" type="button" id="ag_toggle_pwd" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 6 characters.</div>
                            </div>
                        </div>
                    </div>
                </div> {{-- /modal-body --}}

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Create Guardian
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
/**
 * Toggle password visibility for Add Faculty & Add Guardian
 */
document.addEventListener('click', (e) => {
    if (e.target.closest('#af_toggle_pwd')) {
        const btn  = e.target.closest('button');
        const inp  = document.getElementById('af_password');
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }
    if (e.target.closest('#ag_toggle_pwd')) {
        const btn  = e.target.closest('button');
        const inp  = document.getElementById('ag_password');
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }
});

/**
 * Reset forms when modals close to avoid stale values
 */
const resetOnHide = (modalId, formId) => {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    modalEl.addEventListener('hidden.bs.modal', () => {
        const form = document.getElementById(formId);
        if (form) form.reset();
    });
};
resetOnHide('addFacultyModal', 'addFacultyForm');
resetOnHide('addGuardianModal', 'addGuardianForm');
</script>
@endpush
