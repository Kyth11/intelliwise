@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
    {{-- DataTables + Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        /* -------- Header layout (mirrors Faculty) -------- */
        #dashboard-header {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: stretch;
        }
        #dashboard-header .intro {
            min-width: 0;
            display: flex;
            align-items: center;
            padding: .75rem 1rem;
            background: var(--bs-body-bg);
        }

        /* ---- Compact KPI strip (center) ---- */
        .kpi-strip {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 1fr;
            gap: .75rem;
            align-items: center;
            justify-items: center;
            padding: .5rem;
            min-width: 300px;
        }
        .kpi-card {
            width: 120px;
            border-radius: .75rem;
            border: 1px solid var(--bs-border-color, rgba(0,0,0,.12));
            background: var(--bs-body-bg);
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            padding: .5rem .75rem;
            text-align: center;
        }
        .kpi-number {
            line-height: 1;
            font-weight: 700;
            font-size: 1.35rem;
        }
        .kpi-label {
            font-size: .75rem;
            color: var(--bs-secondary-color);
            white-space: nowrap;
        }

        /* ---- Enroll card (right) ---- */
        .enroll-card {
            min-width: 260px;
            border: 1px solid var(--bs-border-color, rgba(0,0,0,.125));
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            border-radius: .75rem;
        }
        .enroll-card .btn { width: 100%; }

        .list-toggle-wrap { text-align: center; }

        /* -------- Responsive -------- */
        @media (max-width: 992px) {
            #dashboard-header {
                grid-template-columns: 1fr;
            }
            .kpi-strip {
                order: 2;
                grid-auto-flow: row;
                grid-auto-rows: 1fr;
                grid-template-columns: repeat(2, 1fr);
                min-width: 0;
                padding: 0;
            }
            .enroll-card { order: 3; }
            .intro { order: 1; }
        }
        @media (max-width: 400px) {
            .kpi-card { width: 100%; }
        }
    </style>
@endpush

@section('content')
    <div class="card section p-4">
        <!-- Header: Welcome | KPIs (center) | Enroll -->
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <div>
                    <h5 class="mb-1">Welcome, {{ Auth::check() ? Auth::user()->name : 'Admin' }}!</h5>
                    <div class="text-muted small">Here’s a quick system snapshot and your tools.</div>
                </div>
            </div>

            <!-- KPI strip (center) -->
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $students->count() }}</div>
                    <div class="kpi-label">Total Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $faculties->count() }}</div>
                    <div class="kpi-label">Teachers</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $guardians->count() + $faculties->count() }}</div>
                    <div class="kpi-label">System Users</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $announcements->count() }}</div>
                    <div class="kpi-label">Announcements</div>
                </div>
            </div>

            <!-- Enroll & Finances (right) -->
            <div class="card enroll-card p-3 text-center">
                <h6 class="mb-1">Enroll a Student</h6>
                <p class="text-muted mb-3 small">Add a new student to the system.</p>
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i> Enroll Now
                </a>
                <a href="{{ route('admin.finances') }}" class="btn btn-outline-secondary mt-2">
                    <i class="bi bi-cash-coin me-2"></i> Go to Finances
                </a>
            </div>
        </div>

        <!-- Announcements -->
        <div class="card mt-2 p-4" id="announcements-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Announcements</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                    <i class="bi bi-megaphone me-1"></i> Add
                </button>
            </div>

            @if($announcements->isEmpty())
                <p class="text-muted">No announcements yet.</p>
            @else
                <ul class="list-group" id="announcementsList">
                    @foreach($announcements as $a)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $a->title ?? 'Untitled' }}</strong>
                                @if($a->content) — {{ $a->content }} @endif
                                <br>
                                <small class="text-muted d-block">
                                    @if($a->date_of_event)
                                        <span class="me-3">Event: {{ $a->date_of_event->format('Y-m-d') }}</span>
                                    @endif
                                    @if($a->deadline)
                                        <span class="me-3">Deadline: {{ $a->deadline->format('Y-m-d') }}</span>
                                    @endif
                                    <span class="me-3">
                                        For:
                                        @php $names = $a->gradelvls->pluck('grade_level')->filter()->values(); @endphp
                                        {{ $names->isNotEmpty() ? $names->implode(', ') : 'All Grade Levels' }}
                                    </span>
                                    <span>Posted: {{ $a->created_at->format('Y-m-d g:i A') }}</span>
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <!-- EDIT -->
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#editAnnouncementModal{{ $a->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <!-- DELETE (SweetAlert2) -->
                                <form action="{{ route('announcements.destroy', $a->id) }}" method="POST"
                                      class="d-inline js-confirm-delete" data-confirm="Delete this announcement?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger js-delete-btn" aria-label="Delete announcement">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </div>
                        </li>

                        {{-- Per-row edit modal --}}
                        @include('auth.admindashboard.partials.edit-announcement-modal', ['a' => $a, 'gradelvls' => $gradelvls])
                    @endforeach
                </ul>

                <!-- Show more/less for announcements -->
                <div id="announcementsToggle" class="list-toggle-wrap mt-2"></div>
            @endif
        </div>

        <!-- Schedule Notes -->
        <div class="card mt-4 p-4" id="schedule-section">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Schedule Notes</h5>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="scheduleSearch" class="form-control form-control-sm" placeholder="Search schedule...">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Schedule
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="scheduleTable">
                    <thead class="table-light">
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Grade Level</th>
                            <th>School Year</th>
                            <th>Faculty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($schedules->isNotEmpty())
                            @foreach($schedules as $schedule)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $schedule->day }}</span></td>
                                    <td>{{ $schedule->class_start }} - {{ $schedule->class_end }}</td>
                                    <td>{{ $schedule->subject->subject_name ?? '-' }}</td>
                                    <td>{{ $schedule->gradelvl->grade_level ?? '-' }}</td>
                                    <td>{{ $schedule->school_year ?? '—' }}</td>
                                    <td>{{ $schedule->faculty->user->name ?? '—' }}</td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editScheduleModal{{ $schedule->id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST"
                                            class="d-inline js-confirm-delete" data-confirm="Delete this schedule record?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn" aria-label="Delete schedule">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Edit Schedule Modals (one per schedule) --}}
    @foreach($schedules as $schedule)
        <div class="modal fade" id="editScheduleModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    @include('auth.admindashboard.partials.edit-schedule-modal', ['schedule' => $schedule])
                </div>
            </div>
        </div>
    @endforeach

    {{-- Include Modals --}}
    @include('auth.admindashboard.partials.add-announcement-modal')
    @include('auth.admindashboard.partials.add-schedule-modal')
@endsection

@push('scripts')
    {{-- jQuery + DataTables + Bootstrap 5 adapter --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // SweetAlert2 delete
        (function () {
            function confirmDelete(form, msg, btn) {
                if (!window.Swal) { form.submit(); return; }
                Swal.fire({
                    title: 'Are you sure to delete this record?',
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
    </script>

    <script>
        // DataTables for #scheduleTable
        $(function () {
            const scheduleDT = $('#scheduleTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                language: { emptyTable: "No schedules available." },
                columnDefs: [{ targets: -1, orderable: false }]
            });
            $('#scheduleSearch').on('input', function () { scheduleDT.search(this.value).draw(); });
        });
    </script>

    <script>
        // Show more/less for Announcements UL
        (function attachListShowMore(listId, toggleWrapId, maxVisible = 10) {
            const ul = document.getElementById(listId);
            const wrap = document.getElementById(toggleWrapId);
            if (!ul || !wrap) return;

            const items = Array.from(ul.querySelectorAll('li'));
            if (items.length <= maxVisible) { wrap.innerHTML = ''; return; }

            let collapsed = true;
            function render() {
                items.forEach((li, idx) => { li.style.display = (collapsed && idx >= maxVisible) ? 'none' : ''; });
                wrap.innerHTML = '';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary btn-sm';
                btn.innerHTML = collapsed
                    ? `<i class="bi bi-chevron-down me-1"></i> Show more (${items.length - maxVisible})`
                    : `<i class="bi bi-chevron-up me-1"></i> Show less`;
                btn.addEventListener('click', () => { collapsed = !collapsed; render(); });
                wrap.appendChild(btn);
            }
            render();
        })('announcementsList', 'announcementsToggle', 10);
    </script>
@endpush
