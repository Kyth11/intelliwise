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

                {{-- Multiple subject names for the selected grade --}}
                <div id="addSubjectsWrapper">
                    <div class="row g-2 align-items-end subject-row">
                        <div class="col-9">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="subject_name[]" class="form-control" required>
                        </div>
                        <div class="col-3 text-end">
                            <button type="button"
                                    class="btn btn-outline-secondary w-100 js-add-subject-row"
                                    title="Add another subject">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- template for extra rows (Add modal) --}}
                <template id="addSubjectRowTemplate">
                    <div class="row g-2 align-items-end subject-row mt-2">
                        <div class="col-9">
                            <input type="text" name="subject_name[]" class="form-control" placeholder="Subject Name" required>
                        </div>
                        <div class="col-3 text-end">
                            <button type="button"
                                    class="btn btn-outline-danger w-100 js-remove-subject-row"
                                    title="Remove this subject">
                                <i class="bi bi-dash"></i>
                            </button>
                        </div>
                    </div>
                </template>
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

            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Grade Level</label>
                    <select id="es_gradelvl" name="gradelvl_id" class="form-select" required>
                        @foreach($gradelvls as $g)
                            <option value="{{ $g->id }}">{{ $g->grade_level }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Multiple subject names for this grade in edit context --}}
                <div id="editSubjectsWrapper">
                    <div class="row g-2 align-items-end subject-row">
                        <div class="col-9">
                            <label class="form-label">Subject Name</label>
                            {{-- first input keeps the original id used by your existing JS --}}
                            <input type="text" id="es_name" name="subject_name[]" class="form-control" required>
                        </div>
                        <div class="col-3 text-end">
                            <button type="button"
                                    class="btn btn-outline-secondary w-100 js-add-edit-subject-row"
                                    title="Add another subject">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- template for extra rows (Edit modal) --}}
                <template id="editSubjectRowTemplate">
                    <div class="row g-2 align-items-end subject-row mt-2">
                        <div class="col-9">
                            <input type="text" name="subject_name[]" class="form-control" placeholder="Subject Name" required>
                        </div>
                        <div class="col-3 text-end">
                            <button type="button"
                                    class="btn btn-outline-danger w-100 js-remove-subject-row"
                                    title="Remove this subject">
                                <i class="bi bi-dash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        // Add-subject modal: add extra subject rows
        document.addEventListener('click', function (e) {
            const addBtn = e.target.closest('.js-add-subject-row');
            if (addBtn) {
                const wrapper = document.getElementById('addSubjectsWrapper');
                const tmpl = document.getElementById('addSubjectRowTemplate');
                if (!wrapper || !tmpl) return;

                const clone = tmpl.content.cloneNode(true);
                wrapper.appendChild(clone);
            }

            const addEditBtn = e.target.closest('.js-add-edit-subject-row');
            if (addEditBtn) {
                const wrapper = document.getElementById('editSubjectsWrapper');
                const tmpl = document.getElementById('editSubjectRowTemplate');
                if (!wrapper || !tmpl) return;

                const clone = tmpl.content.cloneNode(true);
                wrapper.appendChild(clone);
            }

            const removeBtn = e.target.closest('.js-remove-subject-row');
            if (removeBtn) {
                const row = removeBtn.closest('.subject-row');
                if (!row) return;

                const wrapper = row.parentElement;
                if (!wrapper) return;

                // keep at least one row
                const rows = wrapper.querySelectorAll('.subject-row');
                if (rows.length > 1) {
                    row.remove();
                }
            }
        });
    })();
</script>
@endpush
