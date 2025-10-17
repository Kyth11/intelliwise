@extends('layouts.guardian')

@section('title', 'Children')

@push('styles')
    {{-- Use the shared dashboard stylesheet you attached --}}
    <link rel="stylesheet" href="{{ asset('css/app-dashboard.css') }}">
@endpush

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;

        $user       = Auth::user();
        $isGuardian = $user && ($user->role === 'guardian');

        // If the controller didn't pass $guardian, fetch it here with grade relation.
        $guardianModel = $guardian
            ?? ($isGuardian && $user->guardian_id
                ? \App\Models\Guardian::with('students.gradelvl')->find($user->guardian_id)
                : null);

        // "Default guardian" = username exactly 'guardian' OR has no linked students
        $isDefaultGuardian = $isGuardian && (
            (($user->username ?? null) === 'guardian')
            || ($guardianModel && $guardianModel->students->isEmpty())
        );

        if ($isGuardian) {
            if ($isDefaultGuardian) {
                // Show ALL students, ordered
                $children = \App\Models\Student::with('gradelvl')
                    ->orderBy('s_lastname')->orderBy('s_firstname')
                    ->get();
            } else {
                // Only this guardian's children
                $children = optional($guardianModel)->students ?? collect();
                if ($children instanceof \Illuminate\Database\Eloquent\Collection) {
                    $children->loadMissing('gradelvl');
                }
            }
        } else {
            // Non-guardian fallback
            $children = \App\Models\Student::with('gradelvl')
                ->orderBy('s_lastname')->orderBy('s_firstname')
                ->get();
        }

        // KPI helpers
        $totalChildren = $children->count();
        $enrolledCount = $children->where('enrollment_status', 'Enrolled')->count();
        $uniqueGrades  = $children->map(function ($c) {
            return $c->s_gradelvl ?? optional($c->gradelvl)->grade_level;
        })->filter()->unique()->values();
        $gradeCount    = $uniqueGrades->count();
    @endphp

    <div class="card section p-4">
        <!-- =========================
             Header: Intro | KPIs | Right: Action
        ========================== -->
        <div id="dashboard-header" class="mb-3">
            <!-- Intro -->
            <div class="intro">
                <div>
                    <h5 class="mb-1">Children</h5>
                    <div class="text-muted small">
                        @if($isGuardian && $isDefaultGuardian)
                            You’re viewing all learners (default guardian).
                        @elseif($isGuardian)
                            Your linked learners at a glance.
                        @else
                            Learners overview.
                        @endif
                    </div>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $totalChildren }}</div>
                    <div class="kpi-label">Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $enrolledCount }}</div>
                    <div class="kpi-label">Enrolled</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $gradeCount }}</div>
                    <div class="kpi-label">Grade Levels</div>
                </div>
            </div>

            <!-- Action card -->
            <div class="pay-card p-3 text-center">
                <h6 class="mb-1">Quick Action</h6>
                <p class="text-muted mb-3 small">Print your current list.</p>
                <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print List
                </button>
            </div>
        </div>

        <!-- Notices -->
        @if($isGuardian && $isDefaultGuardian)
            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-people-fill me-2"></i>
                You are logged in as the default guardian. All enrolled students are shown below.
            </div>
        @elseif($isGuardian)
            <p class="mb-3 text-muted">Manage your children’s records here.</p>
        @endif

        <!-- =========================
             Children Table
        ========================== -->
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Learners</h6>
                @if($isGuardian && $isDefaultGuardian)
                    <span class="badge bg-secondary">All Students</span>
                @elseif($isGuardian)
                    <span class="badge bg-primary">Your Learners</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:260px;">Name</th>
                            <th>Grade Level</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($children as $child)
                        @php
                            // Name
                            $name = $child->full_name
                                ?? trim(implode(' ', array_filter([
                                    $child->s_firstname ?? '',
                                    $child->s_middlename ?? '',
                                    $child->s_lastname ?? ''
                                ])));
                            if ($name === '') $name = 'Student #'.$child->id;

                            // Grade
                            $grade = $child->s_gradelvl
                                ?? optional($child->gradelvl)->grade_level
                                ?? '—';

                            // Enrollment status
                            $status = $child->enrollment_status ?? '—';
                            $statusClass = match ($status) {
                                'Enrolled'     => 'bg-success',
                                'Not Enrolled' => 'bg-secondary',
                                default        => 'bg-light text-dark border'
                            };
                        @endphp
                        <tr>
                            <td>{{ $name }}</td>
                            <td>{{ $grade }}</td>
                            <td class="text-center"><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
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
    </div>
@endsection
