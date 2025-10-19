<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('admin.schedules.store') }}" method="POST">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">Add Schedule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="form-label">Day <span class="text-danger">*</span></label>
              @php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']; @endphp
              <select name="day" class="form-select" required>
                @foreach($days as $d)
                  <option value="{{ $d }}" {{ old('day')===$d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-4">
              <label class="form-label">Class Start <span class="text-danger">*</span></label>
              <input type="time" name="class_start" class="form-control" value="{{ old('class_start') }}" required>
            </div>

            <div class="col-sm-4">
              <label class="form-label">Class End <span class="text-danger">*</span></label>
              <input type="time" name="class_end" class="form-control" value="{{ old('class_end') }}" required>
            </div>

            <div class="col-sm-6">
              <label class="form-label">Faculty <span class="text-danger">*</span></label>
              <select name="faculty_id" class="form-select" required>
                @foreach(($faculties ?? collect()) as $f)
                  @php $fname = optional($f->user)->name ?: ('Faculty #'.$f->id); @endphp
                  <option value="{{ $f->id }}" {{ (string)old('faculty_id') === (string)$f->id ? 'selected' : '' }}>
                    {{ $fname }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <select name="subject_id" class="form-select" required>
                @foreach(($subjects ?? collect()) as $s)
                  <option value="{{ $s->id }}" {{ (string)old('subject_id') === (string)$s->id ? 'selected' : '' }}>
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
                  <option value="{{ $gl->id }}" {{ (string)old('gradelvl_id') === (string)$gl->id ? 'selected' : '' }}>
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
                  <option value="{{ $sy->school_year }}" {{ (string)old('school_year') === (string)$sy->school_year ? 'selected' : '' }}>
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

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
