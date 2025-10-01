<form action="{{ route('schedules.update', $schedule->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-header">
        <h5 class="modal-title">Edit Schedule (ID: {{ $schedule->id }})</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body row g-3">
        @php
            use Illuminate\Support\Carbon;
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $selectedDay = old('day', $schedule->day);

            // Format DB times (HH:MM:SS) to HH:MM for the <input type="time">
            $startValue = old('class_start',
                $schedule->class_start
                    ? Carbon::parse($schedule->class_start)->format('H:i')
                    : null
            );
            $endValue = old('class_end',
                $schedule->class_end
                    ? Carbon::parse($schedule->class_end)->format('H:i')
                    : null
            );

            // current school year value (old input wins)
            $currentSY = old('school_year', $schedule->school_year);
        @endphp

        <div class="col-md-4">
            <label for="day{{ $schedule->id }}" class="form-label">Day</label>
            <select name="day" id="day{{ $schedule->id }}" class="form-select" required>
                <option value="" class="dropdownheader">-- Select Day --</option>
                @foreach($days as $d)
                    <option value="{{ $d }}" {{ $selectedDay === $d ? 'selected' : '' }}>
                        {{ $d }}
                    </option>
                @endforeach
            </select>
            @error('day') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="class_start{{ $schedule->id }}" class="form-label">Class Start</label>
            <input
                type="time"
                name="class_start"
                id="class_start{{ $schedule->id }}"
                class="form-control"
                step="60"  {{-- minutes only --}}
                value="{{ $startValue }}"
                required
            >
            @error('class_start') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="class_end{{ $schedule->id }}" class="form-label">Class End</label>
            <input
                type="time"
                name="class_end"
                id="class_end{{ $schedule->id }}"
                class="form-control"
                step="60"  {{-- minutes only --}}
                value="{{ $endValue }}"
                required
            >
            @error('class_end') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Faculty</label>
            <select name="faculty_id" class="form-select" required>
                <option value="" class="dropdownheader">-- Select Faculty --</option>
                @foreach($faculties as $faculty)
                    <option value="{{ $faculty->id }}" {{ $faculty->id == $schedule->faculty_id ? 'selected' : '' }}>
                        {{ $faculty->user->name ?? ($faculty->f_firstname . ' ' . $faculty->f_lastname) }}
                    </option>
                @endforeach
            </select>
            @error('faculty_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select" required>
                <option value="" class="dropdownheader">-- Select Subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ $subject->id == $schedule->subject_id ? 'selected' : '' }}>
                        {{ $subject->subject_name }}
                    </option>
                @endforeach
            </select>
            @error('subject_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Room</label>
            <select name="room_id" class="form-select" required>
                <option value="" class="dropdownheader">-- Select Room --</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ $room->id == $schedule->room_id ? 'selected' : '' }}>
                        {{ $room->room_number }}
                    </option>
                @endforeach
            </select>
            @error('room_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Section</label>
            <select name="section_id" class="form-select">
                <option value="" class="dropdownheader">-- Select Section --</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ $section->id == $schedule->section_id ? 'selected' : '' }}>
                        {{ $section->section_name }}
                    </option>
                @endforeach
            </select>
            @error('section_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Grade Level</label>
            <select name="gradelvl_id" class="form-select">
                <option value="" class="dropdownheader">-- Select Grade Level --</option>
                @foreach($gradelvls as $g)
                    <option value="{{ $g->id }}" {{ $g->id == $schedule->gradelvl_id ? 'selected' : '' }}>
                        {{ $g->grade_level }}
                    </option>
                @endforeach
            </select>
            @error('gradelvl_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">School Year (optional)</label>
            <select name="school_year" class="form-select">
                <option value="">— None —</option>
                @foreach($schoolyrs as $sy)
                    <option value="{{ $sy->school_year }}" {{ $currentSY === $sy->school_year ? 'selected' : '' }}>
                        {{ $sy->school_year }}
                    </option>
                @endforeach
            </select>
            @error('school_year') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success">Save Changes</button>
    </div>
</form>
