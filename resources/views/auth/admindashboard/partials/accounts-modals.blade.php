@extends('layouts.admin')

@section('title', 'Manage Accounts')

@section('content')
<div class="card section p-4">
    <h4>Manage Accounts</h4>
    <p>Here you can add, edit, or delete system users.</p>

    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="bi bi-person-badge me-2"></i> Add Faculty Account
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGuardianModal">
            <i class="bi bi-people-fill me-2"></i> Add Guardian Account
        </button>
    </div>

    {{-- =========================
         FACULTY ACCOUNTS
       ========================= --}}
    <div class="card mt-3 p-3">
        <h5>Faculty Accounts</h5>
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
            <tr>
                <th>Full Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Email</th>
                <th>Username</th>
                <th>Created At</th>
                <th>Tools</th>
            </tr>
            </thead>
            <tbody>
            @forelse($faculties as $f)
                <tr>
                    <td>{{ $f->f_firstname }} {{ $f->f_middlename }} {{ $f->f_lastname }}</td>
                    <td>{{ $f->f_contact }}</td>
                    <td>{{ $f->f_address }}</td>
                    <td>{{ $f->f_email ?? '-' }}</td>
                    <td>{{ $f->user->username ?? '-' }}</td>
                    <td>{{ $f->created_at->format('Y-m-d') }}</td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editFacultyModal"
                                data-id="{{ $f->id }}"
                                data-firstname="{{ $f->f_firstname }}"
                                data-middlename="{{ $f->f_middlename }}"
                                data-lastname="{{ $f->f_lastname }}"
                                data-contact="{{ $f->f_contact }}"
                                data-address="{{ $f->f_address }}"
                                data-email="{{ $f->f_email }}"
                                data-username="{{ $f->user->username ?? '' }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form action="{{ route('faculties.destroy', $f->id) }}" method="POST" class="d-inline delete-form">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-sm btn-danger delete-btn">
                                <i class="bi bi-archive"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No faculty accounts found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- =========================
         GUARDIAN ACCOUNTS
       ========================= --}}
    <div class="card mt-4 p-3">
        <h5>Guardian Accounts</h5>
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-success">
            <tr>
                <th>Parents / Guardian</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Email</th>
                <th>Username</th>
                <th>Created At</th>
                <th>Tools</th>
            </tr>
            </thead>
            <tbody>
            @forelse($guardians as $g)
                @php
                    // --- Build full names (safe/trimmed) ---
                    $motherFull = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
                    $fatherFull = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));

                    // Derive "Mr. & Mrs." style if parents share last name
                    $mLast = trim((string) $g->m_lastname);
                    $fLast = trim((string) $g->f_lastname);
                    $familyLast = $fLast ?: $mLast;
                    $refFirst   = trim((string) ($g->f_firstname ?: $g->m_firstname)); // prefer father's first name

                    $haveBothParents = $motherFull !== '' && $fatherFull !== '';
                    $shareLast = $fLast !== '' && $mLast !== '' && strcasecmp($fLast, $mLast) === 0;

                    if ($haveBothParents && $shareLast && $refFirst && $familyLast) {
                        $parentsDisplay = "Mr. & Mrs. {$refFirst} {$familyLast}";
                    } elseif ($haveBothParents) {
                        $parentsDisplay = "{$motherFull} & {$fatherFull}";
                    } else {
                        $parentsDisplay = $motherFull ?: $fatherFull ?: '';
                    }

                    // --- Guardian display name: prefer explicit guardian_name, then g_* names, then linked user name ---
                    $gNames = trim(collect([$g->g_firstname, $g->g_middlename, $g->g_lastname])->filter()->implode(' '));
                    $guardianDisplayName = trim(
                        collect([$g->guardian_name ?? null, $gNames ?: null, optional($g->user)->name ?: null])
                        ->filter()
                        ->first() ?? ''
                    );

                    // Final row header label
                    $rowLabel = trim($parentsDisplay);
                    if ($guardianDisplayName !== '') {
                        $rowLabel = $rowLabel !== '' ? ($rowLabel . ' / ' . $guardianDisplayName . ' (Guardian)') : ($guardianDisplayName . ' (Guardian)');
                    }

                    // Convenience display fields
                    $displayContact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: '—'));
                    $displayEmail   = $g->g_email   ?: ($g->m_email   ?: ($g->f_email   ?: '—'));
                    $address        = $g->g_address ?: '—';
                    $username       = optional($g->user)->username ?? '—';
                @endphp

                <tr>
                    <td>
                        <div class="fw-semibold">{{ $rowLabel !== '' ? $rowLabel : 'Guardian #'.$g->id }}</div>
                        @if($motherFull || $fatherFull)
                            <div class="small text-muted">
                                @if($motherFull) Mother: {{ $motherFull }}@endif
                                @if($motherFull && $fatherFull) &nbsp;|&nbsp; @endif
                                @if($fatherFull) Father: {{ $fatherFull }}@endif
                            </div>
                        @endif
                    </td>
                    <td>{{ $displayContact }}</td>
                    <td>{{ $address }}</td>
                    <td>{{ $displayEmail }}</td>
                    <td>{{ $username }}</td>
                    <td>{{ $g->created_at?->format('Y-m-d') }}</td>
                    <td class="text-nowrap">
                        {{-- Use dashed AND provide max info so modal can populate robustly --}}
                        <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editGuardianModal"
                                data-id="{{ $g->id }}"
                                {{-- guardian (if present in db or via user name) --}}
                                data-guardian-name="{{ $guardianDisplayName }}"
                                data-g-firstname="{{ $g->g_firstname }}"
                                data-g-middlename="{{ $g->g_middlename }}"
                                data-g-lastname="{{ $g->g_lastname }}"
                                data-g-contact="{{ $g->g_contact }}"
                                data-g-address="{{ $g->g_address }}"
                                data-g-email="{{ $g->g_email }}"
                                {{-- parents for fallback --}}
                                data-m-firstname="{{ $g->m_firstname }}" data-m-middlename="{{ $g->m_middlename }}" data-m-lastname="{{ $g->m_lastname }}"
                                data-m-contact="{{ $g->m_contact }}" data-m-email="{{ $g->m_email }}"
                                data-f-firstname="{{ $g->f_firstname }}" data-f-middlename="{{ $g->f_middlename }}" data-f-lastname="{{ $g->f_lastname }}"
                                data-f-contact="{{ $g->f_contact }}" data-f-email="{{ $g->f_email }}"
                                data-username="{{ optional($g->user)->username ?? '' }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <form action="{{ route('guardians.destroy', $g->id) }}" method="POST" class="d-inline delete-form">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-sm btn-danger delete-btn">
                                <i class="bi bi-archive"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No guardian accounts found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add modals (keep your existing partial if any) --}}
@includeIf('auth.admindashboard.partials.accounts-add-modals')

{{-- =========================
     EDIT MODALS (inline)
   ========================= --}}

{{-- Edit Faculty Modal --}}
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editFacultyForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #476DA3, #3C5F8E); color:#fff;">
                    <h5 class="modal-title">Edit Faculty Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">First Name</label>
                            <input type="text" class="form-control" name="f_firstname" id="ef_firstname">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Middle Name</label>
                            <input type="text" class="form-control" name="f_middlename" id="ef_middlename">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Last Name</label>
                            <input type="text" class="form-control" name="f_lastname" id="ef_lastname">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Contact</label>
                            <input type="text" class="form-control" name="f_contact" id="ef_contact">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Address</label>
                            <input type="text" class="form-control" name="f_address" id="ef_address">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Email</label>
                            <input type="email" class="form-control" name="f_email" id="ef_email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Username</label>
                            <input type="text" class="form-control" name="username" id="ef_username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">New Password (leave blank to keep current)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="ef_password" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="ef_toggle_pwd">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Faculty</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Guardian Modal --}}
<div class="modal fade" id="editGuardianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editGuardianForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #476DA3, #3C5F8E); color:#fff;">
                    <h5 class="modal-title">Edit Guardian Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">First Name (Guardian)</label>
                            <input type="text" class="form-control" name="g_firstname" id="eg_g_firstname">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Middle Name (Guardian)</label>
                            <input type="text" class="form-control" name="g_middlename" id="eg_g_middlename">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Last Name (Guardian)</label>
                            <input type="text" class="form-control" name="g_lastname" id="eg_g_lastname">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Contact</label>
                            <input type="text" class="form-control" name="g_contact" id="eg_g_contact">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Address</label>
                            <input type="text" class="form-control" name="g_address" id="eg_g_address">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Email</label>
                            <input type="email" class="form-control" name="g_email" id="eg_g_email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Username</label>
                            <input type="text" class="form-control" name="username" id="eg_username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">New Password (leave blank to keep current)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="eg_password" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="eg_toggle_pwd">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Optional: show computed display (read-only) --}}
                        <div class="col-12">
                            <label class="form-label small">Computed Display</label>
                            <input type="text" class="form-control" id="eg_computed_display" readonly>
                            <div class="form-text">Shown as “Mr. &amp; Mrs.” when possible; appends “/ {Guardian} (Guardian)” if available.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- NOTE: Your controller currently writes g_* columns; if your DB lacks them, saving will error. --}}
                    <button type="submit" class="btn btn-primary">Update Guardian</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// --- delete confirm ---
document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        const form = this.closest('form');
        Swal.fire({
            title: 'Are you sure to delete this account?',
            text: "You can't undo this action.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, proceed',
            reverseButtons: true,
            background: '#fff',
            backdrop: false
        }).then(res => { if (res.isConfirmed) form.submit(); });
    });
});

// helpers
const v    = x => (x ?? '');
const pick = (...vals) => vals.find(s => !!(s && s.trim && s.trim().length)) || '';
const ds   = (d, ...keys) => { for(const k of keys){ if(d[k] !== undefined){ return d[k]; } } return ''; };

// toggle pw
document.addEventListener('click', e=>{
    if(e.target.closest('#ef_toggle_pwd')){
        const inp = document.getElementById('ef_password');
        const isPwd = inp.type === 'password';
        inp.type = isPwd ? 'text' : 'password';
        e.target.closest('button').innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }
    if(e.target.closest('#eg_toggle_pwd')){
        const inp = document.getElementById('eg_password');
        const isPwd = inp.type === 'password';
        inp.type = isPwd ? 'text' : 'password';
        e.target.closest('button').innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }
});

// FACULTY modal populate
document.getElementById('editFacultyModal')
.addEventListener('show.bs.modal', function(ev){
    const btn = ev.relatedTarget; if(!btn) return; const d = btn.dataset;
    const form = document.getElementById('editFacultyForm');
    form.action = "{{ route('faculties.update', ':id') }}".replace(':id', ds(d,'id'));

    document.getElementById('ef_firstname').value = v(ds(d,'firstname'));
    document.getElementById('ef_middlename').value = v(ds(d,'middlename'));
    document.getElementById('ef_lastname').value  = v(ds(d,'lastname'));
    document.getElementById('ef_contact').value   = v(ds(d,'contact'));
    document.getElementById('ef_address').value   = v(ds(d,'address'));
    document.getElementById('ef_email').value     = v(ds(d,'email'));
    document.getElementById('ef_username').value  = v(ds(d,'username'));
    document.getElementById('ef_password').value  = '';
});

// GUARDIAN modal populate + computed display preview
document.getElementById('editGuardianModal')
.addEventListener('show.bs.modal', function(ev){
    const btn = ev.relatedTarget; if(!btn) return; const d = btn.dataset;
    const form = document.getElementById('editGuardianForm');
    form.action = "{{ route('guardians.update', ':id') }}".replace(':id', ds(d,'id'));

    // fill guardian inputs (fall back mother/father)
    const gFirst  = pick(v(ds(d,'gFirstname','g_firstname')), v(ds(d,'mFirstname','m_firstname')), v(ds(d,'fFirstname','f_firstname')));
    const gMiddle = pick(v(ds(d,'gMiddlename','g_middlename')), v(ds(d,'mMiddlename','m_middlename')), v(ds(d,'fMiddlename','f_middlename')));
    const gLast   = pick(v(ds(d,'gLastname','g_lastname')), v(ds(d,'mLastname','m_lastname')), v(ds(d,'fLastname','f_lastname')));

    document.getElementById('eg_g_firstname').value = gFirst;
    document.getElementById('eg_g_middlename').value = gMiddle;
    document.getElementById('eg_g_lastname').value = gLast;

    document.getElementById('eg_g_contact').value = pick(v(ds(d,'gContact','g_contact')), v(ds(d,'mContact','m_contact')), v(ds(d,'fContact','f_contact')));
    document.getElementById('eg_g_address').value = v(ds(d,'gAddress','g_address'));
    document.getElementById('eg_g_email').value   = pick(v(ds(d,'gEmail','g_email')), v(ds(d,'mEmail','m_email')), v(ds(d,'fEmail','f_email')));
    document.getElementById('eg_username').value  = v(ds(d,'username'));
    document.getElementById('eg_password').value  = '';

    // compute display preview: "Mr. & Mrs." if possible + guardian suffix
    const mFirst = v(ds(d,'mFirstname','m_firstname')), mMid=v(ds(d,'mMiddlename','m_middlename')), mLast=v(ds(d,'mLastname','m_lastname'));
    const fFirst = v(ds(d,'fFirstname','f_firstname')), fMid=v(ds(d,'fMiddlename','f_middlename')), fLast=v(ds(d,'fLastname','f_lastname'));

    const motherFull = [mFirst,mMid,mLast].filter(Boolean).join(' ').trim();
    const fatherFull = [fFirst,fMid,fLast].filter(Boolean).join(' ').trim();

    const familyLast = (fLast || mLast || '').trim();
    const refFirst   = (fFirst || mFirst || '').trim();
    const shareLast  = !!(fLast && mLast && fLast.toLowerCase() === mLast.toLowerCase());
    const haveBoth   = !!(motherFull && fatherFull);

    let parentsDisplay = '';
    if (haveBoth && shareLast && refFirst && familyLast){
        parentsDisplay = `Mr. & Mrs. ${refFirst} ${familyLast}`;
    } else if (haveBoth){
        parentsDisplay = `${motherFull} & ${fatherFull}`;
    } else {
        parentsDisplay = motherFull || fatherFull || '';
    }

    const guardianName = v(ds(d,'guardianName')); // precomputed in data-guardian-name
    const finalDisplay = parentsDisplay
        ? (guardianName ? `${parentsDisplay} / ${guardianName} (Guardian)` : parentsDisplay)
        : (guardianName ? `${guardianName} (Guardian)` : '');

    document.getElementById('eg_computed_display').value = finalDisplay || `Guardian #${v(ds(d,'id'))}`;
});
</script>
@endpush
