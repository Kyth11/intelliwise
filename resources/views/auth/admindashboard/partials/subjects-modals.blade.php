{{-- Add Subject Modal --}}
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.subjects.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-2">
                    <label class="form-label">Grade Level</label>
                    <select name="gradelvl_id" class="form-select" required>
                        <option value="">Select grade level</option>
                        @foreach($gradelvls as $g)
                            <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Subject Name</label>
                    <input type="text" name="subject_name" class="form-control" required>
                </div>


            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>


{{-- Edit Subject Modal --}}
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editSubjectForm" method="POST" class="modal-content">
            @csrf
            @method('PUT')

            <div class="modal-header bg-warning">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="mb-2">
                <label class="form-label">Grade Level</label>
                <select id="es_gradelvl" name="gradelvl_id" class="form-select" required>
                    @foreach($gradelvls as $g)
                        <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
                    @endforeach
                </select>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Subject Name</label>
                    <input type="text" id="es_name" name="subject_name" class="form-control" required>
                </div>


            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
</div>
