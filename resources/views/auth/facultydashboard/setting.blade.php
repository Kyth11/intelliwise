@extends('layouts.faculty')
@section('title', 'Faculty · Settings')

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
    $faculty = \App\Models\Faculty::find($auth->faculty_id);
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
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    @if(!$faculty)
        <div class="alert alert-warning">
            Your faculty profile isn’t fully linked yet. You can still fill in your details below — we’ll create/link your profile when you save.
        </div>
    @endif

    <div class="row g-3">
        {{-- Profile details --}}
        <div class="col-lg-6">
            <div class="card p-3 h-100">
                <h6 class="mb-3">My Profile</h6>

                {{-- ✅ No id param needed now --}}
                <form action="{{ route('faculty.profile.update') }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">First name</label>
                            <input type="text" name="f_firstname" class="form-control"
                                   value="{{ old('f_firstname', $faculty?->f_firstname) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle name</label>
                            <input type="text" name="f_middlename" class="form-control"
                                   value="{{ old('f_middlename', $faculty?->f_middlename) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last name</label>
                            <input type="text" name="f_lastname" class="form-control"
                                   value="{{ old('f_lastname', $faculty?->f_lastname) }}" required>
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" name="f_contact" class="form-control"
                                   value="{{ old('f_contact', $faculty?->f_contact) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="f_email" class="form-control"
                                   value="{{ old('f_email', $faculty?->f_email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="f_address" class="form-control"
                                   value="{{ old('f_address', $faculty?->f_address) }}">
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

                {{-- ✅ No id param needed now --}}
                <form action="{{ route('faculty.profile.update') }}" method="POST" autocomplete="off" novalidate>
                    @csrf
                    @method('PUT')

                    {{-- Hidden fields to keep current names/username when only changing password --}}
                    <input type="hidden" name="f_firstname"  value="{{ $faculty?->f_firstname }}">
                    <input type="hidden" name="f_middlename" value="{{ $faculty?->f_middlename }}">
                    <input type="hidden" name="f_lastname"   value="{{ $faculty?->f_lastname }}">
                    <input type="hidden" name="f_contact"    value="{{ $faculty?->f_contact }}">
                    <input type="hidden" name="f_email"      value="{{ $faculty?->f_email }}">
                    <input type="hidden" name="f_address"    value="{{ $faculty?->f_address }}">
                    <input type="hidden" name="username"     value="{{ $auth->username }}">

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
        // apply stored theme on load
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
