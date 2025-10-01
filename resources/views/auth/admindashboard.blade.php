@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <!-- Local styles for header + top-right enroll card + table toggles -->
    <style>
        /* Scope to this page only */
        #dashboard-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        #dashboard-header .intro {
            min-width: 0;
            /* prevents overflow in flex */
        }

        /* The small action card on the right */
        #dashboard-header .enroll-card {
            min-width: 260px;
            max-width: 320px;
            border: 1px solid rgba(0, 0, 0, .075);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        }

        #dashboard-header .enroll-card .btn {
            width: 100%;
        }

        /* Toggle area under tables/lists */
        .table-toggle-wrap,
        .list-toggle-wrap {
            text-align: center;
        }

        /* Responsive: stack on small screens */
        @media (max-width: 768px) {
            #dashboard-header {
                flex-direction: column;
            }

            #dashboard-header .enroll-card {
                width: 100%;
                max-width: none;
                order: 2;
                /* show after intro text on mobile */
            }

            #dashboard-header .intro {
                order: 1;
            }
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
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
                    <i class="bi bi-person-plus me-2"></i> Enroll Now
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
                                @if($a->content)
                                    — {{ $a->content }}
                                @endif
                                <br>

                                <small class="text-muted d-block">
                                    @if($a->date_of_event)
                                        <span class="me-3">Event: {{ $a->date_of_event->format('Y-m-d') }}</span>
                                    @endif
                                    @if($a->deadline)
                                        <span class="me-3">Deadline: {{ $a->deadline->format('Y-m-d') }}</span>
                                    @endif
                                    <span class="me-3">
                                        For: {{ $a->gradelvl?->grade_level ?? 'All Grade Levels' }}
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

                                <!-- DELETE (native submit + confirm) -->
                                <form action="{{ route('announcements.destroy', $a->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this announcement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
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
                    <input type="text" id="scheduleSearch" class="form-control form-control-sm"
                        placeholder="Search schedule...">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Schedule
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped searchable-sortable" id="scheduleTable">
                    <thead class="table-light">
                        <tr>
                            <th data-type="text">Day</th>
                            <th data-type="text">Time</th>
                            <th data-type="text">Subject</th>
                            <th data-type="text">Room</th>
                            <th data-type="text">Section</th>
                            <th data-type="text">Grade Level</th>
                            <th data-type="text">School Year</th>
                            <th data-type="text">Faculty</th>
                            <th data-type="text">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $schedule)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $schedule->day }}</span></td>
                                <td>{{ $schedule->class_start }} - {{ $schedule->class_end }}</td>
                                <td>{{ $schedule->subject->subject_name ?? '-' }}</td>
                                <td>{{ $schedule->room->room_number ?? '-' }}</td>
                                <td>{{ $schedule->section->section_name ?? '-' }}</td>
                                <td>{{ $schedule->gradelvl->grade_level ?? '-' }}</td>
                                <td>{{ $schedule->school_year ?? '—' }}</td>
                                <td>{{ $schedule->faculty->user->name ?? '—' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editScheduleModal{{ $schedule->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this schedule record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No schedules available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="scheduleToggle" class="table-toggle-wrap mt-2"></div>
        </div>

        <!-- Tuition Details Section -->
        <div class="card mt-4 p-4" id="tuition-section">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Tuition & Fees</h5>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="tuitionSearch" class="form-control form-control-sm"
                        placeholder="Search tuition...">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTuitionModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Tuition
                    </button>
                </div>
            </div>

            @if($tuitions->isEmpty())
                <p class="text-muted">No tuition fees set yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped searchable-sortable" id="tuitionTable">
                        <thead class="table-light">
                            <tr>
                                <th data-type="text">Grade Level</th>
                                <th data-type="number">Monthly (₱)</th>
                                <th data-type="number">Yearly (₱)</th>
                                <th data-type="number">Misc (₱)</th>
                                <th data-type="text">Optional Desc</th>
                                <th data-type="number">Optional (₱)</th>
                                <th data-type="number">Total (₱)</th>
                                <th data-type="text">School Year</th>
                                <th data-type="text">Updated</th>
                                <th data-type="text">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tuitions as $t)
                                <tr>
                                    <td>{{ $t->grade_level }}</td>
                                    <td>{{ number_format((float) $t->monthly_fee, 2) }}</td>
                                    <td>{{ number_format((float) $t->yearly_fee, 2) }}</td>
                                    <td>
                                        @if(is_null($t->misc_fee)) — @else {{ number_format((float) $t->misc_fee, 2) }} @endif
                                    </td>
                                    <td>{{ $t->optional_fee_desc ?? '—' }}</td>
                                    <td>
                                        @if(is_null($t->optional_fee_amount)) — @else
                                        {{ number_format((float) $t->optional_fee_amount, 2) }} @endif
                                    </td>
                                    <td>{{ number_format((float) $t->total_yearly, 2) }}</td>
                                    <td>{{ $t->school_year ?? '—' }}</td>
                                    <td>{{ $t->updated_at?->format('Y-m-d') ?? '—' }}</td>

                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editTuitionModal{{ $t->id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- DELETE (native submit + confirm) -->
                                        <form action="{{ route('tuitions.destroy', $t->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Delete this tuition record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Include per-row edit modal so $t exists inside --}}
                                @include('auth.admindashboard.partials.edit-tuition-modal', ['t' => $t, 'schoolyrs' => $schoolyrs])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="tuitionToggle" class="table-toggle-wrap mt-2"></div>
            @endif
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
    @include('auth.admindashboard.partials.enroll-student-modal')
    @include('auth.admindashboard.partials.add-announcement-modal')
    @include('auth.admindashboard.partials.add-schedule-modal')
    @include('auth.admindashboard.partials.add-tuition-modal') {{-- ensure this uses route("tuitions.store") --}}
@endsection

@push('scripts')

{{-- SweetAlert2 for delete confirmations (optional)
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Single, unified delete confirm to match your global design
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form.delete-form');
            if (!form) return;

            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            Swal.fire({
                title: 'Are you sure?',
                text: message,
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
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script> --}}


<!-- Optional: Bootstrap JS and dependencies (Popper) -->
<script>

        function attachTableFeatures(tableId, searchInputId, opts = {}) {
            const maxVisible = opts.maxVisible ?? 5;
            const toggleWrapId = opts.toggleId || null;

            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const headers = table.querySelectorAll('thead th');
            const searchInput = document.getElementById(searchInputId);
            const rows = Array.from(tbody.querySelectorAll('tr'));

            let collapsed = true; // default collapsed
            let currentSort = { index: null, dir: 'asc', type: 'text' };

            // Sorting
            headers.forEach((th, idx) => {
                th.style.cursor = 'pointer';
                th.dataset.sortDir = 'none';
                th.addEventListener('click', () => {
                    const type = th.dataset.type || 'text';
                    const dir = (th.dataset.sortDir === 'asc') ? 'desc' : 'asc';
                    headers.forEach(h => { h.dataset.sortDir = 'none'; h.classList.remove('sorted-asc', 'sorted-desc'); });
                    th.dataset.sortDir = dir;
                    th.classList.add(dir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                    currentSort = { index: idx, dir, type };
                    applySort();
                    applyFilterAndSlice();
                });
            });

            function compareValues(a, b, type, dir) {
                const m = dir === 'asc' ? 1 : -1;
                if (type === 'number') {
                    const n1 = parseFloat(String(a).replace(/[^0-9.\-]/g, '')) || 0;
                    const n2 = parseFloat(String(b).replace(/[^0-9.\-]/g, '')) || 0;
                    return (n1 === n2 ? 0 : (n1 > n2 ? 1 : -1)) * m;
                }
                const s1 = String(a).toLowerCase(), s2 = String(b).toLowerCase();
                return (s1 === s2 ? 0 : (s1 > s2 ? 1 : -1)) * m;
            }

            function applySort() {
                const { index, dir, type } = currentSort;
                if (index === null) return;
                rows.sort((a, b) => {
                    const A = a.children[index]?.innerText.trim() ?? '';
                    const B = b.children[index]?.innerText.trim() ?? '';
                    return compareValues(A, B, type, dir);
                });
                rows.forEach(r => tbody.appendChild(r));
            }

            // Search + slice logic
            function applyFilterAndSlice() {
                const q = (searchInput ? searchInput.value.trim().toLowerCase() : '');
                let visibleMatches = 0;
                let totalMatches = 0;

                rows.forEach((row) => {
                    const matches = row.innerText.toLowerCase().includes(q);
                    row.dataset.match = matches ? '1' : '0';
                });

                const matchedRows = rows.filter(r => r.dataset.match === '1');
                totalMatches = matchedRows.length;

                if (!q && collapsed) {
                    // show only first maxVisible
                    matchedRows.forEach((row, idx) => {
                        row.style.display = idx < maxVisible ? '' : 'none';
                        if (idx < maxVisible) visibleMatches++;
                    });
                } else {
                    // show all matches
                    matchedRows.forEach(row => { row.style.display = ''; });
                    visibleMatches = matchedRows.length;
                }

                // hide all non-matching
                rows.filter(r => r.dataset.match === '0').forEach(r => r.style.display = 'none');

                updateToggle(totalMatches, visibleMatches, q.length > 0);
            }

            // Toggle button
            function updateToggle(totalMatches, visibleMatches, isSearching) {
                if (!toggleWrapId) return;
                const wrap = document.getElementById(toggleWrapId);
                if (!wrap) return;

                wrap.innerHTML = '';

                // If searching, or there are <= maxVisible matches, no need to show toggle
                if (isSearching || totalMatches <= (collapsed ? maxVisible : 0)) return;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary btn-sm';

                if (collapsed) {
                    const remaining = Math.max(totalMatches - visibleMatches, 0);
                    btn.innerHTML = `<i class="bi bi-chevron-down me-1"></i> Show more (${remaining})`;
                } else {
                    btn.innerHTML = `<i class="bi bi-chevron-up me-1"></i> Show less`;
                }

                btn.addEventListener('click', () => {
                    collapsed = !collapsed;
                    applyFilterAndSlice();
                });

                wrap.appendChild(btn);
            }

            // Hook up search
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    applyFilterAndSlice();
                });
            }

            // Initial render
            applyFilterAndSlice();
        }

        /**
         * Simple show more/less for lists (e.g., announcements)
         */
        function attachListShowMore(listId, toggleWrapId, maxVisible = 5) {
            const ul = document.getElementById(listId);
            const wrap = document.getElementById(toggleWrapId);
            if (!ul || !wrap) return;

            const items = Array.from(ul.querySelectorAll('li'));
            if (items.length <= maxVisible) {
                wrap.innerHTML = '';
                return; // nothing to collapse
            }

            let collapsed = true;

            function render() {
                items.forEach((li, idx) => {
                    li.style.display = (collapsed && idx >= maxVisible) ? 'none' : '';
                });
                wrap.innerHTML = '';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary btn-sm';
                if (collapsed) {
                    btn.innerHTML = `<i class="bi bi-chevron-down me-1"></i> Show more (${items.length - maxVisible})`;
                } else {
                    btn.innerHTML = `<i class="bi bi-chevron-up me-1"></i> Show less`;
                }
                btn.addEventListener('click', () => {
                    collapsed = !collapsed;
                    render();
                });
                wrap.appendChild(btn);
            }

            render();
        }

        // ===== Initialize features on DOM ready =====
        document.addEventListener('DOMContentLoaded', function () {
            // Tables: search + sort + show more/less
            attachTableFeatures('scheduleTable', 'scheduleSearch', { maxVisible: 5, toggleId: 'scheduleToggle' });
            attachTableFeatures('tuitionTable', 'tuitionSearch', { maxVisible: 5, toggleId: 'tuitionToggle' });

            // Announcements list (optional show more/less)
            attachListShowMore('announcementsList', 'announcementsToggle', 5);
        });
    </script>
@endpush
