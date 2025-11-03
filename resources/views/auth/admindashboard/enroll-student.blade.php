{{-- resources/views/auth/admindashboard/enroll-student.blade.php --}}
@extends('layouts.admin')

@section('title', 'Enrollment Form')

@push('styles')
    <style>
        /* Page styling */
        .enroll-page { max-width: 900px; margin: 0 auto; }
        .school-header { text-align: center; line-height: 1.3; margin-bottom: 1rem; }
        .school-header h3 { margin: 0; font-weight: 700; }
        .school-header .sub { color: #6c757d; }
        .enroll-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-bottom: .75rem; }
        .enroll-card { border: 1px solid #dee2e6; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }

        /* Suggest dropdown */
        .suggest-wrap { position: relative; }
        .suggest-list {
            position: absolute; z-index: 50; top: 100%; left: 0; right: 0;
            background: #fff; border: 1px solid #ced4da; border-radius: .375rem;
            box-shadow: 0 6px 18px rgba(0,0,0,.08); margin-top: .15rem; max-height: 260px; overflow:auto;
        }
        .suggest-item { padding: .45rem .6rem; cursor: pointer; }
        .suggest-item:hover, .suggest-item.active { background: #f1f3f5; }

        /* Print layout: A4, hide chrome/nav */
        @page { size: A4; margin: 14mm; }
        @media print {
            .sidebar, .no-print, .flash-messages, .btn, .navbar, .card .card-header .btn { display: none !important; }
            body { background: #fff !important; }
            .enroll-card { border: none !important; box-shadow: none !important; }
        }

        /* Form controls print nicely (show underline + value) */
        .form-control, .form-select, textarea.form-control { border: 1px solid #ced4da; }
        @media print {
            .form-control, .form-select, textarea.form-control {
                border: none !important; border-bottom: 1px solid #000 !important; border-radius: 0 !important;
                padding-left: 0 !important; padding-right: 0 !important;
            }
            label.form-label, .form-floating>label { color:#000 !important; opacity:1 !important; }
            .form-floating>label { position: static; transform:none; }
            .form-floating>.form-control, .form-floating>.form-select { height:auto; padding:0; }
        }

        .signature-line { border-top: 1px solid #000; width: 320px; margin-top: 2.25rem; text-align: center; padding-top: .25rem; font-size: .95rem; }

        /* Address 4-up grid */
        .addr-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:.75rem; }
        @media (min-width: 992px) {
            .addr-grid { grid-template-columns: repeat(4, minmax(0,1fr)); }
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
                    Please make sure to submit a <strong>CLEAR COPY</strong> of the <strong>CHILD’S BIRTH CERTIFICATE</strong>.
                </div>

                <form action="{{ route('admin.students.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <input type="hidden" name="auto_create_login" value="1">

                    {{-- Learner’s section --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">LEARNER’S NAME</label>

                            {{-- Suggestion search binds to FIRST and LAST name fields. --}}
                            <div class="row g-2">
                                <div class="col-md-4 suggest-wrap">
                                    <input type="text" class="form-control" name="s_firstname" id="s_firstname"
                                           placeholder="FIRST NAME" value="{{ old('s_firstname') }}" required autocomplete="off">
                                    <div id="suggest_first" class="suggest-list d-none"></div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_middlename" id="s_middlename"
                                           value="{{ old('s_middlename') }}" placeholder="MIDDLE NAME (optional)" autocomplete="off">
                                </div>
                                <div class="col-md-4 suggest-wrap">
                                    <input type="text" class="form-control" name="s_lastname" id="s_lastname"
                                           placeholder="LAST NAME" value="{{ old('s_lastname') }}" required autocomplete="off">
                                    <div id="suggest_last" class="suggest-list d-none"></div>
                                </div>
                            </div>

                            {{-- Selected existing student id (if user picked from suggestions) --}}
                            <input type="hidden" id="picked_student_id" name="picked_student_id" value="">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender" id="s_gender">
                                <option value="">—</option>
                                <option value="Male"   {{ old('s_gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('s_gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Prefer not to say" {{ old('s_gender') === 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_birthdate" id="s_birthdate"
                                   value="{{ old('s_birthdate') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Citizenship</label>
                            <input type="text"
                                   class="form-control"
                                   name="s_citizenship"
                                   id="s_citizenship"
                                   list="citizenshipsList"
                                   placeholder="Choose or type"
                                   value="{{ old('s_citizenship', 'Filipino') }}"
                                   required>
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

                        {{-- Address (PH cascade + details) --}}
                        <div class="col-12">
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
                                    <input type="text" id="addr_details" class="form-control" placeholder="Street / Sitio / House #" autocomplete="off">
                                </div>
                            </div>
                            {{-- Hidden combined field used by backend --}}
                            <input type="hidden" name="s_address" id="s_address" value="{{ old('s_address') }}">
                            <div class="form-text">We’ll assemble the full address for you.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Religion</label>
                            <input type="text" class="form-control" name="s_religion" id="s_religion"
                                   list="religionsList" placeholder="Choose or type"
                                   value="{{ old('s_religion') }}">
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

                        <div class="col-md-3">
                            <label class="form-label">Contact No. (optional)</label>
                            <input type="text" class="form-control" name="s_contact" id="s_contact"
                                   value="{{ old('s_contact') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" name="s_email" id="s_email"
                                   value="{{ old('s_email') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Does the learner have Special Education needs or disabilities?</label>
                            <div class="input-group">
                                <select class="form-select" id="sped_has" name="sped_has" aria-label="SPED has">
                                    <option value="">—</option>
                                    <option value="Yes" {{ old('sped_has') === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No"  {{ old('sped_has', 'No') === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <input type="text" class="form-control" id="sped_desc" name="sped_desc"
                                       placeholder="If yes, please specify."
                                       value="{{ old('sped_desc') }}"
                                       {{ old('sped_has', 'No') === 'Yes' ? '' : 'disabled' }}>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GRADE LEVEL TO ENROLL</label>
                            <select name="s_gradelvl" id="s_gradelvl" class="form-select" required>
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
                            <input type="text" class="form-control" name="previous_school" id="previous_school"
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
                                    <option value="{{ $g->id }}" {{ old('guardian_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->display_name }} — {{ $g->display_contact }}
                                    </option>
                                @endforeach
                                <option value="new" {{ old('guardian_id') === 'new' ? 'selected' : '' }}>➕ Add New Parents / Guardian</option>
                            </select>
                        </div>

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
                            <div class="col-12"><h6 class="mt-3 mb-1">Mother</h6></div>
                            <div class="col-md-4"><input type="text" name="m_firstname" id="m_firstname" class="form-control" value="{{ old('m_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="m_middlename" id="m_middlename" class="form-control" value="{{ old('m_middlename') }}" placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="m_lastname" id="m_lastname" class="form-control" value="{{ old('m_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="m_contact" id="m_contact" class="form-control" value="{{ old('m_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="m_email" id="m_email" class="form-control" value="{{ old('m_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="m_occupation" id="m_occupation" class="form-control" value="{{ old('m_occupation') }}" placeholder="Occupation (print only)"></div>

                            {{-- Father --}}
                            <div class="col-12"><h6 class="mt-3 mb-1">Father</h6></div>
                            <div class="col-md-4"><input type="text" name="f_firstname" id="f_firstname" class="form-control" value="{{ old('f_firstname') }}" placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="f_middlename" id="f_middlename" class="form-control" value="{{ old('f_middlename') }}" placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="f_lastname" id="f_lastname" class="form-control" value="{{ old('f_lastname') }}" placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="f_contact" id="f_contact" class="form-control" value="{{ old('f_contact') }}" placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="f_email" id="f_email" class="form-control" value="{{ old('f_email') }}" placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="f_occupation" id="f_occupation" class="form-control" value="{{ old('f_occupation') }}" placeholder="Occupation (print only)"></div>

                            {{-- In case with a guardian (full name, contact, relation) --}}
                            <div class="col-12">
                                <label class="form-label mt-2">If learner is with a guardian, please indicate details</label>
                                <input type="text" name="alt_guardian_details" id="alt_guardian_details" class="form-control"
                                       value="{{ old('alt_guardian_details') }}" placeholder="Full name, contact no., relation (print only)">
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
                            <li>The school will have the right to hold off documents and important records in case of failure in settling school payables.</li>
                            <li>The learner will be provided with all the necessary equipment for distant learning.</li>
                            <li>To be open and available for communication with regards to matters about the learner, his studies, and other school activities.</li>
                            <li>I will be liable, and I am aware that failure to settle my financial obligations, the school will execute legal actions against me, therefore I will make sure to pay all fees within the said school year.</li>
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
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form.needs-validation');

    /* ======================
       Helpers / Validation
       ====================== */
    function applyRequiredMessage(el) {
        if (!el) return;
        el.addEventListener('invalid', function () {
            if (el.validity.valueMissing) el.setCustomValidity('You are required to fill this field');
        });
        ['input','change'].forEach(evt => el.addEventListener(evt, () => el.setCustomValidity('')));
    }
    form.querySelectorAll('[required]').forEach(applyRequiredMessage);

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
    birth.addEventListener('change', calcAge); calcAge();

    spedHas.addEventListener('change', () => {
        const yes = spedHas.value === 'Yes';
        spedDesc.disabled = !yes;
        if (!yes) spedDesc.value = '';
    });

    /* ======================
       Guardian block logic
       ====================== */
    const guardianSelect = document.getElementById('guardian_id');
    const newGuardianFields = document.getElementById('newGuardianFields');
    const sameAddress = document.getElementById('sameAddress');
    const requiredWhenNew = [
        'input[name="g_address"]',
        'input[name="m_firstname"]','input[name="m_lastname"]','input[name="m_contact"]',
        'input[name="f_firstname"]','input[name="f_lastname"]','input[name="f_contact"]',
    ];
    applyRequiredMessage(guardianSelect);

    function setRequiredForNewGuardian(isNew) {
        requiredWhenNew.forEach(sel => {
            const el = newGuardianFields.querySelector(sel);
            if (!el) return;
            if (isNew) { el.setAttribute('required','required'); applyRequiredMessage(el); }
            else { el.removeAttribute('required'); el.setCustomValidity(''); }
        });
    }
    function toggleGuardianUI() {
        const isNew = guardianSelect.value === 'new';
        newGuardianFields.classList.toggle('d-none', !isNew);
        if (!isNew) {
            newGuardianFields.querySelectorAll('input').forEach(i => { if (!document.body.dataset.initialized) return; i.value=''; i.setCustomValidity(''); });
        }
        setRequiredForNewGuardian(isNew);
    }
    guardianSelect.addEventListener('change', toggleGuardianUI);

    sameAddress?.addEventListener('change', function () {
        const hhAddress = document.getElementById('g_address');
        const sAddress = document.getElementById('s_address');
        if (this.checked && hhAddress) hhAddress.value = sAddress.value || '';
    });

    /* ======================
       Student suggestions
       ====================== */
    const first = document.getElementById('s_firstname');
    const mid   = document.getElementById('s_middlename');
    const last  = document.getElementById('s_lastname');
    const suggestFirst = document.getElementById('suggest_first');
    const suggestLast  = document.getElementById('suggest_last');
    const pickHidden = document.getElementById('picked_student_id');

    // smarter select match: matches by value OR text, case-insensitive; also maps M/F
    function selectMatch(selectEl, rawVal) {
        if (!selectEl) return;
        const v = String(rawVal ?? '').trim();
        if (v === '') { selectEl.selectedIndex = 0; selectEl.dispatchEvent(new Event('change')); return; }
        const normalized = v.toLowerCase();

        // map common gender variants
        const genderMap = { 'm':'male', 'f':'female', 'boy':'male', 'girl':'female' };
        const target = genderMap[normalized] || normalized;

        let matched = false;
        Array.from(selectEl.options).forEach(opt => {
            const ov = (opt.value ?? '').toString().trim().toLowerCase();
            const ot = (opt.textContent ?? '').toString().trim().toLowerCase();
            if (ov === target || ot === target) {
                opt.selected = true;
                matched = true;
            } else {
                opt.selected = false;
            }
        });
        if (!matched) { selectEl.selectedIndex = 0; }
        selectEl.dispatchEvent(new Event('change'));
    }

    function setVal(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'SELECT') {
            selectMatch(el, value);
        } else {
            // default Filipino when setting citizenship and nothing came from server
            if (id === 's_citizenship' && (!value || String(value).trim() === '')) {
                el.value = 'Filipino';
            } else {
                el.value = value ?? '';
            }
            el.dispatchEvent(new Event('input'));
            el.dispatchEvent(new Event('change'));
        }
    }

    const fillMap = {
        s_middlename:'s_middlename', s_gender:'s_gender', s_birthdate:'s_birthdate',
        s_citizenship:'s_citizenship', s_address:'s_address', s_religion:'s_religion',
        s_contact:'s_contact', s_email:'s_email', sped_has:'sped_has', sped_desc:'sped_desc',
        s_gradelvl:'s_gradelvl', previous_school:'previous_school',
        // guardian
        g_address:'g_address',
        m_firstname:'m_firstname', m_middlename:'m_middlename', m_lastname:'m_lastname', m_contact:'m_contact', m_email:'m_email', m_occupation:'m_occupation',
        f_firstname:'f_firstname', f_middlename:'f_middlename', f_lastname:'f_lastname', f_contact:'f_contact', f_email:'f_email', f_occupation:'f_occupation',
        alt_guardian_details:'alt_guardian_details'
    };

    function debounce(fn, ms=250){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms);} }

    const SEARCH_URL = @json(route('admin.students.search'));
    const PREFILL_URL_TEMPLATE = @json(route('admin.students.prefill', ['id' => 'ID_PLACEHOLDER']));

    async function fetchSuggest(term) {
        if (!term || term.trim().length < 2) return [];
        try {
            const r = await fetch(`${SEARCH_URL}?q=${encodeURIComponent(term)}`, { headers:{'Accept':'application/json'} });
            if (!r.ok) { console.error('Search HTTP error', r.status); return []; }
            const ct = (r.headers.get('content-type') || '').toLowerCase();
            if (!ct.includes('application/json')) return [];
            return await r.json();
        } catch (e) { console.error('Search fetch failed', e); return []; }
    }

    function renderList(el, items) {
        if (!items.length) { el.classList.add('d-none'); el.innerHTML=''; return; }
        el.innerHTML = items.map(i => `<div class="suggest-item" data-id="${i.id}">${i.name}</div>`).join('');
        el.classList.remove('d-none');
    }

    async function handleType(targetInput, listEl) {
        const term = targetInput.value.trim();
        const items = await fetchSuggest(term);
        renderList(listEl, items);
    }

    const onTypeFirst = debounce(()=>handleType(first, suggestFirst), 250);
    const onTypeLast  = debounce(()=>handleType(last, suggestLast), 250);
    first.addEventListener('input', onTypeFirst);
    last.addEventListener('input', onTypeLast);

    async function prefillById(id) {
        pickHidden.value = id;
        const url = PREFILL_URL_TEMPLATE.replace('ID_PLACEHOLDER', encodeURIComponent(id));
        try {
            const r = await fetch(url, { headers:{'Accept':'application/json'} });
            if (!r.ok) { console.error('Prefill HTTP error', r.status); return; }
            const ct = (r.headers.get('content-type') || '').toLowerCase();
            if (!ct.includes('application/json')) { console.warn('Prefill non-JSON response'); return; }
            const data = await r.json();

            // Names first
            setVal('s_firstname',  data.s_firstname);
            setVal('s_middlename', data.s_middlename);
            setVal('s_lastname',   data.s_lastname);

            // Fill the rest
            Object.entries(fillMap).forEach(([serverKey, fieldId])=>{
                if (fieldId === 's_address') {
                    setVal('s_address', data[serverKey]);
                    tryPrefillAddressFromFull(data[serverKey] || '');
                } else {
                    setVal(fieldId, data[serverKey]);
                }
            });

            if (data.guardian_id) {
                setVal('guardian_id', data.guardian_id);
            }
            calcAge();
        } catch (e) {
            console.error('Prefill fetch failed', e);
        }
    }

    function attachPick(listEl) {
        listEl.addEventListener('click', (e)=>{
            const item = e.target.closest('.suggest-item');
            if (!item) return;
            listEl.classList.add('d-none');
            prefillById(item.dataset.id);
        });
    }
    attachPick(suggestFirst);
    attachPick(suggestLast);

    document.addEventListener('click', (e)=>{
        if (!suggestFirst.contains(e.target) && e.target !== first) suggestFirst.classList.add('d-none');
        if (!suggestLast.contains(e.target) && e.target !== last)   suggestLast.classList.add('d-none');
    });

    /* ======================
       Address (PSGC-powered)
       ====================== */
    const addrProvince  = document.getElementById('addr_province');
    const addrCity      = document.getElementById('addr_city');
    const addrBarangay  = document.getElementById('addr_barangay');
    const addrDetails   = document.getElementById('addr_details');
    const sAddressHidden= document.getElementById('s_address');

    // PSGC helper (inline; cached via localStorage)
    const PSGC_API = 'https://psgc.gitlab.io/api';
    const LS_PREFIX = 'psgc_cache_v1_';
    async function psgcCached(key, url) {
        const k = LS_PREFIX + key;
        try {
            const raw = localStorage.getItem(k);
            if (raw) {
                const { ts, data } = JSON.parse(raw);
                if (Date.now() - ts < 14*24*60*60*1000) return data;
            }
        } catch {}
        const res = await fetch(url, { headers: { 'Accept':'application/json' }});
        if (!res.ok) throw new Error('HTTP '+res.status);
        const data = await res.json();
        try { localStorage.setItem(k, JSON.stringify({ ts: Date.now(), data })); } catch {}
        return data;
    }
    const listProvinces = () => psgcCached('provinces', `${PSGC_API}/provinces/`);
    const listCitiesMunsOfProvince = (provCode) => psgcCached(`prov_${provCode}_cm`, `${PSGC_API}/provinces/${provCode}/cities-municipalities/`);
    const listBarangaysOf = (code, isCity) => psgcCached(`${isCity?'city':'mun'}_${code}_brgys`, `${PSGC_API}/${isCity?'cities':'municipalities'}/${code}/barangays/`);
    const byName = (a,b) => a.name.localeCompare(b.name);

    function composeFullAddress() {
        const p = addrProvince.options[addrProvince.selectedIndex]?.text || '';
        const c = addrCity.options[addrCity.selectedIndex]?.text || '';
        const b = addrBarangay.options[addrBarangay.selectedIndex]?.text || '';
        const d = addrDetails.value || '';
        const parts = [];
        if (d) parts.push(d);
        if (b) parts.push(b);
        if (c) parts.push(c);
        if (p) parts.push(p);
        sAddressHidden.value = parts.join(', ');
    }

    // wire selects + controller for preselect
    const Address = {
        ready: (async function init() {
            // Provinces
            addrProvince.innerHTML = '<option value="">Province</option>';
            addrProvince.disabled = true;
            try {
                const provs = (await listProvinces()).sort(byName);
                provs.forEach(p => {
                    const o = document.createElement('option');
                    o.value = p.code; o.textContent = p.name;
                    addrProvince.appendChild(o);
                });
            } catch (e) { /* silent */ }
            addrProvince.disabled = false;
        })(),
        async setByNames(provName, cityName, brgyName) {
            if (!provName) return;
            await this.ready;

            // select province by label
            let provCode = '';
            Array.from(addrProvince.options).forEach(o => {
                if ((o.textContent||'').trim().toLowerCase() === provName.trim().toLowerCase()) provCode = o.value;
            });
            if (!provCode) return;

            addrProvince.value = provCode;
            addrProvince.dispatchEvent(new Event('change'));

            // cities/mun
            addrCity.innerHTML = '<option value="">City / Town</option>';
            addrCity.disabled = true;
            const cms = (await listCitiesMunsOfProvince(provCode)).sort(byName);
            cms.forEach(cm => {
                const isCity = /city/i.test(cm.classification);
                const o = document.createElement('option');
                o.value = `${cm.code}|${isCity?'city':'mun'}`;
                o.textContent = cm.name;
                addrCity.appendChild(o);
            });
            addrCity.disabled = false;

            if (!cityName) return;
            let cmCode = '', isCity = false;
            Array.from(addrCity.options).forEach(o => {
                if ((o.textContent||'').trim().toLowerCase() === cityName.trim().toLowerCase()) {
                    const [code, kind] = o.value.split('|'); cmCode = code; isCity = (kind==='city'); addrCity.value = o.value;
                }
            });
            addrCity.dispatchEvent(new Event('change'));

            // barangays
            if (!cmCode) return;
            addrBarangay.innerHTML = '<option value="">Barangay</option>';
            addrBarangay.disabled = true;
            const brgys = (await listBarangaysOf(cmCode, isCity)).sort(byName);
            brgys.forEach(b => {
                const o = document.createElement('option');
                o.value = b.code; o.textContent = b.name;
                addrBarangay.appendChild(o);
            });
            addrBarangay.disabled = false;

            if (brgyName) {
                Array.from(addrBarangay.options).forEach(o => {
                    if ((o.textContent||'').trim().toLowerCase() === brgyName.trim().toLowerCase()) addrBarangay.value = o.value;
                });
            }
            composeFullAddress();
        }
    };

    addrProvince.addEventListener('change', async () => {
        composeFullAddress();
        const provCode = addrProvince.value;
        addrCity.innerHTML = '<option value="">City / Town</option>';
        addrBarangay.innerHTML = '<option value="">Barangay</option>';
        addrCity.disabled = true; addrBarangay.disabled = true;
        if (!provCode) return;

        try {
            const cms = (await listCitiesMunsOfProvince(provCode)).sort(byName);
            cms.forEach(cm => {
                const isCity = /city/i.test(cm.classification);
                const o = document.createElement('option');
                o.value = `${cm.code}|${isCity?'city':'mun'}`;
                o.textContent = cm.name;
                addrCity.appendChild(o);
            });
            addrCity.disabled = false;
        } catch (e) {
            // leave disabled
        }
    });

    addrCity.addEventListener('change', async () => {
        composeFullAddress();
        addrBarangay.innerHTML = '<option value="">Barangay</option>';
        addrBarangay.disabled = true;
        if (!addrCity.value) return;

        const [code, kind] = addrCity.value.split('|');
        const isCity = (kind === 'city');
        try {
            const brgys = (await listBarangaysOf(code, isCity)).sort(byName);
            brgys.forEach(b => {
                const o = document.createElement('option');
                o.value = b.code; o.textContent = b.name;
                addrBarangay.appendChild(o);
            });
            addrBarangay.disabled = false;
        } catch (e) {}
    });

    addrBarangay.addEventListener('change', composeFullAddress);
    addrDetails.addEventListener('input', composeFullAddress);

    function tryPrefillAddressFromFull(full) {
        if (!full) { composeFullAddress(); return; }
        const parts = full.split(',').map(s => s.trim()).filter(Boolean);
        // heuristics: [details?, barangay?, city?, province?]
        const provinceName = parts.find(p => true) ? parts[parts.length-1] : '';
        const cityName     = parts.length >= 2 ? parts[parts.length-2] : '';
        const brgyName     = parts.length >= 3 ? parts[parts.length-3] : '';
        const detail       = parts.slice(0, Math.max(0, parts.length-3)).join(', ');

        addrDetails.value = detail || '';
        Address.setByNames(provinceName || '', cityName || '', brgyName || '');
    }

    // Load provinces initially
    Address.ready.then(() => {
        // If there was an old s_address (validation fail), prefill widgets
        const oldFull = document.getElementById('s_address').value || '';
        if (oldFull) tryPrefillAddressFromFull(oldFull);
    });

    /* ======================
       Reset button
       ====================== */
    const resetBtn = document.getElementById('resetFormBtn');
    resetBtn?.addEventListener('click', function() {
        form.classList.remove('was-validated');

        form.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.tagName === 'INPUT' && el.type === 'hidden' && el.id !== 'picked_student_id' && el.id !== 's_address') return;

            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            } else {
                el.value = '';
            }
            el.setCustomValidity('');
        });

        // default citizenship back to Filipino
        const citizen = document.getElementById('s_citizenship');
        if (citizen) citizen.value = 'Filipino';

        // optional fees unchecked
        form.querySelectorAll('input[name="student_optional_fee_ids[]"]').forEach(cb => cb.checked = false);

        // guardian block
        guardianSelect.value = '';
        guardianSelect.dispatchEvent(new Event('change'));

        // suggestions hidden
        suggestFirst?.classList.add('d-none');
        suggestLast?.classList.add('d-none');

        // clear picked id & age
        pickHidden.value = '';
        calcAge();

        // SPED desc disabled
        spedDesc.disabled = true;

        // Address reset: reload provinces
        addrDetails.value = '';
        sAddressHidden.value = '';
        Address.ready.then(() => {
            addrProvince.selectedIndex = 0;
            addrCity.innerHTML = '<option value="">City / Town</option>';
            addrBarangay.innerHTML = '<option value="">Barangay</option>';
            addrCity.disabled = true; addrBarangay.disabled = true;
        });
    });

    /* ======================
       Submit validation
       ====================== */
    form.addEventListener('submit', function (e) {
        setRequiredForNewGuardian(guardianSelect.value === 'new');
        composeFullAddress();

        if (!form.checkValidity()) {
            e.preventDefault(); e.stopPropagation();
            form.classList.add('was-validated');
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }, false);

    document.body.dataset.initialized = 'true';
    toggleGuardianUI();

    // Ensure default citizenship if empty on load
    const citizen = document.getElementById('s_citizenship');
    if (citizen && (!citizen.value || citizen.value.trim() === '')) citizen.value = 'Filipino';
});
</script>
@endpush
