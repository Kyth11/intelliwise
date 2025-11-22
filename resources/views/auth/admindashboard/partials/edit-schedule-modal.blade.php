@php
    /** @var \App\Models\Schedule $schedule */
    $currentFacultyId  = $schedule->faculty_id ?? optional($schedule->faculty)->id;
    $currentSubjectId  = $schedule->subject_id ?? optional($schedule->subject)->id;
    $currentGradelvlId = $schedule->gradelvl_id ?? optional($schedule->gradelvl)->id;

    // Make sure time inputs are HH:MM (no seconds)
    $startVal = old('class_start', $schedule->class_start);
    $endVal   = old('class_end',   $schedule->class_end);
    if ($startVal && strlen($startVal) > 5) $startVal = substr($startVal, 0, 5);
    if ($endVal   && strlen($endVal)   > 5) $endVal   = substr($endVal,   0, 5);

    $dayVal = old('day', $schedule->day);
    $days   = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
@endphp

<div class="modal fade" id="editScheduleModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      {{-- UPDATE (separate form; no nested forms) --}}
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
            <div class="col-sm-4">
              <label class="form-label">Day <span class="text-danger">*</span></label>
              <select name="day" class="form-select" required>
                @foreach($days as $d)
                  <option value="{{ $d }}" {{ $dayVal === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-4">
              <label class="form-label">Class Start <span class="text-danger">*</span></label>
              <input type="time" name="class_start" class="form-control"
                     step="60" value="{{ $startVal }}" required>
            </div>

            <div class="col-sm-4">
              <label class="form-label">Class End <span class="text-danger">*</span></label>
              <input type="time" name="class_end" class="form-control"
                     step="60" value="{{ $endVal }}" required>
            </div>

            <div class="col-sm-6">
              <label class="form-label">Faculty <span class="text-danger">*</span></label>
              <select name="faculty_id" class="form-select" required>
                @foreach(($faculties ?? collect()) as $f)
                  @php
                      $fname = trim(($f->f_firstname ?? '') . ' ' . ($f->f_lastname ?? ''));
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
              <label class="form-label">Grade Level (optional)</label>
              <select name="gradelvl_id" class="form-select">
                <option value="">— None —</option>
                @foreach(($gradelvls ?? collect()) as $gl)
                  <option value="{{ $gl->id }}"
                    {{ (string)old('gradelvl_id', $currentGradelvlId) === (string)$gl->id ? 'selected' : '' }}>
                    {{ $gl->grade_level }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6">
              <label class="form-label">School Year (optional)</label>
              <select name="school_year" class="form-select">
                <option value="">— None —</option>
                @foreach(($schoolyrs ?? collect()) as $sy)
                  <option value="{{ $sy->school_year }}"
                    {{ (string)old('school_year', $schedule->school_year) === (string)$sy->school_year ? 'selected' : '' }}>
                    {{ $sy->school_year }}
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
      </form>

      {{-- DELETE (separate form) --}}
      <form id="deleteSchedule{{ $schedule->id }}"
            action="{{ route('admin.schedules.destroy', $schedule->id) }}"
            method="POST" class="d-none">
        @csrf
        @method('DELETE')
      </form>

      <div class="modal-footer d-flex justify-content-between">
        <button type="submit" class="btn btn-danger"
                form="deleteSchedule{{ $schedule->id }}"
                onclick="return confirm('Delete this schedule record?')">
          Delete
        </button>

        <div>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" form="updateSchedule{{ $schedule->id }}">Update</button>
        </div>
      </div>

    </div>
  </div>
</div>
