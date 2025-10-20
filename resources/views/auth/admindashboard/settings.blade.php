@extends('layouts.admin')

@section('title', 'System Settings')

@push('styles')
<style>
    .theme-chip { cursor:pointer; user-select:none; }
    .form-control.is-valid {
        border-color: #198754 !important;
        box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25) !important;
    }
    .form-control.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
    }
    .pw-hint { font-size: .85rem; }

    /* Quick actions search icon */
    .quick-actions .icon-left {
        position:absolute; left:.5rem; top:50%; transform:translateY(-50%); color:#94a3b8;
    }
    .quick-actions input { padding-left:1.75rem; }
</style>
@endpush

@section('content')
@php
    // KPI counts
    $studentsCount       = \App\Models\Student::count();
    $teachersCount       = \App\Models\Faculty::count();
    $usersCount          = \App\Models\User::count();
    $announcementsCount  = \App\Models\Announcement::count();
    $feOn                = (bool) ($facultyEnrollmentEnabled ?? true);
@endphp

<div class="card section p-4">
    <!-- =========================
         Header: Intro | KPIs | Right: Quick Actions
    ========================== -->
    <div id="dashboard-header" class="mb-3">
        <!-- Intro -->
        <div class="intro">
            <div>
                <h5 class="mb-1">System Settings</h5>
            </div>
        </div>

        <!-- KPI strip -->
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

        <!-- Right: Quick Actions -->
        <div class="right-stack">
            <div class="card quick-actions p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="position-relative">
                    <i class="bi bi-search icon-left"></i>
                    <input type="text" id="quickSearch" class="form-control form-control-sm"
                        placeholder="Type e.g. “pay balance”, “settings”, “add subject”, “students”… then Enter">
                </div>
                <div class="mt-2 d-grid gap-2 flex-wrap">
                    {{-- Only the Faculty Enrollment quick toggle here --}}
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

    <!-- =========================
         Main content
    ========================== -->
    <div class="row g-3">

        {{-- Admin Accounts --}}
        <div class="col-12">
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

        {{-- Subjects --}}
        <div class="col-12">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Subjects</h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-journal-plus me-1"></i> Add Subject
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Grade Level</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects ?? [] as $s)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $s->subject_code }}</span></td>
                                    <td>{{ $s->subject_name }}</td>
                                    <td class="text-wrap" style="max-width: 420px;">{{ $s->description ?: '—' }}</td>
                                    <td>{{ $s->gradelvl?->grade_level ?? '—' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-warning js-edit-subject"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editSubjectModal"
                                                data-id="{{ $s->id }}"
                                                data-name="{{ $s->subject_name }}"
                                                data-code="{{ $s->subject_code }}"
                                                data-desc="{{ $s->description }}"
                                                data-grade="{{ $s->gradelvl_id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        {{-- FIXED: use admin.subjects.destroy --}}
                                        <form action="{{ route('admin.subjects.destroy', $s->id) }}"
                                              method="POST"
                                              class="d-inline js-confirm-delete"
                                              data-confirm="Delete subject '{{ $s->subject_name }}' ({{ $s->subject_code }})?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No subjects yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2">Codes must be unique. A subject belongs to a specific grade level.</small>
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

{{-- Faculty Enrollment quick modal --}}
<div class="modal fade" id="facultyEnrollmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.system.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Faculty Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="facultyEnrollmentSwitchModal"
                               name="faculty_enrollment_enabled"
                               value="1"
                               {{ $feOn ? 'checked' : '' }}>
                        <label class="form-check-label" for="facultyEnrollmentSwitchModal">
                            Allow faculty to enroll students
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Turning this off hides the “Enroll a Student” entry point in the faculty dashboard and blocks access to the flow.
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

{{-- Add Subject Modal --}}
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('admin.subjects.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header" style="background: linear-gradient(90deg, #198754, #157347); color:#fff;">
        <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Add Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Subject Name</label>
          <input type="text" name="subject_name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Subject Code</label>
          <input type="text" name="subject_code" class="form-control" placeholder="Must be unique" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Grade Level</label>
          <select name="gradelvl_id" class="form-select" required>
            <option value="">Select grade level</option>
            @foreach(($gradelvls ?? []) as $g)
              <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Short description"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save me-1"></i> Save Subject
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Edit Subject Modal --}}
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editSubjectForm" action="#" method="POST" class="modal-content">
      @csrf
      @method('PUT')
      <div class="modal-header" style="background: linear-gradient(90deg, #ffc107, #ffca2c); color:#000;">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Subject</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Subject Name</label>
          <input type="text" id="es_name" name="subject_name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Subject Code</label>
          <input type="text" id="es_code" name="subject_code" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Grade Level</label>
          <select id="es_gradelvl" name="gradelvl_id" class="form-select" required>
            <option value="">Select grade level</option>
            @foreach(($gradelvls ?? []) as $g)
              <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Description (optional)</label>
          <textarea id="es_desc" name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-save me-1"></i> Update Subject
        </button>
      </div>
    </form>
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

    // Delete confirm (delegated)
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

    // Password eye + match (add admin)
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
            const v1 = (pw.value || '').trim();
            const v2 = (pw2.value || '').trim();
            if (!v2.length) {
                pw2.classList.remove('is-valid','is-invalid');
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

    // Edit Subject modal populate
    (function () {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-edit-subject');
            if (!btn) return;

            const id   = btn.dataset.id;
            const name = btn.dataset.name || '';
            const code = btn.dataset.code || '';
            const desc = btn.dataset.desc || '';
            const gid  = btn.dataset.grade || '';

            const form = document.getElementById('editSubjectForm');
            // FIXED: admin.* route here
            form.action = "{{ route('admin.subjects.update', ':id') }}".replace(':id', id);

            document.getElementById('es_name').value = name;
            document.getElementById('es_code').value = code;
            document.getElementById('es_desc').value = desc;
            document.getElementById('es_gradelvl').value = gid;
        });
    })();

    // Quick Actions search (same behavior as dashboard)
    (function () {
        const input = document.getElementById('quickSearch');
        if (!input) return;

        const routes = {
            finances: "{{ route('admin.finances') }}",
            settings: "{{ route('admin.settings.index') }}",
  addSubject: "{{ route('admin.settings.index', ['tab' => 'subjects']) }}", // ← fix
            // FIXED: use admin.students.create
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

    // Keep Quick Action chip in sync with modal toggle (visual only)
    (function () {
        const sw = document.getElementById('facultyEnrollmentSwitchModal');
        const chip = document.getElementById('qaFeChip');
        if (!sw || !chip) return;
        sw.addEventListener('change', () => {
            const on = sw.checked;
            chip.textContent = on ? 'ON' : 'OFF';
            chip.classList.toggle('bg-success', on);
            chip.classList.toggle('bg-secondary', !on);
        });
    })();
    </script>
@endpush
