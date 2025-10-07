@foreach($faculties as $faculty)
    @foreach($faculty->schedules as $schedule)
        <div class="modal fade" id="editFacultyScheduleModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('schedules.update', $schedule->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Schedule for {{ $faculty->f_firstname }} {{ $faculty->f_lastname }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Subject</label>
                                    <select name="subject_id" class="form-select" required>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ $schedule->subject_id == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->subject_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Grade Level</label>
                                    <select name="gradelvl_id" class="form-select" required>
                                        @foreach($gradelvls as $gradelvl)
                                            <option value="{{ $gradelvl->id }}" {{ $schedule->gradelvl_id == $gradelvl->id ? 'selected' : '' }}>
                                                {{ $gradelvl->grade_level }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>



                                <div class="col-md-6">
                                    <label class="form-label">Day</label>
                                    <input type="text" name="day" class="form-control" value="{{ $schedule->day }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Class Start</label>
                                    <input type="time" name="class_start" class="form-control" value="{{ $schedule->class_start }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Class End</label>
                                    <input type="time" name="class_end" class="form-control" value="{{ $schedule->class_end }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Update Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endforeach
