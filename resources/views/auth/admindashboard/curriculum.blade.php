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

        /* ensure tables/cards line up nicely on taller content */
        .card.h-100 .table-responsive { min-height: 120px; }

        /* right column stacked cards */
        .right-stack { display: grid; gap: .75rem; align-content: start; }
        .card-title-tight { display:flex; align-items:center; justify-content:space-between; }
        .muted-hint { font-size: .8rem; color:#6c757d; }
        .path-note { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: .78rem; word-break: break-all; }
    </style>
@endpush

@section('content')
   

        <!-- =========================
             Main content (Admin Accounts + Subjects) — side-by-side, equal height
        ========================== -->
        <div class="row g-3 align-items-stretch">

            {{-- Subjects (right) --}}
            <div class="col-12 col-lg-12">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Manage Curriculum</h6>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCurriculum">
                            <i class="bi bi-journal-plus me-1"></i> Add New Curriculum
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>School Year</th>
                                    <th>Grade Level</th>
                                    <th>Adviser Name</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp

                                @forelse ($result as $row)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $row->school_year }}</td>
                                    <td>{{ $row->grade_level }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"  >
                                            <a href="{{ route('admin.curriculum.curriculum_edit', $row->id) }}"><i class="bi bi-pencil-square"></i></a>
                                        </button>
                                       

                                        <form action="{{ route('admin.subjects.destroy', $row->id) }}" method="POST" class="d-inline js-confirm-delete"
                                            data-confirm="Delete subject '{{ $row->school_year }}' ({{ $row->grade_level }})?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                
                                @endforelse


                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Codes must be unique. A subject belongs to a specific grade
                        level.</small>
                </div>
            </div>
        </div>
    </div>

   

    {{-- Edit Subject Modal --}}
    <div class="modal fade" id="modalCurriculum" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form  action="{{ route('admin.curriculum.store') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Manage Curriculum</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">School Year</label>
                            <select name="schoolyr_id" id="schoolyrSelect" class="form-select">
                                <option value="">Select School Year</option>
                                @foreach($schoolyrs as $sy)
                                    <option value="{{ $sy->id }}">
                                        {{ $sy->school_year}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Grade Level</label>
                            <select name="grade_id" id="schoolyrSelect" class="form-select">
                                <option value="">Select Grade Level</option>
                                @foreach($gradelvls as $gl)
                                    <option value="{{ $gl->id }}">
                                        {{ $gl->grade_level }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Faculty Name</label>
                            <select name="adviser_id" class="form-select" required>
                                <option value="">Select Faculty</option>
                            @foreach(($faculties ?? collect()) as $f)
                            @php $fname = optional($f->user)->name ?: ('Faculty #'.$f->id); @endphp
                            <option value="{{ $f->id }}" {{ (string)old('faculty_id') === (string)$f->id ? 'selected' : '' }}>
                                {{ $fname }}
                            </option>
                            @endforeach
                        </select>
                        </div>


                        <div class="row">
    

                        <div class="col-sm-6">
                            <h4>Subjects</h4>
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
                                    <?php if($child): ?>
                                        <?php $i=1;?>
                                        <?php foreach ($child as $value): ?>
                                            <tr>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove_edit" 
                                                        data-id="<?= $value['subject_id'] ?>" 
                                                        data-code="<?= $value['code'] ?>" 
                                                        data-name="<?= $value['name'] ?>">
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                    <input type="hidden" value="<?= $value['subject_id'] ?>" name="itemlist[data][old<?=$value["id"]?>][subject_id]" >
                                                    <input type="hidden" value="<?= $value['id'] ?>" name="itemlist[data][old<?=$value["id"]?>][id]" >
                                                    <input type="hidden" class="deleted" value="0" name="itemlist[data][old<?=$value["id"]?>][deleted]" >


                                                </td>
                                                <td><?=$i?></td>
                                                <td><?= $value['code'] ?></td>
                                                <td><?= $value['name'] ?></td>
                                            </tr>
                                            <?php $i++;?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    </tbody>
                                </table>
                                
                            </div>   

                        </div>
                        

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

                                        <tr>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary add_subject" 
                                                    data-id="{{$s->id}}" 
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
                      
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var tblAvailable;
        var tblSelected

        
        tblAvailable = $('#subjectlist').DataTable();
        tblSelected = $('#mysubjectlist').DataTable({
            paging: false // disables pagination completely
        });


                
        $(document).on('click', '.add_subject', function() {

            var id = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');

            // Append to selected table
            tblSelected.row.add([
                `<button type="button" class="btn btn-sm btn-danger remove_subject" 
                    data-id="${id}" data-code="${code}" data-name="${name}">
                    <i class="bi bi-trash"></i>
                </button>
                <input type="hidden" value="${id}" name="itemlist[data][new${id}][subject_id]">
                `,
                tblSelected.rows().count() + 1,
                code,
                name
            ]).draw(false);

            // Remove from available table
            tblAvailable.row($(this).closest('tr')).remove().draw(false);
        


        });



        $(document).on('click', '.remove_subject', function() {

        var id = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');

            // Append back to available table
            tblAvailable.row.add([
                `<button type="button" class="btn btn-sm btn-primary add_subject" 
                    data-id="${id}" data-code="${code}" data-name="${name}">
                    <i class="bi bi-plus"></i>
                </button>`,
                code,
                name
            ]).draw(false);

            // Remove from selected table
            tblSelected.row($(this).closest('tr')).remove().draw(false);


        });




        $(document).on('click', '.remove_edit', function() {

            var tr = $(this).parent().parent();
            tr.find('.deleted').val(1);
            tr.addClass('rowhide');
        });


    </script>
@endpush
