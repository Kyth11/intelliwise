<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal{{ $a->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('faculty.announcements.update', $a->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title">Edit Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $a->title) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" rows="4" class="form-control">{{ old('content', $a->content) }}</textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Date of Event (optional)</label>
              <input type="date" name="date_of_event" class="form-control"
                     value="{{ old('date_of_event', optional($a->date_of_event)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Deadline (optional)</label>
              <input type="date" name="deadline" class="form-control"
                     value="{{ old('deadline', optional($a->deadline)->format('Y-m-d')) }}">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">For Grade Level(s) (optional)</label>
            @php $selectedIds = old('gradelvl_ids', $a->gradelvls->pluck('id')->all()); @endphp
            <select name="gradelvl_ids[]" class="form-select" multiple>
              @foreach($gradelvls as $g)
                <option value="{{ $g->id }}" {{ in_array($g->id, $selectedIds) ? 'selected' : '' }}>
                  {{ $g->grade_level }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Leave none selected to make it visible to all grade levels (global).</small>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-warning" type="submit">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
