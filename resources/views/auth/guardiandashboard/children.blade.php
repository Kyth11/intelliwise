@extends('layouts.guardian')

@section('title', 'Children')

@push('styles')
    {{-- Use the shared dashboard stylesheet you attached --}}
    <link rel="stylesheet" href="{{ asset('css/app-dashboard.css') }}">
@endpush

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;
        use Illuminate\Support\Facades\DB;

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



        $result = DB::select(
            "SELECT
                s.lrn,
                s.s_firstname,
                s.s_middlename,
                s.s_lastname,
                s.s_birthdate,
                s.s_gender,
                s.enrollment_status,
                en.*
            FROM students s
            LEFT JOIN (
                SELECT 
                    rs.student_id,
                    MAX(rs.id) AS last_registrar_id,
                    MAX(rs.curriculum_id) AS curriculum_id,
                    MAX(sy.school_year) AS school_year,
                    MAX(g.grade_level) AS grade_level,
                    MAX(u.`name`) AS adviser_name
                FROM registrar_student rs
                LEFT JOIN curriculum c ON c.id = rs.curriculum_id
                LEFT JOIN schoolyrs sy ON sy.id = c.schoolyr_id
                LEFT JOIN gradelvls g ON g.id = c.grade_id
                LEFT JOIN faculties f ON f.id = c.adviser_id
                LEFT JOIN users u ON u.faculty_id = f.id
                GROUP BY rs.student_id
            ) en ON en.student_id COLLATE utf8mb4_unicode_ci = s.lrn COLLATE utf8mb4_unicode_ci where s.guardian_id = ?",
            [$user->guardian_id]
        );

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
                            <th>Student ID</th>
                            <th style="min-width:260px;">Name</th>
                            <th>Current School Year</th>
                            <th>Current Grade Level</th>
                            <th>Current Adviser</th>
                            <th>Balance</th>
                            <th class="text-center">Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($result as $child)
                        @php
                            // Name
                            $name = trim(implode(' ', array_filter([
                                    $child->s_firstname ?? '',
                                    $child->s_middlename ?? '',
                                    $child->s_lastname ?? ''
                                ])));
                          

                            // Enrollment status
                            $status = $child->enrollment_status ?? '—';
                            $statusClass = match ($status) {
                                'Enrolled'     => 'bg-success',
                                'Not Enrolled' => 'bg-secondary',
                                default        => 'bg-light text-dark border'
                            };
                        @endphp
                        <tr>
                            <td>{{ $child->lrn }}</td>
                            <td>{{ $name }}</td>
                            <td>{{ $child->school_year }}</td>
                            <td>{{ $child->grade_level }}</td>
                            <td>{{ $child->adviser_name }}</td>
                            <td>0.00</td>
                            <td class="text-center"><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary openModalDetails" data-id="{{ $child->lrn }}" data-curriculum_id="{{ $child->curriculum_id }}"> <i class="bi bi-eye">SCHEDULED</i> </button>
                                <button class="btn btn-sm btn-warning openModalDetails" data-id="{{ $child->lrn }}" data-curriculum_id="{{ $child->curriculum_id }}"> <i class="bi bi-eye">PAYMENT</i> </button>
                                <button class="btn btn-sm btn-info openModalDetails" data-id="{{ $child->lrn }}" data-curriculum_id="{{ $child->curriculum_id }}"> <i class="bi bi-eye">TOR</i> </button>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No students found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
   


    <script>
       
          $(document).on('click', '.openModalDetails', function() {
           

            var action = "{{ route('guardians.getCurriculumSubjects') }}";

            var dataObj  = {
                'id' : $(this).data('curriculum_id')
            };

            // Convert the data object into FormData
            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}'); // ← REQUIRED
            for (const key in dataObj) {
                if (dataObj.hasOwnProperty(key)) {
                    formData.append(key, dataObj[key]);
                }   
            }

            var request = main.send_ajax(formData, action, 'POST', true);
            request.done(function (data) {
                var button = '';
                main.modalOpen('View Scheduled', data.html, button,'','modal-xl')

                // $('.modalOpenCustom .modal-body').html('');
                // $('.modalOpenCustom .modal-body').html(data.html);


            });


        });

      
        
    </script>



@endpush
