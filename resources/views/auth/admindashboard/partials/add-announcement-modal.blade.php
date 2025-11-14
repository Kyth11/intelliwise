<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('admin.announcements.store') }}" method="POST">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">Add Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
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
              $oldGradesAdd = old('gradelvl_ids', [null]); // default one empty row
            @endphp

            <div class="d-flex flex-column gap-2 grade-repeater" data-repeater>
              @foreach($oldGradesAdd as $idx => $selectedId)
                <div class="row g-2 align-items-end grade-row">
                  <div class="col-9">
                    <select name="gradelvl_ids[]" class="form-select">
                      <option value="">— All Grade Levels —</option>
                      @foreach(($gradelvls ?? collect()) as $g)
                        <option value="{{ $g->id }}" {{ (string)$selectedId === (string)$g->id ? 'selected' : '' }}>
                          {{ $g->grade_level }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-3 d-flex gap-2">
                    @if($idx === 0)
                      <button type="button" class="btn btn-outline-secondary w-100 js-add-grade">
                        <i class="bi bi-plus-lg"></i>
                      </button>
                    @else
                      <button type="button" class="btn btn-outline-danger w-100 js-remove-grade">
                        <i class="bi bi-dash-lg"></i>
                      </button>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>

            <template data-template>
              <div class="row g-2 align-items-end grade-row">
                <div class="col-9">
                  <select name="gradelvl_ids[]" class="form-select">
                    <option value="">— All Grade Levels —</option>
                    @foreach(($gradelvls ?? collect()) as $g)
                      <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-3 d-flex gap-2">
                  <button type="button" class="btn btn-outline-danger w-100 js-remove-grade">
                    <i class="bi bi-dash-lg"></i>
                  </button>
                </div>
              </div>
            </template>

            @error('gradelvl_ids')   <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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

{{-- Minimal JS for repeater (scoped per modal) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-repeater]').forEach(function (rep) {
    const modalContent = rep.closest('.modal-content') || document;
    const tpl = modalContent.querySelector('template[data-template]');

    rep.addEventListener('click', function (e) {
      if (e.target.closest('.js-add-grade')) {
        if (!tpl) return;
        rep.appendChild(tpl.content.cloneNode(true));
      }
      if (e.target.closest('.js-remove-grade')) {
        const row = e.target.closest('.grade-row');
        if (row && rep.querySelectorAll('.grade-row').length > 1) row.remove();
      }
    });

    // prevent duplicates per repeater
    rep.addEventListener('change', function (e) {
      if (!(e.target instanceof HTMLSelectElement)) return;
      if (e.target.name !== 'gradelvl_ids[]') return;

      const values = Array.from(rep.querySelectorAll('select[name="gradelvl_ids[]"]'))
        .map(s => s.value).filter(Boolean);

      const v = e.target.value;
      if (v && values.filter(x => x === v).length > 1) {
        e.target.value = '';
        e.target.classList.add('is-invalid');
        setTimeout(() => e.target.classList.remove('is-invalid'), 1200);
      }
    });
  });
});
</script>
