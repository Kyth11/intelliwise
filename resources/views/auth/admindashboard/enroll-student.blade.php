{{-- resources/views/auth/admindashboard/enroll-student.blade.php --}}
@extends('layouts.admin')

@section('title', 'Enrollment Form')

@push('styles')
    <style>
        /* Page styling */
        .enroll-page {
            max-width: 900px;
            margin: 0 auto;
        }

        .school-header {
            text-align: center;
            line-height: 1.3;
            margin-bottom: 1rem;
        }

        .school-header h3 {
            margin: 0;
            font-weight: 700;
        }

        .school-header .sub {
            color: #6c757d;
        }

        .enroll-actions {
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
            margin-bottom: .75rem;
        }

        .enroll-card {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        }

        /* Print layout: A4, hide chrome/nav */
        @page {
            size: A4;
            margin: 14mm;
        }

        @media print {

            .sidebar,
            .no-print,
            .flash-messages,
            .btn,
            .navbar,
            .card .card-header .btn {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .enroll-card {
                border: none !important;
                box-shadow: none !important;
            }
        }

        /* Form controls print nicely (show underline + value) */
        .form-control,
        .form-select,
        textarea.form-control {
            border: 1px solid #ced4da;
        }

        @media print {

            .form-control,
            .form-select,
            textarea.form-control {
                border: none !important;
                border-bottom: 1px solid #000 !important;
                border-radius: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            label.form-label,
            .form-floating>label {
                color: #000 !important;
                opacity: 1 !important;
            }

            .form-floating>label {
                position: static;
                transform: none;
            }

            .form-floating>.form-control,
            .form-floating>.form-select {
                height: auto;
                padding: 0;
            }
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 320px;
            margin-top: 2.25rem;
            text-align: center;
            padding-top: .25rem;
            font-size: .95rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3">
        <div class="enroll-page">

            <div class="enroll-actions no-print">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>

            <div class="enroll-card p-4">
                {{-- School Header --}}
                <div class="school-header">
                    <h3>INTELLIWISE GRACE CHRISTIAN ACADEMY</h3>
                    <div class="sub">Zone 3, Bonbon Opol Misamis Oriental</div>
                    <div class="sub">Contact no. +639161808738</div>
                </div>

                <hr>

                <h4 class="text-center mb-3">ENROLLMENT FORM</h4>

                <div class="alert alert-secondary">
                    <strong>INSTRUCTIONS:</strong> This form must be completely answered by the student’s parent/guardian.
                    Please read the questions carefully and fill in all applicable spaces and write your answers legibly in
                    <strong>CAPITAL letters</strong>. For items not applicable, write <strong>N/A</strong>.
                    Please make sure to submit a <strong>CLEAR COPY</strong> of the <strong>CHILD’S BIRTH
                        CERTIFICATE</strong>.
                </div>

                <form action="{{ route('students.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf

                    {{-- Flash + Validation
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
                    @endif --}}

                    {{-- Learner’s section --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">LEARNER’S NAME</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_lastname" placeholder="LAST NAME"
                                           value="{{ old('s_lastname') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_firstname" placeholder="FIRST NAME"
                                           value="{{ old('s_firstname') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_middlename"
                                           value="{{ old('s_middlename') }}"
                                           placeholder="MIDDLE NAME (optional)">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender">
                                <option value="">—</option>
                                <option {{ old('s_gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option {{ old('s_gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option {{ old('s_gender') === 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_birthdate" id="s_birthdate"
                                   value="{{ old('s_birthdate') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Citizenship</label>
                            <input type="text" class="form-control" name="s_citizenship" placeholder="e.g., Filipino"
                                   value="{{ old('s_citizenship') }}" required>
                        </div>


                        <div class="col-md-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" id="s_age" placeholder="Auto" readonly>
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="s_address" id="s_address"
                                   value="{{ old('s_address') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Religion</label>
                            <input type="text" class="form-control" name="s_religion" placeholder="e.g., Christian"
                                   value="{{ old('s_religion') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Contact No. (optional)</label>
                            <input type="text" class="form-control" name="s_contact" value="{{ old('s_contact') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" name="s_email" value="{{ old('s_email') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Does the learner have Special Education needs or disabilities?</label>
                            <div class="input-group">
                                <select class="form-select" id="sped_has" aria-label="SPED has">
                                    <option value="">—</option>
                                    <option value="Yes" {{ old('sped_has') === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No"  {{ old('sped_has', 'No') === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <input type="text" class="form-control" id="sped_desc"
                                       placeholder="If yes, please specify."
                                       value="{{ old('sped_desc') }}"
                                       {{ old('sped_has', 'No') === 'Yes' ? '' : 'disabled' }}>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GRADE LEVEL TO ENROLL</label>
                            <select name="s_gradelvl" class="form-select" required>
                                <option value="">-</option>
                                <option value="Nursery" {{ old('s_gradelvl') === 'Nursery' ? 'selected' : '' }}>Nursery</option>
                                <option value="Kindergarten 1" {{ old('s_gradelvl') === 'Kindergarten 1' ? 'selected' : '' }}>Kindergarten 1</option>
                                <option value="Kindergarten 2" {{ old('s_gradelvl') === 'Kindergarten 2' ? 'selected' : '' }}>Kindergarten 2</option>
                                @for ($i = 1; $i <= 6; $i++)
                                    <option value="Grade {{ $i }}" {{ old('s_gradelvl') === 'Grade ' . $i ? 'selected' : '' }}>
                                        Grade {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Name of the previous school (if applicable)</label>
                            <input type="text" class="form-control" name="previous_school"
                                   value="{{ old('previous_school') }}" placeholder="N/A if none">
                        </div>
                    </div> {{-- /row --}}

                    {{-- Optional Fees (if provided by the controller) --}}
                    @if (!empty($optionalFees) && $optionalFees->count())
                        <hr class="my-4">
                        <div>
                            <label class="form-label fw-semibold">Optional Fees</label>
                            <div class="row g-2">
                                @foreach ($optionalFees as $fee)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="opt_fee_{{ $fee->id }}"
                                                   name="student_optional_fee_ids[]"
                                                   value="{{ $fee->id }}"
                                                   {{ collect(old('student_optional_fee_ids', []))->contains($fee->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="opt_fee_{{ $fee->id }}">
                                                {{ $fee->name }} — ₱{{ number_format($fee->amount, 2) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">You can edit selected optional fees later from the student record.</div>
                        </div>
                    @endif

                    <hr class="my-4">

                    {{-- Parents / Guardian --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Parents / Guardian</label>
                        </div>

                        <div class="col-12">
                            <div class="form-text mb-1">Select an existing household or add new parents/guardian:</div>
                            <select name="guardian_id" id="guardian_id" class="form-select" required>
                                <option value="">— Select existing —</option>
                                @foreach ($guardians as $g)
                                    <option value="{{ $g->id }}"
                                        {{ old('guardian_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->display_name }} — {{ $g->display_contact }}
                                    </option>
                                @endforeach
                                <option value="new" {{ old('guardian_id') === 'new' ? 'selected' : '' }}>
                                    ➕ Add New Parents / Guardian
                                </option>
                            </select>
                        </div>

                        <div id="newGuardianFields" class="{{ old('guardian_id') === 'new' ? '' : 'd-none' }}">
                            {{-- Household Address --}}
                            <div class="col-12">
                                <label class="form-label">Household Address</label>
                                <input type="text" name="g_address" class="form-control"
                                       value="{{ old('g_address') }}"
                                       placeholder="Same as student address? Tick below">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="sameAddress">
                                    <label class="form-check-label" for="sameAddress">Same as student’s address</label>
                                </div>
                            </div>

                            {{-- Mother --}}
                            <div class="col-12">
                                <h6 class="mt-3 mb-1">Mother</h6>
                            </div>
                            <div class="col-md-4"><input type="text" name="m_firstname" class="form-control"
                                    value="{{ old('m_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="m_middlename" class="form-control"
                                    value="{{ old('m_middlename') }}" placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="m_lastname" class="form-control"
                                    value="{{ old('m_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="m_contact" class="form-control"
                                    value="{{ old('m_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="m_email" class="form-control"
                                    value="{{ old('m_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="m_occupation" class="form-control"
                                    value="{{ old('m_occupation') }}" placeholder="Occupation (print only)"></div>

                            {{-- Father --}}
                            <div class="col-12">
                                <h6 class="mt-3 mb-1">Father</h6>
                            </div>
                            <div class="col-md-4"><input type="text" name="f_firstname" class="form-control"
                                    value="{{ old('f_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="f_middlename" class="form-control"
                                    value="{{ old('f_middlename') }}" placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="f_lastname" class="form-control"
                                    value="{{ old('f_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="f_contact" class="form-control"
                                    value="{{ old('f_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="f_email" class="form-control"
                                    value="{{ old('f_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="f_occupation" class="form-control"
                                    value="{{ old('f_occupation') }}" placeholder="Occupation (print only)"></div>

                            {{-- In case with a guardian (full name, contact, relation) --}}
                            <div class="col-12">
                                <label class="form-label mt-2">If learner is with a guardian, please indicate details</label>
                                <input type="text" name="alt_guardian_details" class="form-control"
                                       value="{{ old('alt_guardian_details') }}"
                                       placeholder="Full name, contact no., relation (print only)">
                            </div>

                            {{-- Single login for parents --}}
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hasLogin" name="has_login"
                                           {{ old('has_login') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hasLogin">Create Parent/Guardian Login Account
                                        (single account)</label>
                                </div>
                            </div>
                            <div id="guardianLoginFields" class="row g-2 {{ old('has_login') ? '' : 'd-none' }}">
                                <div class="col-md-6">
                                    <input type="text" name="username" class="form-control"
                                           value="{{ old('username') }}" placeholder="Username">
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="password" name="password" id="guardianPassword" class="form-control"
                                               placeholder="Password">
                                        <button type="button" class="btn btn-outline-secondary toggle-password"
                                                data-target="guardianPassword">👁</button>
                                    </div>
                                </div>
                            </div>
                        </div> {{-- /#newGuardianFields --}}
                    </div> {{-- /row --}}

                    <hr class="my-4">

                    {{-- Consent --}}
                    <div>
                        <p class="mb-2">
                            As the parent (or legal guardian) of the above-named learner, I hereby consent to his/her enrollment at
                            <strong>INTELLIWISE GRACE CHRISTIAN ACADEMY</strong>. In addition to such consent, I hereby acknowledge
                            the following conditions:
                        </p>
                        <ol class="mb-3">
                            <li>I will be responsible for paying for the learner’s school fees.</li>
                            <li>An initial amount will be given to the institution to officially enroll the learner.</li>
                            <li>The school will have the right to hold off documents and important records in case of
                                failure in settling school payables.</li>
                            <li>The learner will be provided with all the necessary equipment for distant learning.</li>
                            <li>To be open and available for communication with regards to matters about the learner, his
                                studies, and other school activities.</li>
                            <li>I will be liable, and I am aware that failure to settle my financial obligations, the school
                                will execute legal actions against me, therefore I will make sure to pay all fees within the said school year.</li>
                        </ol>

                        <div class="d-flex justify-content-end">
                            <div class="signature-line">
                                (Signature over printed name)
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2 no-print">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-circle me-1"></i> Save
                        </button>
                        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.needs-validation');

            // === Helper: attach custom "required" message to an element ===
            function applyRequiredMessage(el) {
                if (!el) return;
                el.addEventListener('invalid', function () {
                    if (el.validity.valueMissing) {
                        el.setCustomValidity('You are required to fill this field');
                    }
                });
                ['input', 'change'].forEach(evt =>
                    el.addEventListener(evt, () => el.setCustomValidity(''))
                );
            }

            // Attach to all currently-required controls
            form.querySelectorAll('[required]').forEach(applyRequiredMessage);

            // === Existing refs from your script ===
            const birth = document.getElementById('s_birthdate');
            const age = document.getElementById('s_age');
            const spedHas = document.getElementById('sped_has');
            const spedDesc = document.getElementById('sped_desc');

            function calcAge() {
                if (!birth.value) { age.value = ''; return; }
                const b = new Date(birth.value), t = new Date();
                let a = t.getFullYear() - b.getFullYear();
                const m = t.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && t.getDate() < b.getDate())) a--;
                age.value = isNaN(a) ? '' : a;
            }
            birth.addEventListener('change', calcAge);
            calcAge();

            spedHas.addEventListener('change', () => {
                const yes = spedHas.value === 'Yes';
                spedDesc.disabled = !yes;
                if (!yes) spedDesc.value = '';
            });

            // === Guardian block logic (enhanced) ===
            const guardianSelect = document.getElementById('guardian_id');
            const newGuardianFields = document.getElementById('newGuardianFields');
            const hasLoginCheckbox = document.getElementById('hasLogin');
            const guardianLoginFields = document.getElementById('guardianLoginFields');
            const sameAddress = document.getElementById('sameAddress');
            const studentAddress = document.getElementById('s_address');

            // Fields that must be REQUIRED when adding a new household
            const requiredWhenNew = [
                'input[name="g_address"]',
                'input[name="m_firstname"]',
                'input[name="m_lastname"]',
                'input[name="m_contact"]',
                'input[name="f_firstname"]',
                'input[name="f_lastname"]',
                'input[name="f_contact"]',
            ];

            // Login fields become required only if "Create Parent/Guardian Login Account" is checked
            const loginRequired = [
                'input[name="username"]',
                'input[name="password"]',
            ];

            function setRequiredForNewGuardian(isNew) {
                requiredWhenNew.forEach(sel => {
                    const el = newGuardianFields.querySelector(sel);
                    if (!el) return;
                    if (isNew) {
                        el.setAttribute('required', 'required');
                        applyRequiredMessage(el);
                    } else {
                        el.removeAttribute('required');
                        el.setCustomValidity('');
                    }
                });

                // Handle login conditional required
                loginRequired.forEach(sel => {
                    const el = newGuardianFields.querySelector(sel);
                    if (!el) return;
                    const needLogin = isNew && hasLoginCheckbox.checked;
                    if (needLogin) {
                        el.setAttribute('required', 'required');
                        applyRequiredMessage(el);
                    } else {
                        el.removeAttribute('required');
                        el.setCustomValidity('');
                    }
                });
            }

            // Initial attach message to guardian selector (it has required in HTML)
            applyRequiredMessage(guardianSelect);

            function toggleGuardianUI() {
                const isNew = guardianSelect.value === 'new';
                newGuardianFields.classList.toggle('d-none', !isNew);
                if (!isNew) {
                    // Clear values when toggling away
                    newGuardianFields.querySelectorAll('input').forEach(i => {
                        // do not wipe old() restored values on initial load
                        if (!document.body.dataset.initialized) {
                            return;
                        }
                        i.value = '';
                        i.setCustomValidity('');
                    });
                    hasLoginCheckbox.checked = false;
                    guardianLoginFields.classList.add('d-none');
                }
                setRequiredForNewGuardian(isNew);
            }

            guardianSelect.addEventListener('change', toggleGuardianUI);

            hasLoginCheckbox.addEventListener('change', function () {
                guardianLoginFields.classList.toggle('d-none', !this.checked);
                setRequiredForNewGuardian(guardianSelect.value === 'new');
                if (!this.checked) {
                    guardianLoginFields.querySelectorAll('input').forEach(i => {
                        i.value = '';
                        i.setCustomValidity('');
                        i.removeAttribute('required');
                    });
                }
            });

            sameAddress.addEventListener('change', function () {
                const hhAddress = document.querySelector('input[name="g_address"]');
                if (this.checked) hhAddress.value = studentAddress.value;
            });

            document.querySelectorAll('.toggle-password').forEach(btn => {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(this.dataset.target);
                    input.type = input.type === 'password' ? 'text' : 'password';
                });
            });

            // === Submit handler: client validation + smooth scroll to first error ===
            form.addEventListener('submit', function (e) {
                // Ensure conditional requireds are set correctly at submit time
                setRequiredForNewGuardian(guardianSelect.value === 'new');

                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    form.classList.add('was-validated');

                    // Scroll to first invalid control
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus({ preventScroll: true });
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }, false);

            // mark that initial old() hydration has occurred, then run initial toggle to reflect default selection
            document.body.dataset.initialized = 'true';
            toggleGuardianUI();
        });
    </script>
@endpush
