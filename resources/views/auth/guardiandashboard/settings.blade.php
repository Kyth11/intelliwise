@extends('layouts.guardian')

@section('title', 'Settings')

@push('styles')
<style>
    .theme-chip { cursor:pointer; user-select:none; }
    .pw-hint { font-size:.85rem; }
</style>
@endpush

@section('content')
@php
    /** @var \App\Models\User $auth */
    $auth = Auth::user();
    $guardian = \App\Models\Guardian::with('user')->find($auth->guardian_id);

    // Build a "Primary Contact" name for display/edit:
    // Prefer mother; else father; else split user's display name.
    $mFirst = trim((string) data_get($guardian, 'm_firstname'));
    $mMid   = trim((string) data_get($guardian, 'm_middlename'));
    $mLast  = trim((string) data_get($guardian, 'm_lastname'));
    $fFirst = trim((string) data_get($guardian, 'f_firstname'));
    $fMid   = trim((string) data_get($guardian, 'f_middlename'));
    $fLast  = trim((string) data_get($guardian, 'f_lastname'));

    $primaryFirst  = $mFirst ?: ($fFirst ?: '');
    $primaryMiddle = $mMid   ?: ($fMid   ?: '');
    $primaryLast   = $mLast  ?: ($fLast  ?: '');

    if (!$primaryFirst && !$primaryLast) {
        // Fallback: split auth->name
        $parts = preg_split('/\s+/', (string) $auth->name, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts) {
            $primaryFirst  = $parts[0] ?? '';
            $primaryLast   = count($parts) > 1 ? $parts[count($parts)-1] : '';
            if (count($parts) > 2) {
                $primaryMiddle = implode(' ', array_slice($parts, 1, -1));
            }
        }
    }

    // Link state for forms
    $guardianId = $auth->guardian_id ?? null;
    $isLinked   = !empty($guardianId);
@endphp

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h5 class="mb-0">Settings</h5>

        {{-- Theme chips (instant + remembered) --}}
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Theme</span>
            <button type="button" class="btn btn-outline-secondary btn-sm theme-chip" data-theme="light">
                <i class="bi bi-sun"></i> Light
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm theme-chip" data-theme="dark">
                <i class="bi bi-moon-stars"></i> Dark
            </button>
        </div>
    </div>

    {{-- Flash + validation --}}
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Fix the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$guardian)
        <div class="alert alert-warning">
            Your guardian profile isn’t fully linked yet. You can still fill in your details below — we’ll create/link your profile when you save.
        </div>
    @endif

    <div class="row g-3">
        {{-- Profile details --}}
        <div class="col-lg-6">
            <div class="card p-3 h-100">
                <h6 class="mb-3">My Profile</h6>

                {{-- Self-upsert: POST when not linked; PUT when linked --}}
                <form action="{{ route('guardians.self.upsert') }}" method="POST" autocomplete="off">
                    @csrf
                    @if($isLinked)
                        @method('PUT')
                    @endif

                    {{-- Primary Contact (mapped in controller to mother fields if you want) --}}
                    <div class="mb-2">
                        <div class="form-text mb-1">
                            Primary Contact (used for your account’s display name)
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">First name</label>
                                <input type="text" name="g_firstname" class="form-control"
                                       value="{{ old('g_firstname', $primaryFirst) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle name</label>
                                <input type="text" name="g_middlename" class="form-control"
                                       value="{{ old('g_middlename', $primaryMiddle) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last name</label>
                                <input type="text" name="g_lastname" class="form-control"
                                       value="{{ old('g_lastname', $primaryLast) }}">
                            </div>
                        </div>
                        <small class="text-muted">
                            Many schools set the mother’s name as the primary contact by default. You can switch to father/guardian if you prefer.
                        </small>
                    </div>

                    <hr>

                    {{-- Household contact --}}
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" name="g_contact" class="form-control"
                                   value="{{ old('g_contact', $guardian?->g_contact) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="g_email" class="form-control"
                                   value="{{ old('g_email', $guardian?->g_email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="g_address" class="form-control"
                                   value="{{ old('g_address', $guardian?->g_address) }}">
                        </div>
                    </div>

                    {{-- Username --}}
                    <div class="mt-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                               value="{{ old('username', $auth->username) }}" required>
                        <small class="text-muted">Must be unique.</small>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ url()->current() }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change password --}}
        <div class="col-lg-6">
            <div class="card p-3 h-100">
                <h6 class="mb-3">Change Password</h6>

                {{-- Same endpoint; hidden fields keep other values when only changing password --}}
                <form action="{{ route('guardians.self.upsert') }}" method="POST" autocomplete="off" novalidate>
                    @csrf
                    @if($isLinked)
                        @method('PUT')
                    @endif

                    {{-- Hidden keepers to avoid wiping other fields --}}
                    <input type="hidden" name="g_firstname" value="{{ $primaryFirst }}">
                    <input type="hidden" name="g_middlename" value="{{ $primaryMiddle }}">
                    <input type="hidden" name="g_lastname" value="{{ $primaryLast }}">
                    <input type="hidden" name="g_contact" value="{{ $guardian?->g_contact }}">
                    <input type="hidden" name="g_email" value="{{ $guardian?->g_email }}">
                    <input type="hidden" name="g_address" value="{{ $guardian?->g_address }}">
                    <input type="hidden" name="username" value="{{ $auth->username }}">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">New password</label>
                            <div class="input-group">
                                <input type="password" id="newPass" name="password" class="form-control" minlength="6" autocomplete="new-password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newPass" title="Show/Hide">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted pw-hint">Leave blank to keep your current password.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm password</label>
                            <div class="input-group">
                                <input type="password" id="newPass2" class="form-control" minlength="6" autocomplete="new-password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newPass2" title="Show/Hide">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small id="pwMatchMsg" class="d-block pw-hint">&nbsp;</small>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button id="savePwBtn" class="btn btn-primary" type="submit">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </form>

                <div class="mt-3 text-muted small">
                    Need to update detailed parent names separately? Ask the admin to edit your household record.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Theme chips
    (function () {
        const key = 'theme';
        const setTheme = (t) => {
            localStorage.setItem(key, t);
            document.documentElement.classList.toggle('theme-dark', t === 'dark');
            document.body.classList.toggle('theme-dark', t === 'dark');
        };
        setTheme(localStorage.getItem(key) || 'light');
        document.querySelectorAll('.theme-chip').forEach(btn => {
            btn.addEventListener('click', () => setTheme(btn.dataset.theme));
        });
    })();

    // Toggle password + live match
    (function () {
        function setEyeIcon(btn, isShown) {
            const i = btn.querySelector('i');
            i.classList.toggle('bi-eye', !isShown);
            i.classList.toggle('bi-eye-slash', isShown);
        }
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                const inp = document.querySelector(btn.dataset.target);
                if (!inp) return;
                const isPass = inp.type === 'password';
                inp.type = isPass ? 'text' : 'password';
                setEyeIcon(btn, isPass);
            });
        });

        const pw  = document.getElementById('newPass');
        const pw2 = document.getElementById('newPass2');
        const msg = document.getElementById('pwMatchMsg');
        const saveBtn = document.getElementById('savePwBtn');

        function validate() {
            const a = pw.value.trim();
            const b = pw2.value.trim();
            if (!a && !b) {
                pw2.classList.remove('is-valid','is-invalid');
                msg.textContent = '\u00A0';
                saveBtn.disabled = false;
                return;
            }
            const ok = a.length >= 6 && a === b;
            pw2.classList.toggle('is-valid', ok);
            pw2.classList.toggle('is-invalid', !ok);
            msg.textContent = ok ? 'Passwords match.' : 'Passwords do not match.';
            msg.classList.toggle('text-success', ok);
            msg.classList.toggle('text-danger', !ok);
            saveBtn.disabled = !ok;
        }
        pw?.addEventListener('input', validate);
        pw2?.addEventListener('input', validate);
    })();
</script>
@endpush
