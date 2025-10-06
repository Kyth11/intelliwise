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

          {{-- Multi-grade selector (repeater) --}}
          <div class="mt-3">
            <label class="form-label">For Grade Level(s) (optional)</label>

            @php
              // Restore previously selected values or show one empty row
              $oldGrades = old('gradelvl_ids', [null]);
            @endphp

            <div id="gradeLevelsRepeater" class="d-flex flex-column gap-2">
              @foreach($oldGrades as $idx => $selectedId)
                <div class="row g-2 align-items-end grade-row">
                  <div class="col-9">
                    <select name="gradelvl_ids[]" class="form-select">
                      <option value="">— All Grade Levels —</option>
                      @foreach($gradelvls as $g)
                        <option value="{{ $g->id }}" {{ (string)$selectedId === (string)$g->id ? 'selected' : '' }}>
                          {{ $g->grade_level }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-3 d-flex gap-2">
                    @if($idx === 0)
                      <button type="button" class="btn btn-outline-secondary w-100" id="addGradeRow">
                        <i class="bi bi-plus-lg"></i>
                      </button>
                    @else
                      <button type="button" class="btn btn-outline-danger w-100 removeGradeRow">
                        <i class="bi bi-dash-lg"></i>
                      </button>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>

            {{-- Hidden template for new rows --}}
            <template id="gradeRowTemplate">
              <div class="row g-2 align-items-end grade-row">
                <div class="col-9">
                  <select name="gradelvl_ids[]" class="form-select">
                    <option value="">— All Grade Levels —</option>
                    @foreach($gradelvls as $g)
                      <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-3 d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger w-100 removeGradeRow">
                    <i class="bi bi-dash-lg"></i>
                  </button>
                </div>
              </div>
            </template>

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

{{-- Minimal JS to add/remove grade rows + warn on duplicates --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const repeater = document.getElementById('gradeLevelsRepeater');
  const addBtn   = document.getElementById('addGradeRow');
  const tpl      = document.getElementById('gradeRowTemplate');

  if (addBtn) {
    addBtn.addEventListener('click', function () {
      const node = tpl.content.cloneNode(true);
      repeater.appendChild(node);
    });
  }

  repeater.addEventListener('click', function (e) {
    const btn = e.target.closest('.removeGradeRow');
    if (!btn) return;
    const row = btn.closest('.grade-row');
    if (row) row.remove();
  });

  // Optional: prevent selecting duplicate grade levels in UI
  repeater.addEventListener('change', function (e) {
    if (e.target.tagName !== 'SELECT') return;
    const all = Array.from(repeater.querySelectorAll('select[name="gradelvl_ids[]"]'))
      .map(s => s.value).filter(Boolean);
    const dupes = all.filter((v,i,a) => a.indexOf(v) !== i);
    if (dupes.length) {
      // revert the latest change
      e.target.value = '';
      // small nudge
      e.target.classList.add('is-invalid');
      setTimeout(() => e.target.classList.remove('is-invalid'), 1200);
    }
  });
});
</script>
