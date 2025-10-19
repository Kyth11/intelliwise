{{-- Add Subject Modal --}}
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('admin.subjects.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header" style="background: linear-gradient(90deg, #198754, #157347); color:#fff;">
        <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Add Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Subject Name</label>
          <input type="text" name="subject_name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Subject Code</label>
          <input type="text" name="subject_code" class="form-control" placeholder="Must be unique" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Grade Level</label>
          <select name="gradelvl_id" class="form-select" required>
            <option value="">Select grade level</option>
            @foreach(($gradelvls ?? []) as $g)
              <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Short description"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save me-1"></i> Save Subject
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Edit Subject Modal --}}
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editSubjectForm" action="#" method="POST" class="modal-content">
      @csrf
      @method('PUT')
      <div class="modal-header" style="background: linear-gradient(90deg, #ffc107, #ffca2c); color:#000;">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Subject</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Subject Name</label>
          <input type="text" id="es_name" name="subject_name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Subject Code</label>
          <input type="text" id="es_code" name="subject_code" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Grade Level</label>
          <select id="es_gradelvl" name="gradelvl_id" class="form-select" required>
            <option value="">Select grade level</option>
            @foreach(($gradelvls ?? []) as $g)
              <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Description (optional)</label>
          <textarea id="es_desc" name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-save me-1"></i> Update Subject
        </button>
      </div>
    </form>
  </div>
</div>
