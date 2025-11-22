@extends('layouts.admin')

@section('title', 'System Settings')

@push('styles')
    <style>
        .theme-chip { cursor: pointer; user-select: none; }

        .form-control.is-valid {
            border-color: #198754 !important;
            box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25) !important;
        }
        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
        }

        .pw-hint { font-size: .85rem; }

        .quick-actions .icon-left {
            position: absolute; left: .5rem; top: 50%;
            transform: translateY(-50%); color: #94a3b8;
        }
        .quick-actions input { padding-left: 1.75rem; }

        .qr-preview { max-height: 340px; }

        .card.h-100 .table-responsive { min-height: 120px; }

        .right-stack { display: grid; gap: .75rem; align-content: start; }
        .card-title-tight { display:flex; align-items:center; justify-content:space-between; }
        .muted-hint { font-size: .8rem; color:#6c757d; }
        .path-note {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .78rem;
            word-break: break-all;
        }
        .rowhide {
            display: none;
        }
   </style>
@endpush

@section('content')

    <div class="row g-3 align-items-stretch">

        <div class="col-12">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Edit Curriculum</h6>
                </div>

                <form action="{{ route('admin.curriculum.store') }}" method="POST">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="id" value="{{ $result->id }}">

                    <div class="mb-2">
                        <label class="form-label">Curriculum Name</label>
                        <input type="text"
                               name="curriculum_name"
                               class="form-control"
                               value="{{ old('curriculum_name', $result->curriculum_name) }}"
                               required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">School Year</label>
                        <select name="schoolyr_id" id="schoolyrSelect" class="form-select" required>
                            <option value="">Select School Year</option>
                            @foreach($schoolyrs as $sy)
                                <option value="{{ $sy->id }}"
                                    {{ (string)$sy->id === (string)$result->schoolyr_id ? 'selected' : '' }}>
                                    {{ $sy->school_year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Grade Level</label>
                        <select name="grade_id" id="gradeSelect" class="form-select" disabled>
                            <option value="">Select Grade Level</option>
                            @foreach($gradelvls as $gl)
                                <option value="{{ $gl->id }}"
                                    {{ (string)$gl->id === (string)$result->grade_id ? 'selected' : '' }}>
                                    {{ $gl->grade_level }}
                                </option>
                            @endforeach
                        </select>
                        {{-- hidden so value is still submitted --}}
                        <input type="hidden" name="grade_id" value="{{ $result->grade_id }}">
                        <small class="text-muted">
                            Grade level is fixed for this curriculum; only subjects belonging to this grade should be included.
                        </small>
                    </div>

                    <div class="row">
                        {{-- Selected subjects --}}
                        <div class="col-sm-6">
                            <h4>Subjects in this Curriculum</h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="mysubjectlist">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>No.#</th>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @if($child && $child->count())
                                        @php $i = 1; @endphp
                                        @foreach ($child as $value)
                                            <tr>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger remove_edit"
                                                            data-id="{{ $value->subject_id }}"
                                                            data-code="{{ $value->subject_code }}"
                                                            data-name="{{ $value->subject_name }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <input type="hidden"
                                                           value="{{ $value->subject_id }}"
                                                           name="itemlist[data][old{{ $value->id }}][subject_id]">
                                                    <input type="hidden"
                                                           value="{{ $value->id }}"
                                                           name="itemlist[data][old{{ $value->id }}][id]">
                                                    <input type="hidden"
                                                           class="deleted"
                                                           value="0"
                                                           name="itemlist[data][old{{ $value->id }}][deleted]">
                                                </td>
                                                <td>{{ $i }}</td>
                                                <td>{{ $value->subject_code }}</td>
                                                <td>{{ $value->subject_name }}</td>
                                            </tr>
                                            @php $i++; @endphp
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Available subjects list --}}
                        <div class="col-sm-6">
                            <h4>List of Subjects</h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered" id="subjectlist">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Subject Code</th>
                                            <th>Subject Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($subjects ?? [] as $s)
                                        <tr data-subject-id="{{ $s->id }}">
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-primary add_subject"
                                                        data-id="{{ $s->id }}"
                                                        data-code="{{ $s->subject_code }}"
                                                        data-name="{{ $s->subject_name }}">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </td>
                                            <td>{{ $s->subject_code }}</td>
                                            <td>{{ $s->subject_name }}</td>
                                        </tr>
                                    @empty
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between">
                        <a href="{{ route('admin.curriculum.index') }}" class="btn btn-light">Back</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save
                        </button>
                    </div>

                    <small class="text-muted d-block mt-2"></small>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var tblAvailable;
        var tblSelected;

        tblAvailable = $('#subjectlist').DataTable();
        tblSelected = $('#mysubjectlist').DataTable({
            paging: false
        });

        $(document).on('click', '.add_subject', function() {
            var id   = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');

            tblSelected.row.add([
                `<button type="button" class="btn btn-sm btn-danger remove_subject"
                    data-id="${id}" data-code="${code}" data-name="${name}">
                    <i class="bi bi-trash"></i>
                </button>
                <input type="hidden" value="${id}" name="itemlist[data][new${id}][subject_id]">`,
                tblSelected.rows().count() + 1,
                code,
                name
            ]).draw(false);

            // Remove from available table
            tblAvailable.row($(this).closest('tr')).remove().draw(false);
        });

        $(document).on('click', '.remove_subject', function() {
            var id   = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');

            tblAvailable.row.add([
                `<button type="button" class="btn btn-sm btn-primary add_subject"
                    data-id="${id}" data-code="${code}" data-name="${name}">
                    <i class="bi bi-plus"></i>
                </button>`,
                code,
                name
            ]).draw(false);

            tblSelected.row($(this).closest('tr')).remove().draw(false);
        });

        $(document).on('click', '.remove_edit', function() {
            var tr = $(this).closest('tr');
            tr.find('.deleted').val(1);
            tr.addClass('rowhide');
        });
    </script>
@endpush
