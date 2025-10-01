<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('announcements.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $e)
                  <li>{{ $e }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" rows="4" class="form-control" placeholder="Write your announcement...">{{ old('content') }}</textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Date of Event (optional)</label>
              <input type="date" name="date_of_event" class="form-control" value="{{ old('date_of_event') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Deadline (optional)</label>
              <input type="date" name="deadline" class="form-control" value="{{ old('deadline') }}">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">For Grade Level (optional)</label>
            <select name="gradelvl_id" class="form-select">
              <option value="">— All Grade Levels —</option>
              @foreach($gradelvls as $g)
                <option value="{{ $g->id }}" {{ old('gradelvl_id') == $g->id ? 'selected' : '' }}>
                  {{ $g->grade_level }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Post</button>
        </div>
      </form>
    </div>
  </div>
</div>
