@extends('layouts.admin')

@section('title', 'Faculty Management')

@section('content')
    <div class="card section p-4">
        <h4>Faculty Management</h4>
        <p>Here you can view, edit, and manage faculty accounts.</p>

        <div class="card mt-3 p-3">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Faculty ID</th>
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
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Grade Level</th>
                                                <th>Section</th>
                                                <th>Room</th>
                                                <th>Day / Time</th>
                                                <th style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($faculty->schedules as $schedule)
                                                <tr>
                                                    <td>{{ $schedule->subject->subject_name ?? '-' }}</td>
                                                    <td>{{ $schedule->gradelvl->grade_level ?? '-' }}</td>
                                                    <td>{{ $schedule->section->section_name ?? '-' }}</td>
                                                    <td>{{ $schedule->room->room_number ?? '-' }}</td>
                                                    <td>{{ $schedule->day }} {{ $schedule->class_start }}-{{ $schedule->class_end }}
                                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        <div class="d-flex gap-2 justify-content-center">
                                                            <!-- EDIT: unified design (small, warning, pencil icon) -->
                                                            <button class="btn btn-sm btn-warning" title="Edit Schedule"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editFacultyScheduleModal{{ $schedule->id }}">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>

                                                            <!-- DELETE: unified design (small, danger, archive icon) -->
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

    {{-- SweetAlert2 --}}
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
    </script>
@endsection
