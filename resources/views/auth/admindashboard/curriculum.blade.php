{{-- resources/views/auth/admindashboard/curriculum.blade.php --}}

@extends('layouts.admin')

@section('title', 'Curriculum Management')

@push('styles')
    <style>
        .card.h-100 .table-responsive {
            min-height: 120px;
        }

        .grade-subjects-panel.d-none {
            display: none !important;
        }

        .rowhide {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="card section p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Curriculum Management</h5>
            </div>
            <div>
                @if($currentSchoolYear)
                    <span class="badge bg-light text-dark border">
                        Active School Year: {{ $currentSchoolYear->school_year }}
                    </span>
                @else
                    <span class="badge bg-danger">No active school year configured</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-stretch">

        {{-- LEFT: Curriculum list --}}
        <div class="col-12 col-lg-6">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Curricula</h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCurriculum">
                        <i class="bi bi-journal-plus me-1"></i> Add New Curriculum
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Curriculum</th>
                                <th>School Year</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @forelse ($result as $row)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $row->curriculum_name }}</td>
                                    <td>{{ $row->school_year }}</td>
                                    <td>
                                        <form action="{{ route('admin.curriculum.updateStatus', $row->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status"
                                                    class="form-select form-select-sm js-curriculum-status-select">
                                                <option value="1" {{ (int)$row->status === 1 ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option value="0" {{ (int)$row->status === 0 ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.curriculum.curriculum_edit', $row->id) }}"
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="{{ route('admin.curriculum.destroy', $row->id) }}" method="POST"
                                              class="d-inline js-confirm-delete"
                                              data-confirm="Delete curriculum '{{ $row->curriculum_name }}' ({{ $row->school_year }} - {{ $row->grade_level }})?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No curriculum records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <small class="text-muted d-block mt-2">
                    The active school year is controlled under System Settings (School Year card). Proceeding to the next
                    school year or reverting will only change which year is marked active.
                </small>
            </div>
        </div>

        {{-- RIGHT: Subjects by Grade Level (moved from Settings) --}}
        <div class="col-12 col-lg-6">
            <div class="card p-3 h-100">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Subjects by Grade Level</h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-journal-plus me-1"></i> Add Subject
                    </button>
                </div>

                @php
                    // ensure display order follows seeding / IDs
                    $gradeLevels = $gradelvls->sortBy('id');
                @endphp

                {{-- Top nav bar of grade levels --}}
                <div class="mb-3">
                    <ul class="nav nav-pills flex-wrap" id="gradeLevelNav">
                        @foreach($gradeLevels as $g)
                            <li class="nav-item mb-1 me-1">
                                <button type="button"
                                        class="nav-link btn-sm js-grade-tab"
                                        data-grade-id="{{ $g->id }}">
                                    {{ $g->grade_level }}
                                    <span class="badge bg-light text-dark border ms-1">
                                        {{ $g->subjects->count() }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <small class="text-muted d-block mt-1">
                        Click a grade level to show or hide its subjects.
                    </small>
                </div>

                <div class="table-responsive mt-2">
                    @foreach($gradeLevels as $g)
                        <div class="grade-subjects-panel mb-2 d-none" data-grade-id="{{ $g->id }}">
                            <div class="card border">
                                <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">{{ $g->grade_level }}</span>
                                    <span class="badge bg-secondary">
                                        {{ $g->subjects->count() }} subject{{ $g->subjects->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>
                                <div class="card-body p-2">
                                    @if($g->subjects->isEmpty())
                                        <div class="text-muted small">No subjects for this grade level.</div>
                                    @else
                                        <table class="table table-sm mb-0 align-middle">
                                            <tbody>
                                                @foreach($g->subjects as $s)
                                                    <tr>
                                                        <td>{{ $s->subject_name }}</td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-warning js-edit-subject"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editSubjectModal"
                                                                    data-id="{{ $s->id }}"
                                                                    data-name="{{ $s->subject_name }}"
                                                                    data-grade="{{ $s->gradelvl_id }}">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>

                                                            <form action="{{ route('admin.subjects.destroy', $s->id) }}"
                                                                  method="POST"
                                                                  class="d-inline js-confirm-delete"
                                                                  data-confirm="Delete subject '{{ $s->subject_name }}'?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-danger js-delete-btn">
                                                                    <i class="bi bi-archive"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>

    {{-- =========================
         Manage Curriculum Modal
    ========================== --}}
    <div class="modal fade" id="modalCurriculum" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form action="{{ route('admin.curriculum.store') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Manage Curriculum</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Curriculum Name</label>
                            <input type="text" name="curriculum_name" class="form-control"
                                   placeholder="e.g., K–12 Standard Grade 7" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label d-block">School Year(s)</label>

                            <div class="d-flex flex-wrap gap-2">
                                @foreach($schoolyrs as $sy)
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="schoolyr_ids[]"
                                            id="sy_{{ $sy->id }}"
                                            value="{{ $sy->id }}"
                                            @if($currentSchoolYear && $currentSchoolYear->id === $sy->id) checked @endif
                                        >
                                        <label class="form-check-label" for="sy_{{ $sy->id }}">
                                            {{ $sy->school_year }}@if($sy->active) (Active) @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <small class="text-muted d-block mt-1">
                                The list of school years comes from the <code>schoolyrs</code> table.
                                Check one or more school years for which this curriculum applies.
                                The active school year is controlled from System Settings (School Year card).
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h6>Curriculum Subjects (all grade levels)</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 10%;">#</th>
                                                <th style="width: 30%;">Grade Level</th>
                                                <th style="width: 60%;">Subject Name</th>
                                            </tr>
                                        </thead>
                                        <tbody id="curriculumSubjectsBody">
                                            @php $rowNum = 1; @endphp
                                            @forelse($gradeLevels as $g)
                                                @foreach($g->subjects as $s)
                                                    <tr>
                                                        <td>{{ $rowNum++ }}</td>
                                                        <td>{{ $g->grade_level }}</td>
                                                        <td>
                                                            {{ $s->subject_name }}
                                                            <input type="hidden" name="subjects[]" value="{{ $s->id }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @empty
                                            @endforelse

                                            @if($rowNum === 1)
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">
                                                        No subjects defined yet. Please add subjects in "Subjects by Grade Level".
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    All subjects from all grade levels are automatically included in this curriculum.
                                    When the school year is moved forward in System Settings, curricula that are still used can
                                    reuse this subject set for the new year. Otherwise, a new curriculum can be defined.
                                </small>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('auth.admindashboard.partials.subjects-modals')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Generic delete confirm (curriculum and subjects)
        (function () {
            function confirmDelete(form, msg, btn) {
                if (!window.Swal) { form.submit(); return; }
                Swal.fire({
                    title: 'Are you sure?',
                    text: msg || "You can't undo this action.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, proceed',
                    reverseButtons: true,
                    background: '#fff',
                    backdrop: false,
                    allowOutsideClick: true,
                    allowEscapeKey: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        if (btn) btn.disabled = true;
                        form.submit();
                    }
                });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-delete-btn');
                if (!btn) return;
                const form = btn.closest('form.js-confirm-delete');
                if (!form) return;
                e.preventDefault();
                confirmDelete(form, form.dataset.confirm, btn);
            });

            document.addEventListener('submit', function (e) {
                const form = e.target.closest('form.js-confirm-delete');
                if (!form) return;
                e.preventDefault();
                const btn = form.querySelector('.js-delete-btn') || form.querySelector('[type="submit"]');
                confirmDelete(form, form.dataset.confirm, btn);
            }, true);
        })();

        // Subject edit modal (from Subjects by Grade Level card)
        (function () {
            document.querySelectorAll('.js-edit-subject').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;

                    const form = document.getElementById('editSubjectForm');
                    if (form) {
                        form.action = `/admin/subjects/${id}`;
                    }

                    const name  = document.getElementById('es_name');
                    const grade = document.getElementById('es_gradelvl');

                    if (name)  name.value  = btn.dataset.name || '';
                    if (grade) grade.value = btn.dataset.grade || '';
                });
            });
        })();

        // Grade level dropdown panels
        (function () {
            const tabs = document.querySelectorAll('.js-grade-tab');
            const panels = document.querySelectorAll('.grade-subjects-panel');
            if (!tabs.length || !panels.length) return;

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.gradeId;
                    if (!id) return;

                    const panel = Array.from(panels).find(p => p.dataset.gradeId === id);
                    if (!panel) return;

                    const isHidden = panel.classList.contains('d-none');

                    if (isHidden) {
                        panel.classList.remove('d-none');
                        btn.classList.add('active');
                    } else {
                        panel.classList.add('d-none');
                        btn.classList.remove('active');
                    }
                });
            });
        })();

        // Auto-submit status selector
        (function () {
            document.querySelectorAll('.js-curriculum-status-select').forEach(sel => {
                sel.addEventListener('change', function () {
                    const form = this.closest('form');
                    if (form) form.submit();
                });
            });
        })();
    </script>
@endpush
