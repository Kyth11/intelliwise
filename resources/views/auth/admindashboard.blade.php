@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
    {{-- DataTables + Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <!-- Local styles for header + top-right enroll card -->
    <style>
        #dashboard-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }
        #dashboard-header .intro { min-width: 0; }
        #dashboard-header .enroll-card {
            min-width: 260px; max-width: 320px;
            border: 1px solid rgba(0,0,0,.075);
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        #dashboard-header .enroll-card .btn { width: 100%; }
        .list-toggle-wrap { text-align: center; }

        @media (max-width: 768px) {
            #dashboard-header { flex-direction: column; }
            #dashboard-header .enroll-card { width: 100%; max-width: none; order: 2; }
            #dashboard-header .intro { order: 1; }
        }
    </style>

    <div class="card section p-4">
        <!-- Header row with top-right enroll card -->
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <h4>Welcome, {{ Auth::check() ? Auth::user()->name : 'Faculty' }}!</h4>
                <p>Here’s a quick overview of the system.</p>
            </div>

            <!-- Enroll Student (top-right) -->
            <div class="card enroll-card p-3 text-center">
                <h6 class="mb-1">Enroll a Student</h6>
                <p class="text-muted mb-3">Add a new student to the system.</p>
                {{-- Link to printable enrollment page --}}
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i> Enroll Now
                </a>
                <a href="{{ route('admin.finances') }}" class="btn btn-outline-secondary mt-2">
                    <i class="bi bi-cash-coin me-2"></i> Go to Finances
                </a>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="row mt-2" id="stats-cards">
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center shadow-sm">
                    <h6>Total Students</h6>
                    <h3>{{ $students->count() }}</h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center shadow-sm">
                    <h6>Total Teachers</h6>
                    <h3>{{ $faculties->count() }}</h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center shadow-sm">
                    <h6>System Users</h6>
                    <h3>{{ $guardians->count() + $faculties->count() }}</h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center shadow-sm">
                    <h6>Active Announcements</h6>
                    <h3>{{ $announcements->count() }}</h3>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="card mt-4 p-4" id="announcements-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Announcements</h5>
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
        // DataTables for #scheduleTable only (Tuition/Fees moved to Finances page)
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
