@extends('layouts.admin')

@section('title', 'Manage Accounts')

@section('content')
    <div class="card section p-4">
        <h4>Manage Accounts</h4>
        <p>Here you can add, edit, or delete system users.</p>

        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                <i class="bi bi-person-badge me-2"></i> Add Faculty Account
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGuardianModal">
                <i class="bi bi-people-fill me-2"></i> Add Guardian Account
            </button>
        </div>

        {{-- Faculty Accounts --}}
        <div class="card mt-3 p-3">
            <h5>Faculty Accounts</h5>
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Username</th>

                        <th>Created At</th>
                        <th>Tools</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faculties as $f)
                        <tr>
                            <td>{{ $f->f_firstname }} {{ $f->f_middlename }} {{ $f->f_lastname }}</td>
                            <td>{{ $f->f_contact }}</td>
                            <td>{{ $f->f_address }}</td>
                            <td>{{ $f->f_email ?? '-' }}</td>
                            <td>{{ $f->user->username ?? '-' }}</td>

                            <td>{{ $f->created_at->format('Y-m-d') }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editFacultyModal"
                                    data-id="{{ $f->id }}" data-firstname="{{ $f->f_firstname }}"
                                    data-middlename="{{ $f->f_middlename }}" data-lastname="{{ $f->f_lastname }}"
                                    data-contact="{{ $f->f_contact }}" data-address="{{ $f->f_address }}"
                                    data-email="{{ $f->f_email }}" data-username="{{ $f->user->username ?? '' }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form action="{{ route('faculties.destroy', $f->id) }}" method="POST"
                                    class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger delete-btn">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No faculty accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Guardian Accounts --}}
        <div class="card mt-4 p-3">
            <h5>Guardian Accounts</h5>
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Username</th>

                        <th>Created At</th>
                        <th>Tools</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardians as $g)
                        <tr>
                            <td>{{ $g->g_firstname }} {{ $g->g_middlename }} {{ $g->g_lastname }}</td>
                            <td>{{ $g->g_contact }}</td>
                            <td>{{ $g->g_address }}</td>
                            <td>{{ $g->g_email ?? '-' }}</td>
                            <td>{{ $g->user->username ?? '-' }}</td>

                            <td>{{ $g->created_at->format('Y-m-d') }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#editGuardianModal" data-id="{{ $g->id }}"
                                    data-firstname="{{ $g->g_firstname }}" data-middlename="{{ $g->g_middlename }}"
                                    data-lastname="{{ $g->g_lastname }}" data-contact="{{ $g->g_contact }}"
                                    data-address="{{ $g->g_address }}" data-email="{{ $g->g_email }}"
                                    data-username="{{ $g->user->username ?? '' }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form action="{{ route('guardians.destroy', $g->id) }}" method="POST"
                                    class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger delete-btn">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No guardian accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modals --}}
    @include('auth.admindashboard.partials.accounts-modals')

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form');
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
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>

@endsection
