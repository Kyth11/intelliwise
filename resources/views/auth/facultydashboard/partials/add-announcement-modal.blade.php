<!-- Add Announcement Modal (FACULTY, multi-select or ALL) -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('faculty.announcements.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

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

          {{-- Multi-select OR All grade levels --}}
          <div class="mt-3">
            <label for="gradelvlSelect" class="form-label">For Grade Level(s)</label>

            @php
              $oldIds = collect(old('gradelvl_ids', []))->map(fn($v) => (string)$v)->all();
            @endphp

            <select id="gradelvlSelect" name="gradelvl_ids[]" class="form-select" multiple size="6">
              @foreach($gradelvls as $g)
                <option value="{{ $g->id }}" {{ in_array((string)$g->id, $oldIds) ? 'selected' : '' }}>
                  {{ $g->grade_level }}
                </option>
              @endforeach
            </select>

            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="allGradeLevelsCheck">
              <label class="form-check-label" for="allGradeLevelsCheck">
                Apply to <strong>All Grade Levels</strong>
              </label>
            </div>

            <small class="text-muted d-block mt-1">
              Tip: Hold <kbd>Ctrl</kbd> (Windows) or <kbd>⌘</kbd> (Mac) to select multiple. Check “All Grade Levels” to make it global.
            </small>

            @error('gradelvl_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @error('gradelvl_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Post</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- JS: toggle ALL (global) vs multi-select list --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const select = document.getElementById('gradelvlSelect');
  const allChk = document.getElementById('allGradeLevelsCheck');

  function syncAllState() {
    if (!select || !allChk) return;
    if (allChk.checked) {
      // Make it global: clear and disable select so no gradelvl_ids[] get posted
      Array.from(select.options).forEach(opt => opt.selected = false);
      select.setAttribute('disabled', 'disabled');
    } else {
      select.removeAttribute('disabled');
    }
  }

  // Optional UX: if user manually selects every option, auto-check "All"
  function maybeAutoCheckAll() {
    if (!select || !allChk) return;
    const opts = Array.from(select.options);
    const selectedCount = opts.filter(o => o.selected).length;
    if (selectedCount === opts.length && opts.length > 0) {
      allChk.checked = true;
      syncAllState();
    }
  }

  if (allChk && select) {
    allChk.addEventListener('change', syncAllState);
    select.addEventListener('change', function () {
      // If user changes the select, uncheck ALL and re-enable it (unless fully selected)
      if (allChk.checked) { allChk.checked = false; syncAllState(); }
      maybeAutoCheckAll();
    });

    // Ensure correct initial state (if nothing selected, ALL remains unchecked by default)
    syncAllState();
  }
});
</script>
