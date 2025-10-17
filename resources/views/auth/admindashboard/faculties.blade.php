@extends('layouts.admin')

@section('title', 'Faculty Management')

@push('styles')
    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- Global Admin Styles --}}
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div class="card section p-4">

        @php
            $facCount     = $faculties->count();
            $schedCount   = $faculties->sum(fn($f) => $f->schedules?->count() ?? 0);
            $subjectCount = $faculties
                ->flatMap(fn($f) => $f->schedules ?? collect())
                ->filter()
                ->map(fn($s) => optional($s->subject)->subject_name)
                ->filter()
                ->unique()
                ->count();
        @endphp

        <!-- =========================
             Header: Intro | KPIs | Right: Quick Actions
        ========================== -->
        <div id="dashboard-header" class="mb-3">
            <!-- Intro -->
            <div class="intro">
                <div>
                    <h5 class="mb-1">Faculty Schedule Management</h5>
                    <div class="text-muted small">View, edit, and manage faculty schedules.</div>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $facCount }}</div>
                    <div class="kpi-label">Faculty</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $schedCount }}</div>
                    <div class="kpi-label">Total Schedules</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $subjectCount }}</div>
                    <div class="kpi-label">Subjects</div>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="right-stack">
                <div class="card quick-actions p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="position-relative mb-2">
                        <i class="bi bi-search icon-left"></i>
                        <input type="text" id="qaFacultySearch" class="form-control form-control-sm"
                               placeholder="Filter faculty… (press Enter)">
                    </div>
                    <div class="d-grid gap-2">
                        <!-- NEW: Add Schedule (opens the shared modal) -->
                <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class="bi bi-calendar-plus me-1"></i> Add Schedule
                </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3 p-3">
            <div class="table-responsive">
                <table id="facultySchedTable" class="table table-bordered table-striped align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width: 90px;">Faculty ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Schedules</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($faculties as $faculty)
                            <tr>
                                <td class="text-center">{{ $faculty->id }}</td>
                                <td>{{ $faculty->f_firstname }} {{ $faculty->f_lastname }}</td>
                                <td>{{ $faculty->f_email ?? '-' }}</td>
                                <td>{{ $faculty->f_contact ?? '-' }}</td>
                                <td>
                                    @if($faculty->schedules->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light text-center">
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Grade Level</th>
                                                        <th>Day / Time</th>
                                                        <th style="width: 140px;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($faculty->schedules as $schedule)
                                                        <tr>
                                                            <td>{{ $schedule->subject->subject_name ?? '-' }}</td>
                                                            <td>{{ $schedule->gradelvl->grade_level ?? '-' }}</td>
                                                            <td>{{ $schedule->day }} {{ $schedule->class_start }}-{{ $schedule->class_end }}</td>
                                                            <td class="text-center text-nowrap">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <!-- EDIT -->
                                                                    <button class="btn btn-sm btn-warning" title="Edit Schedule"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editFacultyScheduleModal{{ $schedule->id }}">
                                                                        <i class="bi bi-pencil-square"></i>
                                                                    </button>

                                                                    <!-- DELETE -->
                                                                    <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST"
                                                                          class="d-inline delete-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                                                title="Delete Schedule" data-confirm="Delete this schedule?">
                                                                            <i class="bi bi-archive"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <span class="text-muted">No schedules assigned</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No faculty records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modals --}}
        @foreach($faculties as $faculty)
            @foreach($faculty->schedules as $schedule)
                @include('auth.admindashboard.partials.edit-faculty-schedule-modal', ['faculty' => $faculty, 'schedule' => $schedule])
            @endforeach
        @endforeach

        {{-- Add Schedule modal from dashboard --}}
        @includeIf('auth.admindashboard.partials.add-schedule-modal')
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Delete confirm
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form.delete-form');
                if (!form) return;

                const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
                Swal.fire({
                    title: 'Are you sure?',
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
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // DataTables
        $(function () {
            const table = $('#facultySchedTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                columnDefs: [
                    { targets: -1, orderable: false }
                ]
            });

            // Quick filter
            const qa = document.getElementById('qaFacultySearch');
            qa?.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                table.search(qa.value || '').draw();
            });
        });
    </script>
@endpush
