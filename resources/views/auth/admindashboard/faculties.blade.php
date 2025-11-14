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
                ->map(fn($s) => optional($s->subject)->name)
                ->filter()
                ->unique()
                ->count();
        @endphp

        <!-- =========================
             Header: Intro | KPIs | Right: Quick Actions
        ========================== -->
        <div id="dashboard-header" class="mb-3 d-grid gap-3" style="grid-template-columns: 1fr auto;">
            <!-- Intro + KPIs -->
            <div>
                <div class="intro mb-3">
                    <h5 class="mb-1">Faculty Schedule Management</h5>
                    <div class="text-muted small">View, edit, and manage faculty schedules.</div>
                </div>

                <!-- KPI strip -->
                <div class="kpi-strip d-flex gap-2">
                    <div class="kpi-card border rounded p-3 text-center">
                        <div class="kpi-number fs-4 fw-bold">{{ $facCount }}</div>
                        <div class="kpi-label text-muted small">Faculty</div>
                    </div>
                    <div class="kpi-card border rounded p-3 text-center">
                        <div class="kpi-number fs-4 fw-bold">{{ $schedCount }}</div>
                        <div class="kpi-label text-muted small">Total Schedules</div>
                    </div>
                    <div class="kpi-card border rounded p-3 text-center">
                        <div class="kpi-number fs-4 fw-bold">{{ $subjectCount }}</div>
                        <div class="kpi-label text-muted small">Subjects</div>
                    </div>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="right-stack" style="width: 320px;">
                <div class="card quick-actions p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="position-relative mb-2">
                        <i class="bi bi-search icon-left" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.6"></i>
                        <input
                            type="text"
                            id="qaFacultySearch"
                            class="form-control form-control-sm ps-5"
                            placeholder="Filter faculty… (press Enter)">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="bi bi-calendar-plus me-1"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
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
                        @foreach ($faculties as $faculty)
                            <tr>
                                <td class="text-center">{{ $faculty->id }}</td>
                                <td>{{ $faculty->first_name }} {{ $faculty->last_name }}</td>
                                <td>{{ $faculty->email ?? '-' }}</td>
                                <td>{{ $faculty->contact ?? '-' }}</td>
                                <td>
                                    @if($faculty->schedules && $faculty->schedules->isNotEmpty())
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
                                                            <td>{{ optional($schedule->subject)->name ?? '-' }}</td>
                                                            <td>{{ optional($schedule->gradelvl)->name ?? '-' }}</td>
                                                            <td>
                                                                {{ $schedule->day_of_week ?? '-' }}
                                                                @php
                                                                    $st = $schedule->start_time ? substr($schedule->start_time, 0, 5) : '';
                                                                    $et = $schedule->end_time ? substr($schedule->end_time, 0, 5) : '';
                                                                @endphp
                                                                {{ $st && $et ? " $st-$et" : '' }}
                                                            </td>
                                                            <td class="text-center text-nowrap">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <!-- EDIT -->
                                                                    <button
                                                                        class="btn btn-sm btn-warning"
                                                                        title="Edit Schedule"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editFacultyScheduleModal{{ $schedule->id }}">
                                                                        <i class="bi bi-pencil-square"></i>
                                                                    </button>

                                                                    <!-- DELETE -->
                                                                    <form
                                                                        action="{{ route('admin.schedules.destroy', $schedule->id) }}"
                                                                        method="POST"
                                                                        class="d-inline delete-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-danger delete-btn"
                                                                            title="Delete Schedule"
                                                                            data-confirm="Delete this schedule?">
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
                        @endforeach
                        {{-- IMPORTANT: no colspan "empty" row; DataTables shows emptyTable message --}}
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

        {{-- Add Schedule modal --}}
        @includeIf('auth.admindashboard.partials.add-schedule-modal')
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Delete confirm (SweetAlert2)
        document.querySelectorAll('.delete-btn').forEach((button) => {
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

        // DataTables init
        $(function () {
            const table = $('#facultySchedTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                columnDefs: [
                    { targets: -1, orderable: false } // make last column (Schedules) unsortable
                ],
                language: {
                    emptyTable: 'No faculty records found.'
                }
            });

            // Quick filter (press Enter)
            const qa = document.getElementById('qaFacultySearch');
            qa?.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                table.search(qa.value || '').draw();
            });
        });
    </script>
@endpush
