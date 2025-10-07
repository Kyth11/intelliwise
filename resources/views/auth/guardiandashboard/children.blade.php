@extends('layouts.guardian')

@section('title', 'Children')

@push('styles')
    {{-- (Optional) Bootstrap Icons if your base layout doesn’t already include them --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
@endpush

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;

        $user = Auth::user();
        $isGuardian = $user && $user->role === 'guardian';

        // If the controller didn't pass $guardian, fetch it here.
        $guardianModel = $guardian
            ?? ($isGuardian && $user->guardian_id
                ? \App\Models\Guardian::with('students.gradelvl')->find($user->guardian_id)
                : null);

        // "Default guardian" rule:
        //  - username exactly 'guardian'  OR
        //  - guardian exists but has no linked students
        $isDefaultGuardian = $isGuardian && (
            (($user->username ?? null) === 'guardian') ||
            ($guardianModel && $guardianModel->students->isEmpty())
        );

        if ($isGuardian) {
            if ($isDefaultGuardian) {
                // Show ALL students
                $children = \App\Models\Student::with('gradelvl')
                    ->orderBy('s_lastname')
                    ->orderBy('s_firstname')
                    ->get();
            } else {
                // Only this guardian's children
                $children = optional($guardianModel)->students ?? collect();
                // Ensure grade level relation is available
                if ($children instanceof \Illuminate\Database\Eloquent\Collection) {
                    $children->loadMissing('gradelvl');
                }
            }
        } else {
            // Non-guardian (just in case): show all
            $children = \App\Models\Student::with('gradelvl')
                ->orderBy('s_lastname')
                ->orderBy('s_firstname')
                ->get();
        }
    @endphp

    <div class="topbar d-flex align-items-center justify-content-between">
        <h3 class="mb-3">Children</h3>

        @if($isGuardian && $isDefaultGuardian)
            <span class="badge bg-secondary">
                Viewing: All Students (default guardian)
            </span>
        @elseif($isGuardian)
            <span class="badge bg-primary">
                Viewing: Your Learners
            </span>
        @endif
    </div>

    <div class="card p-4">
        @if($isGuardian && !$isDefaultGuardian)
            <p class="mb-3">Manage your children’s records here.</p>
        @elseif($isGuardian && $isDefaultGuardian)
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-people-fill me-2"></i>
                You are logged in as the default guardian. All enrolled students are shown below.
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px;">Name</th>
                        <th>Grade Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($children as $child)
                    @php
                        // Prefer accessor if present, else build a name safely
                        $name = $child->full_name
                            ?? trim(implode(' ', array_filter([
                                $child->s_firstname ?? '',
                                $child->s_middlename ?? '',
                                $child->s_lastname ?? ''
                            ])));
                        if ($name === '') $name = 'Student #'.$child->id;

                        $grade = $child->s_gradelvl
                            ?? optional($child->gradelvl)->grade_level
                            ?? '—';

                        $status = $child->enrollment_status ?? '—';
                        $statusClass = $status === 'Enrolled'
                            ? 'bg-success'
                            : ($status === 'Not Enrolled' ? 'bg-secondary' : 'bg-light text-dark border');
                    @endphp
                    <tr>
                        <td>{{ $name }}</td>
                        <td>{{ $grade }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No students found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
