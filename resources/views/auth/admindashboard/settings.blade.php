{{-- resources/views/admin/settings.blade.php --}}
{{-- SUBJECTS AREA REMOVED; QUICK ACTION UPDATED TO POINT TO CURRICULUM --}}

@extends('layouts.admin')

@section('title', 'System Settings')

@push('styles')
    <style>
        .theme-chip {
            cursor: pointer;
            user-select: none;
        }

        .form-control.is-valid {
            border-color: #198754 !important;
            box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25) !important;
        }

        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
        }

        .pw-hint {
            font-size: .85rem;
        }

        .quick-actions .icon-left {
            position: absolute;
            left: .5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .quick-actions input {
            padding-left: 1.75rem;
        }

        .qr-preview {
            max-height: 340px;
        }

        .card.h-100 .table-responsive {
            min-height: 120px;
        }

        .right-stack {
            display: grid;
            gap: .75rem;
            align-content: start;
        }

        .card-title-tight {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .muted-hint {
            font-size: .8rem;
            color: #6c757d;
        }

        .path-note {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .78rem;
            word-break: break-all;
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Str;
        use Illuminate\Support\Facades\Storage;

        // ==== GCash QR: normalize any kind of path and ensure it renders even without public/storage symlink ====
        $raw = \App\Models\AppSetting::get('gcash_qr_path');
        $gcashQrExists = false;
        $gcashServeSrc = null;
        $gcashResolvedClean = null;
        $gcashResolvedUrl = null;
        $gcashResolvedDisk = 'public';
        $gcashResolvedMime = null;
        $gcashResolutionNote = null;

        if ($raw) {
            if (Str::startsWith($raw, ['http://', 'https://'])) {
                $gcashServeSrc = $raw;
                $gcashQrExists = true;
                $gcashResolvedClean = $raw;
                $gcashResolvedUrl = $raw;
                $gcashResolutionNote = 'Using absolute URL.';
            } else {
                $candidate = str_replace('\\', '/', $raw);

                $candidate = preg_replace('#^/?:?storage/#i', '', ltrim($candidate, '/'));
                $candidate = preg_replace('#^public/#i', '', $candidate);

                if (preg_match('#storage/app/public/(.+)$#i', $candidate, $m)) {
                    $candidate = $m[1];
                }

                if (preg_match('#[A-Za-z]:/.*?/storage/app/public/(.+)$#', $candidate, $m)) {
                    $candidate = $m[1];
                }

                $candidate = ltrim($candidate, '/');

                $gcashResolvedClean = $candidate;
                $gcashQrExists = $gcashResolvedClean ? Storage::disk($gcashResolvedDisk)->exists($gcashResolvedClean) : false;
                $gcashResolvedUrl = $gcashResolvedClean ? Storage::disk($gcashResolvedDisk)->url($gcashResolvedClean) : null;

                $publicSymlinkPresent = is_dir(public_path('storage'));

                if ($gcashQrExists) {
                    if ($publicSymlinkPresent && $gcashResolvedUrl) {
                        $gcashServeSrc = $gcashResolvedUrl;
                        $gcashResolutionNote = 'Using public URL via storage symlink.';
                    } else {
                        try {
                            $bytes = Storage::disk($gcashResolvedDisk)->get($gcashResolvedClean);
                            try {
                                $gcashResolvedMime = Storage::disk($gcashResolvedDisk)->mimeType($gcashResolvedClean);
                            } catch (\Throwable $e) {
                                $gcashResolvedMime = 'image/png';
                            }
                            $gcashServeSrc = 'data:' . $gcashResolvedMime . ';base64,' . base64_encode($bytes);
                            $gcashResolutionNote = 'Public symlink missing; embedding image as data URL.';
                        } catch (\Throwable $e) {
                            $gcashServeSrc = null;
                            $gcashResolutionNote = 'File found on disk but failed to read for preview.';
                        }
                    }
                } else {
                    $gcashServeSrc = null;
                    $gcashResolutionNote = 'Path normalized, but file not found on public disk.';
                }
            }
        }

        // ==== KPIs and toggles ====
        $studentsCount = \App\Models\Student::count();
        $teachersCount = \App\Models\Faculty::count();
        $usersCount = \App\Models\User::count();
        $announcementsCount = \App\Models\Announcement::count();
        $feOn = (bool) (\App\Models\AppSetting::get('faculty_enrollment_enabled', true));

        /** @var \App\Models\Schoolyr|null $current */
        $current = $current
            ?? \App\Models\Schoolyr::where('active', true)->first()
            ?? \App\Models\Schoolyr::orderBy('school_year')->first();

    @endphp

    <div class="card section p-4">
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <div>
                    <h5 class="mb-1">System Settings</h5>
                </div>
            </div>

            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ number_format($studentsCount) }}</div>
                    <div class="kpi-label">Total Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ number_format($teachersCount) }}</div>
                    <div class="kpi-label">Teachers</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ number_format($usersCount) }}</div>
                    <div class="kpi-label">System Users</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ number_format($announcementsCount) }}</div>
                    <div class="kpi-label">Announcements</div>
                </div>
            </div>

            <div class="right-stack">
                <div class="card quick-actions p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="position-relative mb-3">
                        <i class="bi bi-search icon-left"></i>
                        <input type="text" id="quickSearch" class="form-control form-control-sm"
                               placeholder="Type e.g. “pay balance”, “settings”, “add subject”, “students”… then Enter">
                    </div>

                    <div class="d-grid gap-2 flex-wrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-warning d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#facultyEnrollmentModal"
                                title="Toggle faculty enrollment">
                            <i class="bi bi-sliders"></i>
                            <span>Faculty Enrollment</span>
                            <span id="qaFeChip" class="badge {{ $feOn ? 'bg-success' : 'bg-secondary' }}">
                                {{ $feOn ? 'ON' : 'OFF' }}
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- MAIN CONTENT ROW --}}
        <div class="row g-3 align-items-stretch">
            {{-- LEFT COLUMN STACK ONLY (subjects removed) --}}
            <div class="col-12 col-lg-6">
                <div class="right-stack">

                    {{-- Admin Accounts --}}
                    <div class="card p-3">
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
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $admin->username }}</span>
                                            </td>
                                            <td>{{ $admin->created_at?->format('Y-m-d') ?? '—' }}</td>
                                            <td class="text-end">
                                                @if(auth()->id() === $admin->id)
                                                    <button class="btn btn-sm btn-secondary" disabled
                                                            title="You cannot delete yourself">
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

                    {{-- School Year --}}
                    <div class="card p-3">
                        <div class="card-title-tight mb-2">
                            <h6 class="mb-0">School Year</h6>
                        </div>
                        @if($current)
                            @php
                                $hasPrev = \App\Models\Schoolyr::where('school_year', '<', $current->school_year)->exists();
                                $hasNext = \App\Models\Schoolyr::where('school_year', '>', $current->school_year)->exists();
                            @endphp

                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="badge bg-light text-dark border">
                                    Current: {{ $current->school_year ?? $current->display_label }}
                                </span>

                                @if($hasNext)
                                    <form class="d-inline js-sy-action" data-action-type="proceed"
                                          action="{{ route('admin.settings.schoolyear.proceed', $current->id) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-arrow-repeat me-1"></i> Proceed to next SY
                                        </button>
                                    </form>
                                @endif

                                @if($hasPrev)
                                    <form class="d-inline js-sy-action" data-action-type="revert"
                                          action="{{ route('admin.settings.schoolyear.revert', $current->id) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Back to previous SY
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <small class="text-muted d-block">
                                These actions only change which school year is marked as active. You can move forward or back as
                                needed.
                            </small>
                        @else
                            <div class="text-muted small">No School Year found.</div>
                        @endif
                    </div>

                    {{-- GCash QR --}}
                    <div class="card p-3">
                        <div class="card-title-tight mb-2">
                            <h6 class="mb-0">GCash QR</h6>
                        </div>

                        @php
                            $raw = \App\Models\AppSetting::get('gcash_qr_path');
                            $gcashResolvedClean = null;
                            $gcashResolvedUrl = null;
                            $gcashServeSrc = null;
                            $gcashQrExists = false;
                            $gcashResolutionNote = null;

                            if ($raw) {
                                if (Str::startsWith($raw, ['http://', 'https://'])) {
                                    $gcashResolvedClean = null;
                                    $gcashResolvedUrl = $raw;
                                    try {
                                        $gcashServeSrc = $gcashResolvedUrl;
                                        $gcashQrExists = true;
                                        $gcashResolutionNote = 'Absolute URL saved; rendering via URL.';
                                    } catch (\Throwable $e) {
                                        $gcashServeSrc = null;
                                        $gcashResolutionNote = 'Absolute URL saved but not readable.';
                                    }
                                } else {
                                    $candidate = str_replace('\\', '/', $raw);
                                    $candidate = preg_replace('#^/?:?storage/#i', '', ltrim($candidate, '/'));
                                    $candidate = preg_replace('#^public/#i', '', $candidate);
                                    if (preg_match('#storage/app/public/(.+)$#i', $candidate, $m)) {
                                        $candidate = $m[1];
                                    }
                                    if (preg_match('#[A-Za-z]:/.*?/storage/app/public/(.+)$#', $candidate, $m)) {
                                        $candidate = $m[1];
                                    }
                                    $candidate = ltrim($candidate, '/');

                                    $gcashResolvedClean = $candidate;
                                    $gcashQrExists = $gcashResolvedClean && Storage::disk('public')->exists($gcashResolvedClean);
                                    $gcashResolvedUrl = $gcashQrExists ? Storage::disk('public')->url($gcashResolvedClean) : null;

                                    if ($gcashQrExists) {
                                        try {
                                            $bytes = Storage::disk('public')->get($gcashResolvedClean);
                                            $mime = Storage::disk('public')->mimeType($gcashResolvedClean) ?? 'image/png';
                                            $gcashServeSrc = 'data:' . $mime . ';base64,' . base64_encode($bytes);
                                            $gcashResolutionNote = 'Embedded as data URL (server URL not required).';
                                        } catch (\Throwable $e) {
                                            $gcashServeSrc = $gcashResolvedUrl;
                                            $gcashResolutionNote = 'Embed failed; falling back to public URL.';
                                        }
                                    } else {
                                        $gcashServeSrc = null;
                                        $gcashResolutionNote = 'Path normalized, but file not found on public disk.';
                                    }
                                }
                            }
                        @endphp

                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-auto">
                                @if($gcashServeSrc && $gcashQrExists)
                                    <img src="{{ $gcashServeSrc }}" class="border rounded qr-preview" alt="GCash QR">
                                @elseif($gcashResolvedClean && !$gcashQrExists)
                                    <div class="text-danger small">QR path saved but file not found on public disk.</div>
                                @else
                                    <div class="text-muted small">No GCash QR uploaded.</div>
                                @endif

                                @if($raw)
                                    <div class="mt-2" style="font-size:.8rem;color:#6c757d">
                                        <div style="font-family:Consolas,monospace"><strong>Saved:</strong> {{ $raw }}</div>
                                        @if($gcashResolvedClean)
                                            <div style="font-family:Consolas,monospace"><strong>Resolved:</strong>
                                                {{ $gcashResolvedClean }}</div>
                                        @endif
                                        @if($gcashResolvedUrl)
                                            <div style="font-family:Consolas,monospace"><strong>URL:</strong> {{ $gcashResolvedUrl }}</div>
                                        @endif
                                        @if($gcashResolutionNote)
                                            <div style="font-family:Consolas,monospace"><strong>Note:</strong>
                                                {{ $gcashResolutionNote }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 col-md-auto">
                                <form action="{{ route('admin.settings.gcashqr.upload') }}" method="POST"
                                      enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                    @csrf
                                    <input type="file" name="gcash_qr" class="form-control form-control-sm"
                                           accept=".jpg,.jpeg,.png,.webp" required>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                        <i class="bi bi-upload me-1"></i> Upload / Replace QR
                                    </button>
                                    <small class="text-muted">
                                        PNG/JPG/WEBP up to 5MB. Stored on <code>public</code> disk under
                                        <code>storage/app/public/gcash</code>.
                                    </small>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="e.g., Jane Dela Cruz" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="e.g., admin.jane" required>
                            <small class="text-muted">Must be unique.</small>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" id="adminPassword" name="password"
                                           class="form-control" minlength="8" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="#adminPassword" title="Show/Hide">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block pw-hint">At least 8 characters.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" id="adminPasswordConfirm"
                                           name="password_confirmation" class="form-control"
                                           minlength="8" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="#adminPasswordConfirm" title="Show/Hide">
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

    {{-- Faculty Enrollment modal --}}
    <div class="modal fade" id="facultyEnrollmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.settings.system.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Faculty Enrollment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="facultyEnrollmentSwitchModal"
                                   name="faculty_enrollment_enabled" value="1" {{ $feOn ? 'checked' : '' }}>
                            <label class="form-check-label" for="facultyEnrollmentSwitchModal">
                                Allow faculty to enroll students
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Turning this off hides the “Enroll a Student” entry point in the faculty dashboard and blocks
                            access to the flow.
                        </small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    // Generic delete confirm
    (function () {
        function confirmDelete(form, msg, btn) {
            if (!window.Swal) { form.submit(); return; }
            Swal.fire({
                title: 'Are you sure?',
                text: msg || "You can't undo this action.",
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

    // Password eye + match
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
            if (!pw || !pw2) return;
            const v1 = pw.value.trim();
            const v2 = pw2.value.trim();
            if (!v2.length) {
                pw2.classList.remove('is-valid', 'is-invalid');
                if (msg) msg.textContent = '\u00A0';
                if (saveBtn) saveBtn.disabled = false;
                return;
            }
            const match = v1 === v2 && v1.length >= 8;
            pw2.classList.toggle('is-valid', match);
            pw2.classList.toggle('is-invalid', !match);
            if (msg) {
                msg.textContent = match ? 'Passwords match.' : 'Passwords do not match.';
                msg.classList.toggle('text-success', match);
                msg.classList.toggle('text-danger', !match);
            }
            if (saveBtn) saveBtn.disabled = !match;
        }

        pw?.addEventListener('input', validateMatch);
        pw2?.addEventListener('input', validateMatch);
    })();

    // Quick action search (addSubject now points to curriculum)
    (function () {
        const input = document.getElementById('quickSearch');
        if (!input) return;

        const routes = {
            finances: "{{ route('admin.finances') }}",
            settings: "{{ route('admin.settings.index') }}",
            addSubject: "{{ route('admin.curriculum.index') }}",
            enroll: "{{ route('admin.students.create') }}",
            students: "{{ route('admin.students.index') }}",
            grades: "{{ route('admin.grades') }}"
        };

        function go(q) {
            q = (q || '').toLowerCase().trim();
            if (!q) return;

            if (q.includes('pay') || q.includes('balance') || q.includes('finance')) { location.href = routes.finances; return; }
            if (q.includes('setting')) { location.href = routes.settings; return; }
            if (q.includes('subject')) { location.href = routes.addSubject; return; }
            if (q.includes('enroll')) { location.href = routes.enroll; return; }
            if (q.includes('student')) { location.href = routes.students; return; }
            if (q.includes('grade')) { location.href = routes.grades; return; }

            const moneyish = ['payment', 'receipt', 'fee', 'tuition', 'cash', 'ledger', 'invoice'];
            if (moneyish.some(w => q.includes(w))) { location.href = routes.finances; return; }

            location.href = routes.settings;
        }

        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(input.value); });
    })();

    // SY proceed/revert SweetAlert
    (function () {
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form.js-sy-action');
            if (!form) return;

            e.preventDefault();

            const type = form.dataset.actionType || 'proceed';
            const submitBtn = form.querySelector('button[type="submit"]');

            const title = type === 'revert'
                ? 'Go back to the previous school year?'
                : 'Proceed to the next school year?';

            const text = type === 'revert'
                ? 'This will mark the previous school year as active again. No records will be deleted.'
                : 'This will mark the next school year as active. No records will be deleted.';

            Swal.fire({
                title: title,
                text: text,
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
            }).then(res => {
                if (res.isConfirmed) {
                    if (submitBtn) submitBtn.disabled = true;
                    form.submit();
                }
            });
        }, true);
    })();
</script>
@endpush
