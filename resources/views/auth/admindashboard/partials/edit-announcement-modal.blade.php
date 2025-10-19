@php
    /** @var \App\Models\Announcement $a */
    // Pre-select many-to-many (pivot) if loaded; fall back to single column if present; else one empty row
    $selectedIdsEdit = old(
        'gradelvl_ids',
        ($a->relationLoaded('gradelvls') ? $a->gradelvls->pluck('id')->all()
         : (isset($a->gradelvl_id) && $a->gradelvl_id ? [$a->gradelvl_id] : [null]))
    );
@endphp

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal{{ $a->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      {{-- UPDATE FORM --}}
      <form id="updateAnnouncement{{ $a->id }}" action="{{ route('admin.announcements.update', $a->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title">Edit Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
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

          {{-- Multi-grade selector (repeater) --}}
          <div class="mt-3">
            <label class="form-label">For Grade Level(s) (optional)</label>

            <div class="d-flex flex-column gap-2 grade-repeater" data-repeater>
              @php $rows = count($selectedIdsEdit) ? $selectedIdsEdit : [null]; @endphp

              @foreach($rows as $idx => $selectedId)
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
      </form>

      {{-- DELETE FORM (separate; no nesting) --}}
      <form id="deleteAnnouncement{{ $a->id }}" action="{{ route('admin.announcements.destroy', $a->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
      </form>

      {{-- Footer buttons reference forms by id --}}
      <div class="modal-footer d-flex justify-content-between">
        <button type="submit" class="btn btn-danger"
                form="deleteAnnouncement{{ $a->id }}"
                onclick="return confirm('Delete this announcement?')">
          Delete
        </button>

        <div>
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-warning" form="updateAnnouncement{{ $a->id }}">Update</button>
        </div>
      </div>

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
