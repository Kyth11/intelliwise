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

        /* Suggest dropdown */
        .suggest-wrap {
            position: relative;
        }

        .suggest-list {
            position: absolute;
            z-index: 50;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            margin-top: .15rem;
            max-height: 260px;
            overflow: auto;
        }

        .suggest-item {
            padding: .45rem .6rem;
            cursor: pointer;
        }

        .suggest-item:hover,
        .suggest-item.active {
            background: #f1f3f5;
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

        /* Address 4-up grid (Province ▸ City/Town ▸ Barangay ▸ Details) */
        .addr-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        @media (min-width: 992px) {
            .addr-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        /* Small helper text blocks */
        .hint {
            font-size: .85rem;
            color: #6c757d;
        }

        .note {
            font-size: .9rem;
        }

        .note.success {
            color: #0a7e2f;
        }

        .note.warn {
            color: #a15c00;
        }

        .note.danger {
            color: #a40000;
        }

        .fieldset-soft {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            padding: .75rem;
        }

        /* Old-Student view visibility helper */
        .d-none {
            display: none !important;
        }

        /* Error list */
        #formErrors ul {
            margin: 0;
            padding-left: 1rem;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-overlay .loading-box {
            background: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: .75rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, .15);
            text-align: center;
            max-width: 260px;
        }

        .loading-spinner {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            border: .35rem solid #dee2e6;
            border-top-color: #0d6efd;
            animation: spin 0.75s linear infinite;
            margin: 0 auto .75rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    @php
        // Safe fallbacks for AJAX endpoints used by the JS
        $searchUrl = \Illuminate\Support\Facades\Route::has('admin.students.search')
            ? route('admin.students.search')
            : url('/admin/students/search');
        $prefillBase = \Illuminate\Support\Facades\Route::has('admin.students.prefill')
            ? preg_replace('/\/\d+$/', '', route('admin.students.prefill', ['id' => 0])) // strip example id
            : url('/admin/students/prefill');

        // PSGC APIs (regions + provinces) – kept for compatibility, not used in new PSGC GitLab dropdown
        $psgcRegionsUrl = \Illuminate\Support\Facades\Route::has('api.psgc.regions')
            ? route('api.psgc.regions')
            : url('/api/psgc/regions');

        $psgcProvincesUrl = \Illuminate\Support\Facades\Route::has('api.psgc.provinces')
            ? route('api.psgc.provinces')
            : url('/api/psgc/provinces');
    @endphp

    <div class="container-fluid py-3">
        <div class="enroll-page">

            <div class="enroll-actions no-print">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

                {{-- Reset button --}}
                <button type="button" id="resetFormBtn" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>

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

                {{-- Error box (AJAX validation) --}}
                <div id="formErrors" class="alert alert-danger d-none">
                    <strong>There were some problems with your input:</strong>
                    <ul class="mb-0"></ul>
                </div>

                {{-- FORM --}}
                <form id="enrollForm" action="{{ route('admin.students.store') }}" method="POST" class="needs-validation"
                    novalidate data-redirect="{{ route('admin.students.index') }}" data-search-url="{{ $searchUrl }}"
                    data-prefill-base="{{ rtrim($prefillBase, '/') }}" data-psgc-regions="{{ $psgcRegionsUrl }}"
                    data-psgc-provinces="{{ $psgcProvincesUrl }}">
                    @csrf
                    <input type="hidden" name="auto_create_login" value="1">
                    <input type="hidden" id="picked_student_id" name="picked_student_id" value="">

                    {{-- Enrollment Type + LRN --}}
                    <div class="fieldset-soft mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Enrollment Type</label>
                                <select id="enroll_type" name="enroll_type" class="form-select" required>
                                    <option value="new" {{ old('enroll_type', 'new') === 'new' ? 'selected' : '' }}>New
                                        Enrollee (Default)</option>
                                    <option value="old" {{ old('enroll_type') === 'old' ? 'selected' : '' }}>Old Student /
                                        Returnee</option>
                                    <option value="transferee" {{ old('enroll_type') === 'transferee' ? 'selected' : '' }}>
                                        Transferee</option>
                                </select>
                                <div class="hint mt-1">Choose how this learner is enrolling.</div>
                            </div>

                            <div class="col-md-4 suggest-wrap">
                                <label class="form-label fw-semibold">LRN (Learner Reference Number)</label>
                                <input type="text" class="form-control" id="lrn" name="lrn" inputmode="numeric"
                                    pattern="\d{10,12}" maxlength="12" placeholder="Enter 10–12 digits"
                                    value="{{ old('lrn') }}" required>
                                <div id="suggest_lrn" class="suggest-list d-none"></div>
                                <div class="hint mt-1">LRN is required for ALL enrollment types.</div>
                                @error('lrn')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Old Student helper --}}
                            <div class="col-md-4 d-none" id="oldStudentHelper">
                                <label class="form-label fw-semibold">Old Student Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="old_retain_chk">
                                    <label class="form-check-label" for="old_retain_chk">
                                        Retain same grade (allowed to re-enroll same grade)
                                    </label>
                                </div>
                                <div id="oldEligibilityNote" class="note mt-1"></div>
                            </div>
                        </div>

                        {{-- Transferee extra fields --}}
                        <div id="transfereeFields" class="row g-2 mt-2 d-none">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Previous School</label>
                                <input type="text" class="form-control" id="prev_school_proxy"
                                    placeholder="Type previous school name">
                                <div class="hint mt-1">This will also fill the “Previous School” field below.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Previous Grade Level</label>
                                <select id="prev_grade" class="form-select">
                                    <option value="">—</option>
                                    <option>Nursery</option>
                                    <option>Kindergarten 1</option>
                                    <option>Kindergarten 2</option>
                                    @for ($i = 1; $i <= 6; $i++)
                                        <option>Grade {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">GWA (General Weighted Average)</label>
                                <input type="number" class="form-control" id="prev_gwa" min="60" max="100" step="0.01"
                                    placeholder="e.g., 84.5">
                                <div class="hint mt-1">GWA ≥ 75 → proceed to next grade; GWA ≤ 74 → retained.</div>
                            </div>
                            <div class="col-12">
                                <div id="transEligibilityNote" class="note mt-1"></div>
                            </div>
                        </div>
                    </div>
                    {{-- /Enrollment Type + LRN --}}

<div class="mb-3">
    <label class="form-label fw-semibold">School Year</label>
    <input type="text" class="form-control" value="{{ $current->school_year ?? '' }}" disabled>

    @if($current)
        {{-- These are actually submitted with the form --}}
        <input type="hidden" name="schoolyr_id" value="{{ $current->id }}">
        <input type="hidden" name="school_year" value="{{ $current->school_year }}">
    @endif
</div>
                    {{-- Learner’s section --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">LEARNER’S NAME</label>
                            <div class="row g-2">
                                <div class="col-md-4 suggest-wrap">
                                    <input type="text" class="form-control" name="s_firstname" id="s_firstname"
                                        placeholder="FIRST NAME" value="{{ old('s_firstname') }}" required
                                        autocomplete="off">
                                    <div id="suggest_first" class="suggest-list d-none"></div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_middlename" id="s_middlename"
                                        value="{{ old('s_middlename') }}" placeholder="MIDDLE NAME (optional)"
                                        autocomplete="off">
                                </div>
                                <div class="col-md-4 suggest-wrap">
                                    <input type="text" class="form-control" name="s_lastname" id="s_lastname"
                                        placeholder="LAST NAME" value="{{ old('s_lastname') }}" required autocomplete="off">
                                    <div id="suggest_last" class="suggest-list d-none"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Basic details kept visible even in Old view --}}
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender" id="s_gender">
                                <option value="">—</option>
                                <option value="Male" {{ old('s_gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('s_gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Prefer not to say" {{ old('s_gender') === 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_birthdate" id="s_birthdate"
                                value="{{ old('s_birthdate') }}" required>
                        </div>

                        {{-- old-hide: hide these in Old view --}}
                        <div class="col-md-3 old-hide">
                            <label class="form-label">Citizenship</label>
                            <input type="text" class="form-control" name="s_citizenship" id="s_citizenship"
                                list="citizenshipsList" placeholder="Choose or type"
                                value="{{ old('s_citizenship', 'Filipino') }}" required>
                            <datalist id="citizenshipsList">
                                <option value="Filipino"></option>
                                <option value="American"></option>
                                <option value="Australian"></option>
                                <option value="British"></option>
                                <option value="Canadian"></option>
                                <option value="Chinese"></option>
                                <option value="French"></option>
                                <option value="German"></option>
                                <option value="Indian"></option>
                                <option value="Indonesian"></option>
                                <option value="Italian"></option>
                                <option value="Japanese"></option>
                                <option value="Korean"></option>
                                <option value="Malaysian"></option>
                                <option value="Singaporean"></option>
                                <option value="Spanish"></option>
                                <option value="Thai"></option>
                                <option value="Vietnamese"></option>
                                <option value="Other"></option>
                            </datalist>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" id="s_age" placeholder="Auto" readonly>
                        </div>

                        {{-- Address (PH cascade + details) (hide in Old view) --}}
                        <div class="col-12 old-hide">
                            <label class="form-label">Address (Philippines)</label>
                            <div class="addr-grid">
                                <div>
                                    <select id="addr_province" class="form-select" aria-label="Province">
                                        <option value="">Province</option>
                                    </select>
                                </div>
                                <div>
                                    <select id="addr_city" class="form-select" aria-label="City / Town" disabled>
                                        <option value="">City / Town</option>
                                    </select>
                                </div>
                                <div>
                                    <select id="addr_barangay" class="form-select" aria-label="Barangay" disabled>
                                        <option value="">Barangay</option>
                                    </select>
                                </div>
                                <div>
                                    <input type="text" id="addr_details" class="form-control"
                                        placeholder="Street / Sitio / House # (optional)" autocomplete="off">
                                </div>
                            </div>
                            {{-- Keep this enabled even in OLD mode so it submits --}}
                            <input type="hidden" name="s_address" id="s_address" value="{{ old('s_address') }}"
                                data-keep-enabled="1">
                            <div class="form-text">We’ll assemble the full address for you.</div>
                        </div>

                        <div class="col-md-3 old-hide">
                            <label class="form-label">Religion</label>
                            <input type="text" class="form-control" name="s_religion" id="s_religion" list="religionsList"
                                placeholder="Choose or type" value="{{ old('s_religion') }}">
                            <datalist id="religionsList">
                                <option value="Roman Catholic"></option>
                                <option value="Protestant"></option>
                                <option value="Evangelical"></option>
                                <option value="Iglesia ni Cristo"></option>
                                <option value="Aglipayan (IFI)"></option>
                                <option value="Seventh-day Adventist"></option>
                                <option value="Jehovah’s Witnesses"></option>
                                <option value="Born Again Christian"></option>
                                <option value="Baptist"></option>
                                <option value="Methodist"></option>
                                <option value="Pentecostal"></option>
                                <option value="Lutheran"></option>
                                <option value="Orthodox"></option>
                                <option value="Islam"></option>
                                <option value="Buddhism"></option>
                                <option value="Hinduism"></option>
                                <option value="Judaism"></option>
                                <option value="Non-religious / None"></option>
                                <option value="Other"></option>
                            </datalist>
                        </div>

                        <div class="col-md-3 old-hide">
                            <label class="form-label">Contact No. (optional)</label>
                            <input type="text" class="form-control" name="s_contact" id="s_contact"
                                value="{{ old('s_contact') }}">
                        </div>

                        <div class="col-md-6 old-hide">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" name="s_email" id="s_email"
                                value="{{ old('s_email') }}">
                        </div>

                        <div class="col-md-6 old-hide">
                            <label class="form-label">Does the learner have Special Education needs or disabilities?</label>
                            <div class="input-group">
                                <select class="form-select" id="sped_has" name="sped_has" aria-label="SPED has">
                                    <option value="">—</option>
                                    <option value="Yes" {{ old('sped_has') === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('sped_has', 'No') === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <input type="text" class="form-control" id="sped_desc" name="sped_desc"
                                    placeholder="If yes, please specify." value="{{ old('sped_desc') }}" {{ old('sped_has', 'No') === 'Yes' ? '' : 'disabled' }}>
                            </div>
                        </div>

                        {{-- Grade Level block: visible for New/Old; hidden for Transferee --}}
                        <div id="gradeLevelBlock" class="col-md-6">
                            <label class="form-label">GRADE LEVEL TO ENROLL</label>
                            <select name="s_gradelvl" id="s_gradelvl" class="form-select" required>
                                <option value="">-</option>
                                <option value="Nursery" {{ old('s_gradelvl') === 'Nursery' ? 'selected' : '' }}>Nursery
                                </option>
                                <option value="Kindergarten 1" {{ old('s_gradelvl') === 'Kindergarten 1' ? 'selected' : '' }}>
                                    Kindergarten 1</option>
                                <option value="Kindergarten 2" {{ old('s_gradelvl') === 'Kindergarten 2' ? 'selected' : '' }}>
                                    Kindergarten 2</option>
                                @for ($i = 1; $i <= 6; $i++)
                                    <option value="Grade {{ $i }}" {{ old('s_gradelvl') === 'Grade ' . $i ? 'selected' : '' }}>
                                        Grade {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <div class="hint mt-1" id="gradeHint"></div>
                        </div>

                        <div class="col-md-6 old-hide">
                            <label class="form-label">Name of the previous school (if applicable)</label>
                            <input type="text" class="form-control" name="previous_school" id="previous_school"
                                value="{{ old('previous_school') }}" placeholder="N/A if none">
                        </div>
                    </div> {{-- /row --}}

                    {{-- Optional Fees --}}
                    @if (!empty($optionalFees) && $optionalFees->count())
                        <hr class="my-4 old-hide">
                        <div class="old-hide" id="optionalFeesBlock">
                            <label class="form-label fw-semibold">Optional Fees</label>
                            <div class="row g-2">
                                @foreach ($optionalFees as $fee)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="opt_fee_{{ $fee->id }}"
                                                name="student_optional_fee_ids[]" value="{{ $fee->id }}" {{ collect(old('student_optional_fee_ids', []))->contains($fee->id) ? 'checked' : '' }}>
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
                                    <option value="{{ $g->id }}" {{ old('guardian_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->display_name }} — {{ $g->display_contact }}
                                    </option>
                                @endforeach
                                <option value="new" {{ old('guardian_id') === 'new' ? 'selected' : '' }}>➕ Add New Parents /
                                    Guardian</option>
                            </select>
                        </div>

                        {{-- New Guardian fields (still appear if user picks "new", even in Old view) --}}
                        <div id="newGuardianFields" class="{{ old('guardian_id') === 'new' ? '' : 'd-none' }}">
                            {{-- Household Address --}}
                            <div class="col-12">
                                <label class="form-label">Household Address</label>
                                <input type="text" name="g_address" class="form-control" id="g_address"
                                    value="{{ old('g_address') }}" placeholder="Same as student address? Tick below">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="sameAddress">
                                    <label class="form-check-label" for="sameAddress">Same as student’s address</label>
                                </div>
                            </div>

                            {{-- Mother --}}
                            <div class="col-12">
                                <h6 class="mt-3 mb-1">Mother</h6>
                            </div>
                            <div class="col-md-4"><input type="text" name="m_firstname" id="m_firstname"
                                    class="form-control" value="{{ old('m_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="m_middlename" id="m_middlename"
                                    class="form-control" value="{{ old('m_middlename') }}"
                                    placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="m_lastname" id="m_lastname" class="form-control"
                                    value="{{ old('m_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="m_contact" id="m_contact" class="form-control"
                                    value="{{ old('m_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="m_email" id="m_email" class="form-control"
                                    value="{{ old('m_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="m_occupation" id="m_occupation"
                                    class="form-control" value="{{ old('m_occupation') }}"
                                    placeholder="Occupation (print only)"></div>

                            {{-- Father --}}
                            <div class="col-12">
                                <h6 class="mt-3 mb-1">Father</h6>
                            </div>
                            <div class="col-md-4"><input type="text" name="f_firstname" id="f_firstname"
                                    class="form-control" value="{{ old('f_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="f_middlename" id="f_middlename"
                                    class="form-control" value="{{ old('f_middlename') }}"
                                    placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="f_lastname" id="f_lastname" class="form-control"
                                    value="{{ old('f_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="f_contact" id="f_contact" class="form-control"
                                    value="{{ old('f_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="f_email" id="f_email" class="form-control"
                                    value="{{ old('f_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="f_occupation" id="f_occupation"
                                    class="form-control" value="{{ old('f_occupation') }}"
                                    placeholder="Occupation (print only)"></div>

                            {{-- In case with a guardian (full name, contact, relation) --}}
                            <div class="col-12">
                                <label class="form-label mt-2">If learner is with a guardian, please indicate
                                    details</label>
                                <input type="text" name="alt_guardian_details" id="alt_guardian_details"
                                    class="form-control" value="{{ old('alt_guardian_details') }}"
                                    placeholder="Full name, contact no., relation (print only)">
                            </div>
                        </div> {{-- /#newGuardianFields --}}
                    </div> {{-- /row --}}

                    <hr class="my-4">

                    {{-- Consent --}}
                    <div>
                        <p class="mb-2">
                            As the parent (or legal guardian) of the above-named learner, I hereby consent to his/her
                            enrollment at
                            <strong>INTELLIWISE GRACE CHRISTIAN ACADEMY</strong>. In addition to such consent, I hereby
                            acknowledge
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
                                will execute legal actions against me, therefore I will make sure to pay all fees within the
                                said school year.</li>
                        </ol>

                        <div class="d-flex justify-content-end">
                            <div class="signature-line">(Signature over printed name)</div>
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
                {{-- /FORM --}}
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div id="loadingOverlay" class="loading-overlay no-print">
        <div class="loading-box">
            <div class="loading-spinner"></div>
            <div>Saving enrollment record…</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.needs-validation');
            const errorBox = document.getElementById('formErrors');
            const errorList = errorBox?.querySelector('ul');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // ---- Elements
            const enrollTypeSel = document.getElementById('enroll_type');
            const oldHelper = document.getElementById('oldStudentHelper');
            const oldRetainChk = document.getElementById('old_retain_chk');
            const oldEligibility = document.getElementById('oldEligibilityNote');

            const transfWrap = document.getElementById('transfereeFields');
            const prevSchoolProxy = document.getElementById('prev_school_proxy');
            const prevGradeSel = document.getElementById('prev_grade');
            const prevGwaInput = document.getElementById('prev_gwa');
            const transNote = document.getElementById('transEligibilityNote');

            const gradeBlock = document.getElementById('gradeLevelBlock');
            const sGradeSel = document.getElementById('s_gradelvl');
            const gradeHint = document.getElementById('gradeHint');

            const oldHideEls = document.querySelectorAll('.old-hide');

            const sFirst = document.getElementById('s_firstname');
            const sMiddle = document.getElementById('s_middlename');
            const sLast = document.getElementById('s_lastname');
            const lrnInput = document.getElementById('lrn');
            const sBirth = document.getElementById('s_birthdate');
            const sAge = document.getElementById('s_age');
            const sGender = document.getElementById('s_gender');

            const spedHas = document.getElementById('sped_has');
            const spedDesc = document.getElementById('sped_desc');

            // Address controls (Province ▸ City / Town ▸ Barangay ▸ Details)
            const addrProvince = document.getElementById('addr_province');
            const addrCity = document.getElementById('addr_city');
            const addrBarangay = document.getElementById('addr_barangay');
            const addrDetails = document.getElementById('addr_details');
            const sAddressHidden = document.getElementById('s_address');

            const previousSchool = document.getElementById('previous_school');

            const guardianSel = document.getElementById('guardian_id');
            const newGuardFields = document.getElementById('newGuardianFields');
            const sameAddrChk = document.getElementById('sameAddress');
            const gAddress = document.getElementById('g_address');

            const pickedStudentId = document.getElementById('picked_student_id');

            // Suggestions UI & endpoints
            const suggestFirstBox = document.getElementById('suggest_first');
            const suggestLastBox = document.getElementById('suggest_last');
            const suggestLrnBox = document.getElementById('suggest_lrn');
            const searchUrl = form.dataset.searchUrl || '';
            const prefillBase = (form.dataset.prefillBase || '').replace(/\/$/, '');

            function showErrors(map) {
                if (!errorBox || !errorList) return;
                errorList.innerHTML = '';
                const items = [];
                for (const key in map) {
                    const msgs = Array.isArray(map[key]) ? map[key] : [String(map[key])];
                    msgs.forEach(m => items.push(`<li>${m}</li>`));
                }
                if (!items.length) {
                    errorBox.classList.add('d-none');
                    return;
                }
                errorList.innerHTML = items.join('');
                errorBox.classList.remove('d-none');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // ---------- Helpers
            function setDisabledGroup(groupEls, disabled) {
                groupEls.forEach(el => {
                    el.classList.toggle('d-none', disabled);
                    el.querySelectorAll('input, select, textarea').forEach(ctrl => {
                        // keep some controls enabled even when visually hidden
                        const keep = ctrl.dataset.keepEnabled === '1';
                        if (disabled && !keep) {
                            ctrl.setAttribute('data-prev-disabled', ctrl.disabled ? '1' : '');
                            ctrl.disabled = true;
                        } else if (!disabled) {
                            const flag = ctrl.getAttribute('data-prev-disabled');
                            ctrl.disabled = flag === '1';
                            ctrl.removeAttribute('data-prev_disabled');
                            ctrl.removeAttribute('data-prev-disabled');
                        }
                    });
                });
            }

            function calcAge(isoDate) {
                if (!isoDate) { sAge.value = ''; return; }
                const b = new Date(isoDate);
                if (isNaN(b.getTime())) { sAge.value = ''; return; }
                const today = new Date();
                let age = today.getFullYear() - b.getFullYear();
                const m = today.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
                sAge.value = age >= 0 ? age : '';
            }

            // Compose full address from Province, City/Town, Barangay + details
            function assembleAddress() {
                const prov = addrProvince?.options[addrProvince.selectedIndex]?.text || '';
                const city = addrCity?.options[addrCity.selectedIndex]?.text || '';
                const brgy = addrBarangay?.options[addrBarangay.selectedIndex]?.text || '';
                const det = (addrDetails?.value || '').trim();

                const parts = [];
                if (det) parts.push(det);
                if (brgy) parts.push(brgy);
                if (city) parts.push(city);
                if (prov) parts.push(prov);

                sAddressHidden.value = parts.join(', ');
                syncGuardianAddress();
            }

            function clearSuggestLists() {
                [suggestFirstBox, suggestLastBox, suggestLrnBox].forEach(box => {
                    if (!box) return;
                    box.classList.add('d-none');
                    box.innerHTML = '';
                });
            }

            function textToGradeValue(txt) {
                const allowed = [
                    'Nursery', 'Kindergarten 1', 'Kindergarten 2',
                    'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'
                ];
                if (allowed.includes(txt)) return txt;
                const t = (txt || '').toLowerCase();
                if (/nursery/.test(t)) return 'Nursery';
                if (/kindergarten\s*1|k1/.test(t)) return 'Kindergarten 1';
                if (/kindergarten\s*2|k2/.test(t)) return 'Kindergarten 2';
                const m = t.match(/grade\s*(\d)/);
                if (m && m[1]) return `Grade ${m[1]}`;
                return '';
            }

            // ---------- Enrollment type toggle
            function updateEnrollTypeUI() {
                const t = enrollTypeSel.value;
                if (t === 'old') {
                    // Old student:
                    oldHelper.classList.remove('d-none');
                    transfWrap.classList.add('d-none');
                    gradeBlock.classList.remove('d-none');

                    setDisabledGroup(oldHideEls, true);

                    // Ensure s_address is included in submit even if block hidden
                    sAddressHidden.disabled = false;

                    sGradeSel.disabled = false;
                    gradeHint.textContent = 'Returning student: search by name or LRN to autofill, then choose grade to re-enroll. Tick "retain same grade" if repeating.';
                    oldEligibility.textContent = oldRetainChk?.checked
                        ? 'Retain same grade checked: student will repeat the same grade level.'
                        : 'Unchecked: set the next appropriate grade level in the selector.';
                } else if (t === 'transferee') {
                    // Transferee:
                    oldHelper.classList.add('d-none');
                    transfWrap.classList.remove('d-none');
                    gradeBlock.classList.add('d-none'); // hidden per requirement
                    setDisabledGroup(oldHideEls, false); // Collect complete data for transferees

                    sGradeSel.disabled = false;
                    gradeHint.textContent = '';
                    computeTransfereeTarget();

                    // Clear any old-mode picks
                    pickedStudentId.value = '';
                    clearSuggestLists();
                } else {
                    // New
                    oldHelper.classList.add('d-none');
                    transfWrap.classList.add('d-none');
                    gradeBlock.classList.remove('d-none');
                    setDisabledGroup(oldHideEls, false);

                    sGradeSel.disabled = false;
                    gradeHint.textContent = 'For new enrollee: pick intended grade level.';

                    pickedStudentId.value = '';
                    clearSuggestLists();
                }
            }

            function computeTransfereeTarget() {
                const prevGradeText = prevGradeSel.value || '';
                const gwa = parseFloat(prevGwaInput.value || '');
                const normalizedPrev = textToGradeValue(prevGradeText);
                if (!normalizedPrev) {
                    transNote.className = 'note';
                    transNote.textContent = 'Select the previous grade and (optionally) provide GWA to auto-suggest the target grade.';
                    return;
                }
                const isAdvance = !isNaN(gwa) ? (gwa >= 75) : true;
                const mapOrder = ['Nursery', 'Kindergarten 1', 'Kindergarten 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
                const idx = mapOrder.indexOf(normalizedPrev);
                let target = normalizedPrev;
                if (isAdvance && idx >= 0 && idx < mapOrder.length - 1) {
                    target = mapOrder[idx + 1];
                    transNote.className = 'note success';
                    transNote.textContent = `Eligible to advance based on provided GWA. Target grade set to ${target}.`;
                } else if (!isAdvance) {
                    transNote.className = 'note warn';
                    transNote.textContent = `GWA indicates retention. Target grade set to ${target}.`;
                } else {
                    transNote.className = 'note warn';
                    transNote.textContent = `Highest grade reached. Target grade remains ${target}.`;
                }
                sGradeSel.value = target;
            }

            // ---------- SPED toggle
            function updateSped() {
                const has = (spedHas.value || '').toLowerCase() === 'yes';
                spedDesc.disabled = !has;
                if (!has) spedDesc.value = '';
            }

            // ---------- Guardian toggle
            function updateGuardianUI() {
                const v = guardianSel.value || '';
                const isNew = v === 'new';
                newGuardFields.classList.toggle('d-none', !isNew);
            }

            // Same address checkbox
            function syncGuardianAddress() {
                if (sameAddrChk && gAddress && sameAddrChk.checked) {
                    gAddress.value = sAddressHidden.value || '';
                }
            }

            function syncGuardianCheckboxChange() {
                if (sameAddrChk.checked) {
                    gAddress.value = sAddressHidden.value || '';
                }
            }

            // ---------- Suggestions (OLD students only) ----------
            function buildSearchList(box, list) {
                if (!box) return;
                if (!Array.isArray(list) || !list.length) { box.classList.add('d-none'); box.innerHTML = ''; return; }
                box.innerHTML = list.map(item => {
                    const id = item.id || item.lrn || '';
                    const name = item.name || [item.s_firstname, item.s_middlename, item.s_lastname].filter(Boolean).join(' ') || '';
                    const label = [name, id ? `(LRN: ${id})` : ''].filter(Boolean).join(' ');
                    return `<div class="suggest-item" data-id="${String(id)}">${label}</div>`;
                }).join('');
                box.classList.remove('d-none');
            }

            async function searchStudents(term) {
                if (!searchUrl || !term || term.length < 2) return [];
                const qs = new URLSearchParams({ q: term });
                try {
                    const res = await fetch(`${searchUrl}?${qs.toString()}`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return [];
                    const data = await res.json();
                    return Array.isArray(data) ? data : [];
                } catch { return []; }
            }

            async function prefillStudentById(id) {
                if (!id || !prefillBase) return;
                try {
                    const res = await fetch(`${prefillBase}/${encodeURIComponent(id)}`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const rec = await res.json();

                    // Fill core fields
                    if (rec.s_firstname) sFirst.value = rec.s_firstname;
                    if (rec.s_middlename !== undefined) sMiddle.value = rec.s_middlename || '';
                    if (rec.s_lastname) sLast.value = rec.s_lastname;
                    if (rec.id) lrnInput.value = rec.id;

                    if (rec.s_birthdate) { sBirth.value = (rec.s_birthdate || '').substring(0, 10); calcAge(sBirth.value); }
                    if (rec.s_gender) sGender.value = rec.s_gender;

                    // Hidden/old-hide values (kept enabled for submit where needed)
                    if (rec.s_address) {
                        sAddressHidden.value = rec.s_address;
                        tryPrefillAddressFromFull(rec.s_address);
                    }
                    if (rec.s_religion) document.getElementById('s_religion').value = rec.s_religion;
                    if (rec.s_contact) document.getElementById('s_contact').value = rec.s_contact;
                    if (rec.s_email) document.getElementById('s_email').value = rec.s_email;
                    if (rec.s_citizenship) document.getElementById('s_citizenship').value = rec.s_citizenship;

                    if (rec.sped_has) spedHas.value = rec.sped_has;
                    if (rec.sped_desc) spedDesc.value = rec.sped_desc;
                    updateSped();

                    // Grade level (user can still change it)
                    if (rec.s_gradelvl) {
                        const gv = textToGradeValue(rec.s_gradelvl);
                        if (gv) sGradeSel.value = gv;
                    }

                    if (rec.previous_school) previousSchool.value = rec.previous_school;

                    // Guardian (select existing)
                    if (rec.guardian_id) {
                        guardianSel.value = String(rec.guardian_id);
                        updateGuardianUI();
                    }

                    pickedStudentId.value = rec.id || id;
                } catch {
                    // ignore
                }
            }

            function wireSuggestBox(box) {
                if (!box) return;
                box.addEventListener('click', async (e) => {
                    const item = e.target.closest('.suggest-item');
                    if (!item) return;
                    const id = item.getAttribute('data-id') || '';
                    clearSuggestLists();
                    if (id) {
                        await prefillStudentById(id);
                        // Focus next logical field
                        sBirth.focus();
                    }
                });
            }

            // ---------- PSGC GitLab-based cascade (Province ▸ City/Town ▸ Barangay)
            const PSGC_API = 'https://psgc.gitlab.io/api';
            const LS_PREFIX = 'psgc_cache_v1_';

            async function psgcCached(key, url) {
                const k = LS_PREFIX + key;
                try {
                    const raw = localStorage.getItem(k);
                    if (raw) {
                        const { ts, data } = JSON.parse(raw);
                        if (Date.now() - ts < 14 * 24 * 60 * 60 * 1000) {
                            return data;
                        }
                    }
                } catch { }
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                try { localStorage.setItem(k, JSON.stringify({ ts: Date.now(), data })); } catch { }
                return data;
            }

            const listProvinces = () => psgcCached('provinces', `${PSGC_API}/provinces/`);
            const listCitiesMunsOfProvince = (provCode) =>
                psgcCached(`prov_${provCode}_cm`, `${PSGC_API}/provinces/${provCode}/cities-municipalities/`);
            const listBarangaysOf = (code, isCity) =>
                psgcCached(`${isCity ? 'city' : 'mun'}_${code}_brgys`, `${PSGC_API}/${isCity ? 'cities' : 'municipalities'}/${code}/barangays/`);
            const byName = (a, b) => a.name.localeCompare(b.name);

            const Address = {
                ready: (async function init() {
                    if (!addrProvince) return;
                    addrProvince.innerHTML = '<option value="">Province</option>';
                    addrProvince.disabled = true;
                    try {
                        const provs = (await listProvinces()).sort(byName);
                        provs.forEach(p => {
                            const o = document.createElement('option');
                            o.value = p.code;
                            o.textContent = p.name;
                            addrProvince.appendChild(o);
                        });
                    } catch (e) {
                        // silent
                    }
                    addrProvince.disabled = false;
                })(),
                async setByNames(provName, cityName, brgyName) {
                    if (!addrProvince) return;
                    if (!provName) return;
                    await this.ready;

                    // Province
                    let provCode = '';
                    Array.from(addrProvince.options).forEach(o => {
                        if ((o.textContent || '').trim().toLowerCase() === provName.trim().toLowerCase()) {
                            provCode = o.value;
                        }
                    });
                    if (!provCode) return;

                    addrProvince.value = provCode;
                    addrProvince.dispatchEvent(new Event('change'));

                    // Cities/Municipalities
                    addrCity.innerHTML = '<option value="">City / Town</option>';
                    addrCity.disabled = true;
                    const cms = (await listCitiesMunsOfProvince(provCode)).sort(byName);
                    cms.forEach(cm => {
                        const isCity = /city/i.test(cm.classification);
                        const o = document.createElement('option');
                        o.value = `${cm.code}|${isCity ? 'city' : 'mun'}`;
                        o.textContent = cm.name;
                        addrCity.appendChild(o);
                    });
                    addrCity.disabled = false;

                    if (!cityName) return;
                    let cmCode = '';
                    let isCityFlag = false;
                    Array.from(addrCity.options).forEach(o => {
                        if ((o.textContent || '').trim().toLowerCase() === cityName.trim().toLowerCase()) {
                            const [code, kind] = o.value.split('|');
                            cmCode = code;
                            isCityFlag = (kind === 'city');
                            addrCity.value = o.value;
                        }
                    });
                    addrCity.dispatchEvent(new Event('change'));

                    if (!cmCode) return;
                    addrBarangay.innerHTML = '<option value="">Barangay</option>';
                    addrBarangay.disabled = true;
                    const brgys = (await listBarangaysOf(cmCode, isCityFlag)).sort(byName);
                    brgys.forEach(b => {
                        const o = document.createElement('option');
                        o.value = b.code;
                        o.textContent = b.name;
                        addrBarangay.appendChild(o);
                    });
                    addrBarangay.disabled = false;

                    if (brgyName) {
                        Array.from(addrBarangay.options).forEach(o => {
                            if ((o.textContent || '').trim().toLowerCase() === brgyName.trim().toLowerCase()) {
                                addrBarangay.value = o.value;
                            }
                        });
                    }
                    assembleAddress();
                }
            };

            function tryPrefillAddressFromFull(full) {
                if (!full) { assembleAddress(); return; }
                const parts = full.split(',').map(s => s.trim()).filter(Boolean);
                const provinceName = parts.length ? parts[parts.length - 1] : '';
                const cityName = parts.length >= 2 ? parts[parts.length - 2] : '';
                const brgyName = parts.length >= 3 ? parts[parts.length - 3] : '';
                const detail = parts.slice(0, Math.max(0, parts.length - 3)).join(', ');

                if (addrDetails) addrDetails.value = detail || '';
                Address.setByNames(provinceName || '', cityName || '', brgyName || '');
            }

            // ---------- Reset button
            function resetForm() {
                form.reset();
                pickedStudentId.value = '';
                clearSuggestLists();
                showErrors({});
                sAge.value = '';
                updateSped();
                updateGuardianUI();
                updateEnrollTypeUI();

                if (addrDetails) addrDetails.value = '';
                if (sAddressHidden) sAddressHidden.value = '';

                Address.ready.then(() => {
                    if (addrProvince) {
                        addrProvince.selectedIndex = 0;
                    }
                    if (addrCity) {
                        addrCity.innerHTML = '<option value="">City / Town</option>';
                        addrCity.disabled = true;
                    }
                    if (addrBarangay) {
                        addrBarangay.innerHTML = '<option value="">Barangay</option>';
                        addrBarangay.disabled = true;
                    }
                    assembleAddress();
                });

                if (loadingOverlay) loadingOverlay.classList.remove('active');
            }

            const resetBtn = document.getElementById('resetFormBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', resetForm);
            }

            // ---------- Wire events

            // Type toggle
            enrollTypeSel.addEventListener('change', updateEnrollTypeUI);
            if (oldRetainChk) {
                oldRetainChk.addEventListener('change', () => {
                    oldEligibility.textContent = oldRetainChk.checked
                        ? 'Retain same grade checked: student will repeat the same grade level.'
                        : 'Unchecked: set the next appropriate grade level in the selector.';
                });
            }

            // Transferee fields
            prevGradeSel.addEventListener('change', computeTransfereeTarget);
            prevGwaInput.addEventListener('input', computeTransfereeTarget);
            prevSchoolProxy.addEventListener('input', () => { previousSchool.value = prevSchoolProxy.value; });

            // SPED
            spedHas.addEventListener('change', updateSped);

            // Age compute
            sBirth.addEventListener('change', () => calcAge(sBirth.value));

            // Guardian toggle
            guardianSel.addEventListener('change', updateGuardianUI);
            if (sameAddrChk) {
                sameAddrChk.addEventListener('change', syncGuardianCheckboxChange);
            }

            // Address cascade + assembly
            if (addrProvince) {
                addrProvince.addEventListener('change', async () => {
                    assembleAddress();
                    const provCode = addrProvince.value;
                    addrCity.innerHTML = '<option value="">City / Town</option>';
                    addrBarangay.innerHTML = '<option value="">Barangay</option>';
                    addrCity.disabled = true;
                    addrBarangay.disabled = true;
                    if (!provCode) return;

                    try {
                        const cms = (await listCitiesMunsOfProvince(provCode)).sort(byName);
                        cms.forEach(cm => {
                            const isCity = /city/i.test(cm.classification);
                            const o = document.createElement('option');
                            o.value = `${cm.code}|${isCity ? 'city' : 'mun'}`;
                            o.textContent = cm.name;
                            addrCity.appendChild(o);
                        });
                        addrCity.disabled = false;
                    } catch (e) { }
                });
            }

            if (addrCity) {
                addrCity.addEventListener('change', async () => {
                    assembleAddress();
                    addrBarangay.innerHTML = '<option value="">Barangay</option>';
                    addrBarangay.disabled = true;
                    if (!addrCity.value) return;

                    const [code, kind] = addrCity.value.split('|');
                    const isCityFlag = (kind === 'city');
                    try {
                        const brgys = (await listBarangaysOf(code, isCityFlag)).sort(byName);
                        brgys.forEach(b => {
                            const o = document.createElement('option');
                            o.value = b.code;
                            o.textContent = b.name;
                            addrBarangay.appendChild(o);
                        });
                        addrBarangay.disabled = false;
                    } catch (e) { }
                });
            }

            if (addrBarangay) {
                addrBarangay.addEventListener('change', assembleAddress);
            }
            if (addrDetails) {
                addrDetails.addEventListener('input', assembleAddress);
            }

            // ------- OLD student suggestions: FIRST / LAST / LRN inputs
            let tFirst = 0, tLast = 0, tLrn = 0;

            async function handleSuggest(inputEl, boxEl) {
                const mode = enrollTypeSel.value;
                if (mode !== 'old') { clearSuggestLists(); return; }
                const term = (inputEl.value || '').trim();
                const list = await searchStudents(term);
                buildSearchList(boxEl, list);
            }

            sFirst.addEventListener('input', () => {
                clearTimeout(tFirst); pickedStudentId.value = '';
                tFirst = setTimeout(() => handleSuggest(sFirst, suggestFirstBox), 180);
            });
            sLast.addEventListener('input', () => {
                clearTimeout(tLast); pickedStudentId.value = '';
                tLast = setTimeout(() => handleSuggest(sLast, suggestLastBox), 180);
            });
            lrnInput.addEventListener('input', () => {
                clearTimeout(tLrn); pickedStudentId.value = '';
                tLrn = setTimeout(() => handleSuggest(lrnInput, suggestLrnBox), 180);
            });

            [sFirst, sLast, lrnInput].forEach(el => {
                el.addEventListener('blur', () => setTimeout(clearSuggestLists, 150));
            });

            wireSuggestBox(suggestFirstBox);
            wireSuggestBox(suggestLastBox);
            wireSuggestBox(suggestLrnBox);

            // Initial loads
            updateSped();
            updateGuardianUI();
            updateEnrollTypeUI();
            if (sBirth.value) calcAge(sBirth.value);

            Address.ready.then(() => {
                const oldFull = sAddressHidden.value || '';
                if (oldFull) {
                    tryPrefillAddressFromFull(oldFull);
                } else {
                    assembleAddress();
                }
            });

            /* ======================
               Submit: AJAX then redirect to Students page
               ====================== */
            form.addEventListener('submit', function (e) {
                if (!form.checkValidity()) {
                    e.preventDefault(); e.stopPropagation();
                    form.classList.add('was-validated');
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus({ preventScroll: true });
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }

                e.preventDefault();
                if (form.dataset.submitting === '1') return; // prevent double-clicks
                form.dataset.submitting = '1';
                errorBox?.classList.add('d-none');
                if (errorList) errorList.innerHTML = '';

                assembleAddress(); // ensure latest address
                if (loadingOverlay) loadingOverlay.classList.add('active');

                const fd = new FormData(form);
                const enrolledLrn = (fd.get('lrn') || '').toString().trim();
                const redirectBase = form.dataset.redirect || '/';
                const redirectUrl = new URL(redirectBase, window.location.origin);
                if (enrolledLrn) redirectUrl.searchParams.set('enrolled', enrolledLrn);

                fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(async (r) => {
                        if (r.ok) {
                            // Let overlay remain while page redirects
                            window.location.href = redirectUrl.toString();
                            return;
                        }
                        if (r.status === 422) {
                            const data = await r.json().catch(() => ({}));
                            showErrors(data.errors || { error: ['Please correct the highlighted fields.'] });
                            form.dataset.submitting = '';
                            if (loadingOverlay) loadingOverlay.classList.remove('active');
                            return;
                        }
                        // Any other status: fall back to normal submit (server-driven redirect)
                        form.dataset.submitting = '';
                        // keep overlay on while full page submit happens
                        form.submit();
                    })
                    .catch(() => {
                        form.dataset.submitting = '';
                        // keep overlay on while full page submit happens
                        form.submit();
                    });
            }, false);
        });
    </script>
@endpush
