@extends('layouts.admin')

@section('title', 'Students by Grade Level')

@section('content')
    <div class="card section p-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Students (Grouped by Grade Level)</h4>
                <p class="mb-0 text-muted">Browse, edit, or archive students. Use the search to filter across all groups.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Search students...">
            </div>
        </div>

        @php
            // Build a quick lookup from Tuitions by grade level (e.g., "Grade 1" => tuition row)
            $tuitionMap = collect($tuitions ?? collect())->keyBy('grade_level');
        @endphp

        @forelse($students as $grade => $group)
            <div class="card mt-3 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">
                        {{ $grade ?: '— No Grade —' }}
                        <span class="text-muted">({{ $group->count() }})</span>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle student-table">
                        <thead class="table-primary">
                            <tr>
                                <th>Name</th>
                                <th>Birthdate</th>
                                <th>Guardian</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Tuition (Yearly)</th>
                                <th>Enrollment</th>
                                <th>Payment</th>
                                <th class="text-nowrap">Tools</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group as $s)
                                @php
                                    // Prefer tuition pulled from the Tuition table by grade level; fallback to stored s_tuition_sum
                                    $row = $tuitionMap->get($s->s_gradelvl);
                                    $tuitionAmount = $row
                                        ? (float) $row->total_yearly
                                        : ($s->s_tuition_sum !== null && $s->s_tuition_sum !== '' ? (float) $s->s_tuition_sum : null);
                                @endphp
                                <tr>
                                    <td>{{ $s->s_firstname }} {{ $s->s_middlename }} {{ $s->s_lastname }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') }}</td>
                                    <td>{{ ($s->guardian->g_firstname ?? '') }} {{ ($s->guardian->g_lastname ?? '') }}</td>
                                    <td>{{ $s->s_contact ?? '—' }}</td>
                                    <td>{{ $s->s_email ?? '—' }}</td>
                                    <td>
                                        {{ $tuitionAmount !== null ? number_format($tuitionAmount, 2) : '—' }}
                                    </td>
                                    <td>{{ $s->enrollment_status }}</td>
                                    <td>{{ $s->payment_status ?? '—' }}</td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editStudentModal"
                                                data-id="{{ $s->id }}"
                                                data-firstname="{{ $s->s_firstname }}"
                                                data-middlename="{{ $s->s_middlename }}"
                                                data-lastname="{{ $s->s_lastname }}"
                                                data-gradelvl="{{ $s->s_gradelvl }}"
                                                data-birthdate="{{ $s->s_birthdate }}"
                                                data-address="{{ $s->s_address }}"
                                                data-contact="{{ $s->s_contact }}"
                                                data-email="{{ $s->s_email }}"
                                                data-guardian="{{ ($s->guardian->g_firstname ?? '').' '.($s->guardian->g_lastname ?? '') }}"
                                                data-guardianemail="{{ $s->guardian->g_email ?? '' }}"
                                                data-status="{{ $s->enrollment_status }}"
                                                data-payment="{{ $s->payment_status }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form action="{{ route('students.destroy', $s->id) }}" method="POST"
                                              class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" title="Archive">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card mt-3 p-3">
                <p class="text-muted mb-0">No students found.</p>
            </div>
        @endforelse
    </div>

    {{-- Edit modal needs grade levels and tuitions; pass safe fallbacks to avoid "Undefined variable" --}}
    @include('auth.admindashboard.partials.edit-student-modal', [
        'gradelvls' => $gradelvls ?? collect(),
        'tuitions'  => $tuitions  ?? collect(),
    ])

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global search across all grade-level tables
        document.getElementById('studentSearch').addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.student-table tbody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // Archive confirm (same look & feel as your Manage Accounts page)
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form');
                Swal.fire({
                title: 'Are you sure to delete this student record?',
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

