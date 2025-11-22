{{-- resources/views/auth/admindashboard/partials/scheduleModal.blade.php --}}

{{-- =========================
     ADD SCHEDULE MODAL
========================= --}}
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.schedules.store') }}" method="POST" id="addScheduleForm">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Faculty <span class="text-danger">*</span></label>
                            <select name="faculty_id" class="form-select" required>
                                @foreach(($faculties ?? collect()) as $f)
                                    @php
                                        $fname = trim(($f->first_name ?? $f->f_firstname ?? '') . ' ' . ($f->last_name ?? $f->f_lastname ?? ''));
                                        if ($fname === '') {
                                            $fname = 'Faculty #'.$f->id;
                                        }
                                    @endphp
                                    <option value="{{ $f->id }}" {{ (string)old('faculty_id') === (string)$f->id ? 'selected' : '' }}>
                                        {{ $fname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select name="gradelvl_id" class="form-select js-sched-grade-select" required>
                                <option value="">Select Grade Level</option>
                                @foreach(($gradelvls ?? collect()) as $gl)
                                    <option value="{{ $gl->id }}" {{ (string)old('gradelvl_id') === (string)$gl->id ? 'selected' : '' }}>
                                        {{ $gl->grade_level }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    <p class="small text-muted mb-2">
                        All subjects for the selected grade level will appear below. Click days to add time slots
                        (each day has its own start and end time). Use “×” to remove a day for a subject.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Subject</th>
                                    <th style="width: 55%;">Day and Time</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleSubjectsBody">
                                {{-- rows injected by JS based on selected grade level --}}
                            </tbody>
                        </table>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3 mb-0">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =========================
     EDIT SCHEDULE MODALS (per schedule)
========================= --}}
@foreach($faculties as $faculty)
    @foreach($faculty->schedules as $schedule)
        @php
            /** @var \App\Models\Schedule $schedule */
            $currentFacultyId  = $schedule->faculty_id ?? optional($schedule->faculty)->id;
            $currentSubjectId  = $schedule->subject_id ?? optional($schedule->subject)->id;
            $currentGradelvlId = $schedule->gradelvl_id ?? optional($schedule->gradelvl)->id;

            // Time inputs: HH:MM
            $startVal = old('class_start', $schedule->class_start);
            $endVal   = old('class_end',   $schedule->class_end);
            if ($startVal && strlen($startVal) > 5) $startVal = substr($startVal, 0, 5);
            if ($endVal   && strlen($endVal)   > 5) $endVal   = substr($endVal,   0, 5);

            $dayVal = old('day', $schedule->day);
            // Sunday removed
            $days   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        @endphp

        <div class="modal fade" id="editScheduleModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    {{-- UPDATE --}}
                    <form id="updateSchedule{{ $schedule->id }}"
                          action="{{ route('admin.schedules.update', $schedule->id) }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Schedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Day <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100 flex-wrap" role="group">
                                        @foreach($days as $d)
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm day-toggle-btn {{ $dayVal === $d ? 'active' : '' }}"
                                                    data-mode="edit"
                                                    data-day="{{ $d }}"
                                                    data-target-input="edit-day-{{ $schedule->id }}">
                                                {{ $d }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <input type="hidden"
                                           name="day"
                                           id="edit-day-{{ $schedule->id }}"
                                           value="{{ $dayVal }}"
                                           required>
                                </div>

                                <div class="col-sm-3">
                                    <label class="form-label">Class Start <span class="text-danger">*</span></label>
                                    <input type="time" name="class_start" class="form-control"
                                           step="60" value="{{ $startVal }}" required>
                                </div>

                                <div class="col-sm-3">
                                    <label class="form-label">Class End <span class="text-danger">*</span></label>
                                    <input type="time" name="class_end" class="form-control"
                                           step="60" value="{{ $endVal }}" required>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label">Faculty <span class="text-danger">*</span></label>
                                    <select name="faculty_id" class="form-select" required>
                                        @foreach(($faculties ?? collect()) as $f)
                                            @php
                                                $fname = trim(($f->first_name ?? $f->f_firstname ?? '') . ' ' . ($f->last_name ?? $f->f_lastname ?? ''));
                                                if ($fname === '') {
                                                    $fname = 'Faculty #'.$f->id;
                                                }
                                            @endphp
                                            <option value="{{ $f->id }}"
                                                {{ (string)old('faculty_id', $currentFacultyId) === (string)$f->id ? 'selected' : '' }}>
                                                {{ $fname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select name="subject_id" class="form-select" required>
                                        @foreach(($subjects ?? collect()) as $s)
                                            <option value="{{ $s->id }}"
                                                {{ (string)old('subject_id', $currentSubjectId) === (string)$s->id ? 'selected' : '' }}>
                                                {{ $s->subject_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                                    <select name="gradelvl_id" class="form-select" required>
                                        @foreach(($gradelvls ?? collect()) as $gl)
                                            <option value="{{ $gl->id }}"
                                                {{ (string)old('gradelvl_id', $currentGradelvlId) === (string)$gl->id ? 'selected' : '' }}>
                                                {{ $gl->grade_level }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger mt-3 mb-0">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Update</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endforeach
@endforeach
