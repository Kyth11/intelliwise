@extends('layouts.faculty')

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
                <a href="{{ route('faculty.dashboard') }}" class="btn btn-outline-secondary">
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
                    <strong>CAPITAL letters</strong>. For items not applicable, write <strong>N/A</strong>. Please make sure
                    to
                    submit a <strong>CLEAR COPY</strong> of the <strong>CHILD’S BIRTH CERTIFICATE</strong>.
                </div>

                {{-- IMPORTANT: post to faculty route --}}
                <form action="{{ route('faculty.students.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf

                    {{-- Flash + Validation --}}
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

                    {{-- Learner’s section --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">LEARNER’S NAME</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_firstname" placeholder="FIRST NAME"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_middlename"
                                        placeholder="MIDDLE NAME (optional)">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="s_lastname" placeholder="LAST NAME"
                                        required>
                                </div>
                            </div>
                        </div>



                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender" required>
                                <option value="">—</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Prefer not to say</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_birthdate" id="s_birthdate" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Citizenship</label>
                            <input type="text" class="form-control" name="s_citizenship" placeholder="e.g., Filipino"
                                required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" id="s_age" placeholder="Auto" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="s_address" id="s_address" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Religion</label>
                            <input type="text" class="form-control" name="s_religion" placeholder="e.g., Christian">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Contact No. (optional)</label>
                            <input type="text" class="form-control" name="s_contact">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" name="s_email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Does the learner have Special Education needs or disabilities?</label>
                            <div class="input-group">
                                <select class="form-select" id="sped_has" aria-label="SPED has">
                                    <option value="">—</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No" selected>No</option>
                                </select>
                                <input type="text" class="form-control" id="sped_desc" placeholder="If yes, please specify."
                                    disabled>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GRADE LEVEL TO ENROLL</label>
                            <select name="s_gradelvl" class="form-select" required>
                                <option value="">-</option>
                                <option value="Nursery">Nursery</option>
                                <option value="Kindergarten 1">Kindergarten 1</option>
                                <option value="Kindergarten 2">Kindergarten 2</option>
                                @for($i = 1; $i <= 6; $i++)
                                    <option value="Grade {{ $i }}">Grade {{ $i }}</option>
                                @endfor
                            </select>
                            {{-- If you prefer dynamic list from $gradelvls, swap the block above. --}}
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Name of the previous school (if applicable)</label>
                            <input type="text" class="form-control" name="previous_school" placeholder="N/A if none">
                        </div>
                    </div> {{-- end row g-3 --}}

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
                                @foreach($guardians as $g)
                                    <option value="{{ $g->id }}">{{ $g->display_name }} — {{ $g->display_contact }}</option>
                                @endforeach
                                <option value="new">➕ Add New Parents / Guardian</option>
                            </select>
                        </div>

                        <div id="newGuardianFields" class="d-none">
                            {{-- Household Address --}}
                            <div class="col-12">
                                <label class="form-label">Household Address</label>
                                <input type="text" name="g_address" class="form-control"
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
                                    placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="m_middlename" class="form-control"
                                    placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="m_lastname" class="form-control"
                                    placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="m_contact" class="form-control"
                                    placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="m_email" class="form-control"
                                    placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="m_occupation" class="form-control"
                                    placeholder="Occupation (print only)"></div>

                            {{-- Father --}}
                            <div class="col-12">
                                <h6 class="mt-3 mb-1">Father</h6>
                            </div>
                            <div class="col-md-4"><input type="text" name="f_firstname" class="form-control"
                                    placeholder="First Name"></div>
                            <div class="col-md-4"><input type="text" name="f_middlename" class="form-control"
                                    placeholder="Middle Name (optional)"></div>
                            <div class="col-md-4"><input type="text" name="f_lastname" class="form-control"
                                    placeholder="Last Name"></div>
                            <div class="col-md-6"><input type="text" name="f_contact" class="form-control"
                                    placeholder="Contact"></div>
                            <div class="col-md-6"><input type="email" name="f_email" class="form-control"
                                    placeholder="Email (optional)"></div>
                            <div class="col-md-6"><input type="text" name="f_occupation" class="form-control"
                                    placeholder="Occupation (print only)"></div>

                            {{-- If with a guardian --}}
                            <div class="col-12">
                                <label class="form-label mt-2">If learner is with a guardian, please indicate
                                    details</label>
                                <input type="text" name="alt_guardian_details" class="form-control"
                                    placeholder="Full name, contact no., relation (print only)">
                            </div>

                            {{-- Single login for parents --}}
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hasLogin" name="has_login">
                                    <label class="form-check-label" for="hasLogin">Create Parent/Guardian Login Account
                                        (single account)</label>
                                </div>
                            </div>
                            <div id="guardianLoginFields" class="row g-2 d-none">
                                <div class="col-md-6"><input type="text" name="username" class="form-control"
                                        placeholder="Username"></div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="password" name="password" id="guardianPassword" class="form-control"
                                            placeholder="Password">
                                        <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="guardianPassword">👁</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Consent --}}
                    <div>
                        <p class="mb-2">
                            As the parent (or legal guardian) of the above-named learner, I hereby consent to his/her
                            enrollment at
                            <strong>Barra Grace Christian School</strong>. In addition to such consent, I hereby acknowledge
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
                        <button class="btn btn-success" type="submit"><i class="bi bi-check-circle me-1"></i> Save</button>
                        <button class="btn btn-outline-secondary" type="button" onclick="window.print()"><i
                                class="bi bi-printer me-1"></i> Print</button>
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

    // === Helper: required messages ===
    function applyRequiredMessage(el) {
        if (!el) return;
        el.addEventListener('invalid', function () {
            if (el.validity.valueMissing) el.setCustomValidity('You are required to fill this field');
        });
        ['input','change'].forEach(evt => el.addEventListener(evt, () => el.setCustomValidity('')));
    }
    form.querySelectorAll('[required]').forEach(applyRequiredMessage);

    // Refs
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

    // Guardian block logic
    const guardianSelect = document.getElementById('guardian_id');
    const newGuardianFields = document.getElementById('newGuardianFields');
    const sameAddress = document.getElementById('sameAddress');
    const studentAddress = document.getElementById('s_address');
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
        if (this.checked && hhAddress) hhAddress.value = studentAddress.value;
    });

    // === Student suggestions ===
    const first = document.getElementById('s_firstname');
    const last  = document.getElementById('s_lastname');
    const suggestFirst = document.getElementById('suggest_first');
    const suggestLast  = document.getElementById('suggest_last');
    const pickHidden = document.getElementById('picked_student_id');

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

    // Build URLs from named routes (robust)
    const SEARCH_URL = @json(route('admin.students.search'));
    const PREFILL_URL_TEMPLATE = @json(route('admin.students.prefill', ['id' => 'ID_PLACEHOLDER']));

    async function fetchSuggest(term) {
        if (!term || term.trim().length < 2) return [];
        try {
            const r = await fetch(`${SEARCH_URL}?q=${encodeURIComponent(term)}`, { headers:{'Accept':'application/json'} });
            if (!r.ok) { console.error('Search HTTP error', r.status); return []; }
            const ct = (r.headers.get('content-type') || '').toLowerCase();
            if (!ct.includes('application/json')) {
                console.warn('Search returned non-JSON'); return [];
            }
            return await r.json();
        } catch (e) {
            console.error('Search fetch failed', e);
            return [];
        }
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
            if (!ct.includes('application/json')) {
                const txt = await r.text();
                console.warn('Prefill non-JSON response. First 200 chars:', txt.slice(0,200));
                alert('Could not prefill. Check if you are logged in and the route is accessible.');
                return;
            }
            const data = await r.json();

            // Names first
            if (data.s_firstname) first.value = data.s_firstname || '';
            if (data.s_middlename) document.getElementById('s_middlename').value = data.s_middlename || '';
            if (data.s_lastname)   last.value  = data.s_lastname  || '';

            // Fill the rest
            Object.entries(fillMap).forEach(([serverKey, fieldId])=>{
                const el = document.getElementById(fieldId);
                if (!el) return;
                const val = (data[serverKey] ?? '');
                if (el.tagName === 'SELECT') {
                    Array.from(el.options).forEach(o=>o.selected = (o.value === String(val)));
                    el.dispatchEvent(new Event('change'));
                } else {
                    el.value = val;
                    el.dispatchEvent(new Event('input'));
                    el.dispatchEvent(new Event('change'));
                }
            });

            // Switch guardian select to existing if we got an id
            if (data.guardian_id) {
                guardianSelect.value = data.guardian_id;
                guardianSelect.dispatchEvent(new Event('change'));
            }
            calcAge();
        } catch (e) {
            console.error('Prefill fetch failed', e);
            alert('Prefill failed. See console for details.');
        }
    }

    function attachPick(listEl) {
        listEl.addEventListener('click', (e)=>{
            const item = e.target.closest('.suggest-item');
            if (!item) return;
            listEl.classList.add('d-none');
            const id = item.dataset.id;
            prefillById(id);
        });
    }
    attachPick(suggestFirst);
    attachPick(suggestLast);

    // Close lists on outside click
    document.addEventListener('click', (e)=>{
        if (!suggestFirst.contains(e.target) && e.target !== first) suggestFirst.classList.add('d-none');
        if (!suggestLast.contains(e.target) && e.target !== last)   suggestLast.classList.add('d-none');
    });

    // Submit validation + scroll
    form.addEventListener('submit', function (e) {
        setRequiredForNewGuardian(guardianSelect.value === 'new');
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
});
</script>
@endpush
