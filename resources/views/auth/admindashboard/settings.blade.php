@extends('layouts.admin')

@section('title', 'System Settings')

@push('styles')
    {{-- Page-specific styles (optional) --}}
    <style>
        /* extra emphasis on match/mismatch */
        .form-control.is-valid {
            border-color: #198754 !important;
            box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25) !important;
        }
        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
        }
        .pw-hint {
            font-size: 0.8rem;
        }
    </style>
@endpush

@section('content')
<div class="card section p-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">System Settings</h4>
        <div class="d-flex gap-2">
                    <label class="form-label">Theme</label>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary theme-chip" data-theme="light">
                <i class="bi bi-sun"></i> Light
            </button>
            <button type="button" class="btn btn-outline-secondary theme-chip" data-theme="dark">
                <i class="bi bi-moon-stars"></i> Dark
            </button>
        </div>
        <small class="text-muted">Applies instantly and is remembered.</small>
    </div>

            {{-- <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="bi bi-person-plus me-1"></i> Add Admin
            </button> --}}
        </div>
    </div>
    <p class="text-muted mb-3">Manage system preferences, administrators.</p>

    {{-- Flash messages --}}
    {{-- @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Fix the following:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif --}}

    {{-- <div class="row g-3">

        <div class="col-lg-6">
            <div class="card p-3 h-100">
                <h6 class="mb-3">Quick Actions</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Change Password
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Open</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        System Preferences
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Coming soon</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Backup Database
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Coming soon</button>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Theme: <span class="dropdownheader">Light / Dark</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch">
                            <label class="form-check-label" for="themeSwitch">Dark mode</label>
                        </div>
                    </li>
                </ul>
            </div>
        </div> --}}

        {{-- Admin Accounts --}}
        <div class="col-lg-6">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Admin Accounts</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="bi bi-person-plus me-1"></i> New Admin
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($admins as $admin)
                                <tr>
                                    <td>{{ $admin->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $admin->username }}</span></td>
                                    <td>{{ $admin->created_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-end">
                                        @if(auth()->id() === $admin->id)
                                            <button class="btn btn-sm btn-secondary" disabled title="You cannot delete yourself">
                                                <i class="bi bi-shield-lock"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.settings.admins.destroy', $admin->id) }}"
                                                  method="POST"
                                                  class="d-inline js-confirm-delete"
                                                  data-confirm="Delete admin '{{ $admin->name }}' ({{ $admin->username }})?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger js-delete-btn">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No admin accounts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2">Tip: Keep at least two admins to avoid lockout.</small>
            </div>
        </div>

        {{-- School Years
        <div class="col-12">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">School Years</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSchoolYearModal">
                        <i class="bi bi-calendar-plus me-1"></i> Add School Year
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($schoolyrs as $sy)
                        <span class="badge bg-light text-dark border">{{ $sy->school_year }}</span>
                    @empty
                        <span class="text-muted">No school years yet.</span>
                    @endforelse
                </div>
            </div>
        </div> --}}
    </div>
</div>

{{-- Add Admin Modal --}}
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.admins.store') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Admin Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Jane Dela Cruz" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g., admin.jane" required>
                        <small class="text-muted">Must be unique.</small>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" id="adminPassword" name="password" class="form-control" minlength="8" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#adminPassword" title="Show/Hide">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block pw-hint">At least 8 characters.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" id="adminPasswordConfirm" name="password_confirmation" class="form-control" minlength="8" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#adminPasswordConfirm" title="Show/Hide">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small id="pwMatchMsg" class="d-block pw-hint">&nbsp;</small>
                        </div>
                    </div>

                    <input type="hidden" name="role" value="admin">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button id="saveAdminBtn" type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add School Year Modal
<div class="modal fade" id="addSchoolYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.schoolyear.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Add School Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">School Year (YYYY-YYYY)</label>
                    <input type="text" name="school_year" class="form-control" placeholder="2025-2026" pattern="^\d{4}-\d{4}$" required>
                    <small class="text-muted">Example: 2025-2026</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Year
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> --}}
@endsection

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // Delete confirm (delegated)
    (function () {
        function confirmDelete(form, msg, btn) {
            if (!window.Swal) { form.submit(); return; }
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
                backdrop: false,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((res) => {
                if (res.isConfirmed) {
                    if (btn) btn.disabled = true;
                    form.submit();
                }
            });
        }
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-delete-btn');
            if (!btn) return;
            const form = btn.closest('form.js-confirm-delete');
            if (!form) return;
            e.preventDefault();
            confirmDelete(form, form.dataset.confirm, btn);
        });
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form.js-confirm-delete');
            if (!form) return;
            e.preventDefault();
            const btn = form.querySelector('.js-delete-btn') || form.querySelector('[type="submit"]');
            confirmDelete(form, form.dataset.confirm, btn);
        }, true);
    })();

    // Show/Hide password toggles + live match check
    (function () {
        const pw = document.getElementById('adminPassword');
        const pw2 = document.getElementById('adminPasswordConfirm');
        const msg = document.getElementById('pwMatchMsg');
        const saveBtn = document.getElementById('saveAdminBtn');

        function setEyeIcon(btn, isShown) {
            const i = btn.querySelector('i');
            i.classList.toggle('bi-eye', !isShown);
            i.classList.toggle('bi-eye-slash', isShown);
        }

        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = document.querySelector(btn.dataset.target);
                if (!target) return;
                const isPass = target.getAttribute('type') === 'password';
                target.setAttribute('type', isPass ? 'text' : 'password');
                setEyeIcon(btn, isPass);
            });
        });

        function validateMatch() {
            const v1 = pw.value.trim();
            const v2 = pw2.value.trim();

            if (v2.length === 0) {
                pw2.classList.remove('is-valid', 'is-invalid');
                msg.textContent = '\u00A0';
                saveBtn.disabled = false;
                return;
            }

            const match = v1 === v2 && v1.length >= 8;
            pw2.classList.toggle('is-valid', match);
            pw2.classList.toggle('is-invalid', !match);

            msg.textContent = match ? 'Passwords match.' : 'Passwords do not match.';
            msg.classList.toggle('text-success', match);
            msg.classList.toggle('text-danger', !match);

            // Optional: prevent submit when mismatch
            saveBtn.disabled = !match;
        }

        pw.addEventListener('input', validateMatch);
        pw2.addEventListener('input', validateMatch);
    })();

    // Theme switch (persists across site)
    (function () {
        const key = 'theme';
        const stored = localStorage.getItem(key) || 'light';
        const isDark = stored === 'dark';

        const switchEl = document.getElementById('themeSwitch');
        if (switchEl) {
            switchEl.checked = isDark;
            switchEl.addEventListener('change', () => {
                const dark = switchEl.checked;
                document.body.classList.toggle('theme-dark', dark);
                document.documentElement.classList.toggle('theme-dark', dark);
                localStorage.setItem(key, dark ? 'dark' : 'light');
            });
        }
    })();
    </script>
@endpush
