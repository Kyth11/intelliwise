<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('schedules.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-3">
                    @php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; @endphp

                    <div class="col-md-4">
                        <label for="day" class="form-label">Day</label>
                        <select name="day" id="day" class="form-select" required>
                            <option value="" class="dropdownheader">-- Select Day --</option>
                            @foreach($days as $d)
                                <option value="{{ $d }}" {{ old('day') === $d ? 'selected' : '' }}>
                                    {{ $d }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="class_start" class="form-label">Class Start</label>
                        <input type="time" name="class_start" id="class_start" class="form-control"
                            value="{{ old('class_start') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="class_end" class="form-label">Class End</label>
                        <input type="time" name="class_end" id="class_end" class="form-control"
                            value="{{ old('class_end') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Faculty</label>
                        <select name="faculty_id" class="form-select" required>
                            <option value="" class="dropdownheader">-- Select Faculty --</option>
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>
                                    {{ $faculty->user->name ?? ($faculty->f_firstname . ' ' . $faculty->f_lastname) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="" class="dropdownheader">-- Select Subject --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Grade Level</label>
                        <select name="gradelvl_id" class="form-select">
                            <option value="" class="dropdownheader">Select Grade Level</option>
                            @foreach($gradelvls as $g)
                                <option value="{{ $g->id }}" {{ old('gradelvl_id') == $g->id ? 'selected' : '' }}>
                                    {{ $g->grade_level }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">School Year (optional)</label>
                        <select name="school_year" class="form-select">
                            <option value="">— None —</option>
                            @foreach($schoolyrs as $sy)
                                <option value="{{ $sy->school_year }}" {{ old('school_year') === $sy->school_year ? 'selected' : '' }}>
                                    {{ $sy->school_year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
