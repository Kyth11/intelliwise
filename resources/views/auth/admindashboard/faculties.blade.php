{{-- resources/views/auth/admindashboard/faculty.blade.php --}}

@extends('layouts.admin')

@section('title', 'Faculty Management')

@push('styles')
    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- Global Admin Styles --}}
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div class="card section p-4">
        @php
            // Normalize active school year to the *string* value (e.g. "2025-2026")
            if (isset($activeSchoolYear)) {
                if (is_object($activeSchoolYear)) {
                    $activeSy = $activeSchoolYear->school_year ?? null;
                } else {
                    $activeSy = $activeSchoolYear ?: null;
                }
            } else {
                // Fallback: read from DB if not explicitly passed
                $activeSy = \App\Models\Schoolyr::where('active', 1)->value('school_year');
            }

            $facCount = $faculties->count();

            // Distinct grade levels across schedules for the active school year only
            $gradeLevelCount = $faculties
                ->flatMap(function ($f) use ($activeSy) {
                    $scheds = $f->schedules ?? collect();

                    if ($activeSy) {
                        return $scheds->where('school_year', $activeSy);
                    }

                    // If no active SY, show all schedules (or use collect() if you want none)
                    return $scheds;
                })
                ->map(fn($s) => optional($s->gradelvl)->grade_level)
                ->filter()
                ->unique()
                ->count();
        @endphp

        {{-- =========================
             Header: Intro | KPIs | Right: Quick Actions
        ========================== --}}
        <div id="dashboard-header" class="mb-3 d-grid gap-3" style="grid-template-columns: 1fr auto;">
            {{-- Intro + KPIs --}}
            <div>
                <div class="intro mb-3">
                    <h5 class="mb-1">Faculty Schedule Management</h5>
                    <div class="text-muted small">
                        View, edit, and manage faculty schedules.
                        @if($activeSy)
                            Only schedules for the currently active School Year are shown.
                        @else
                            No active School Year detected, all schedules are shown.
                        @endif
                    </div>
                    @if($activeSy)
                        <div class="small mt-1">
                            <span class="badge bg-light text-dark border">
                                Active School Year: {{ $activeSy }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- KPI strip (Faculty + Grade Levels only) --}}
                <div class="kpi-strip d-flex gap-2">
                    <div class="kpi-card border rounded p-3 text-center">
                        <div class="kpi-number fs-4 fw-bold">{{ $facCount }}</div>
                        <div class="kpi-label text-muted small">Faculty</div>
                    </div>
                    <div class="kpi-card border rounded p-3 text-center">
                        <div class="kpi-number fs-4 fw-bold">{{ $gradeLevelCount }}</div>
                        <div class="kpi-label text-muted small">Grade Levels</div>
                    </div>
                </div>
            </div>

            {{-- Right: Quick Actions --}}
            <div class="right-stack" style="width: 320px;">
                <div class="card quick-actions p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="position-relative mb-2">
                        <i class="bi bi-search icon-left"
                           style="position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.6"></i>
                        <input
                            type="text"
                            id="qaFacultySearch"
                            class="form-control form-control-sm ps-5"
                            placeholder="Filter faculty… (press Enter)">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-dark btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#addScheduleModal">
                            <i class="bi bi-calendar-plus me-1"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================
             Main Table
        ========================== --}}
        <div class="card mt-3 p-3">
            <div class="table-responsive">
                <table id="facultySchedTable" class="table table-bordered table-striped align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Name</th>
                            <th>Grade Level (Active SY)</th>
                            <th style="width: 140px;">Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($faculties as $faculty)
                            @php
                                $scheds = $faculty->schedules ?? collect();
                                // Filter this faculty's schedules by active school year if present
                                $facultySchedules = $activeSy
                                    ? $scheds->where('school_year', $activeSy)
                                    : $scheds;

                                $gradeLabels = $facultySchedules
                                    ->map(fn ($s) => optional($s->gradelvl)->grade_level)
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp
                            <tr>
                                <td>{{ $faculty->first_name ?? $faculty->f_firstname }} {{ $faculty->last_name ?? $faculty->f_lastname }}</td>

                                <td>
                                    @if($gradeLabels->isNotEmpty())
                                        {{ $gradeLabels->join(', ') }}
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($facultySchedules->isNotEmpty())
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewFacultySchedule{{ $faculty->id }}">
                                            View Schedule
                                        </button>
                                    @else
                                        <span class="text-muted">
                                            @if($activeSy)
                                                No schedules in active SY
                                            @else
                                                No schedules
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        {{-- DataTables empty message handles empty state --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================
             VIEW SCHEDULE MODALS (per faculty)
        ========================== --}}
        @foreach($faculties as $faculty)
            @php
                $fname = trim(($faculty->first_name ?? $faculty->f_firstname ?? '') . ' ' . ($faculty->last_name ?? $faculty->f_lastname ?? ''));
                if ($fname === '') {
                    $fname = 'Faculty #'.$faculty->id;
                }

                $scheds = $faculty->schedules ?? collect();
                $facultySchedules = $activeSy
                    ? $scheds->where('school_year', $activeSy)
                    : $scheds;

                // Distinct grade levels for this faculty (for header)
                $gradeLabelsForHeader = $facultySchedules
                    ->map(fn ($s) => optional($s->gradelvl)->grade_level)
                    ->filter()
                    ->unique()
                    ->values();

                // Group schedules by subject for row-wise display (subject -> many days/times)
                $groupedBySubject = $facultySchedules->groupBy('subject_id');
            @endphp

            <div class="modal fade" id="viewFacultySchedule{{ $faculty->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Schedule for {{ $fname }}
                                @if($gradeLabelsForHeader->isNotEmpty())
                                    – Grade Level: {{ $gradeLabelsForHeader->join(', ') }}
                                @endif
                                @if($activeSy)
                                    <span class="small text-muted ms-1">(SY {{ $activeSy }})</span>
                                @endif
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            @if($facultySchedules->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th style="width: 35%;">Subject</th>
                                                <th style="width: 65%;">Day / Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groupedBySubject as $subjectId => $subjectSchedules)
                                                @php
                                                    $first = $subjectSchedules->first();
                                                    $subjectName = optional($first->subject)->subject_name ?? '-';
                                                @endphp
                                                <tr>
                                                    <td>{{ $subjectName }}</td>
                                                    <td>
                                                        @foreach($subjectSchedules as $schedule)
                                                            @php
                                                                $st = $schedule->class_start
                                                                    ? date('g:i A', strtotime($schedule->class_start))
                                                                    : '';
                                                                $et = $schedule->class_end
                                                                    ? date('g:i A', strtotime($schedule->class_end))
                                                                    : '';
                                                            @endphp
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <span class="badge bg-primary">
                                                                    {{ $schedule->day ?? '-' }}
                                                                </span>
                                                                <span>
                                                                    @if($st && $et)
                                                                        {{ $st }}–{{ $et }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </span>
                                                                <span class="ms-auto d-flex gap-1">
                                                                    {{-- EDIT: reuse existing edit modal from scheduleModal partial --}}
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-warning"
                                                                        title="Edit Schedule"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editScheduleModal{{ $schedule->id }}"
                                                                        data-parent-modal="#viewFacultySchedule{{ $faculty->id }}">
                                                                        <i class="bi bi-pencil-square"></i>
                                                                    </button>

                                                                    {{-- DELETE: inline form, hooked by .delete-btn script --}}
                                                                    <form
                                                                        action="{{ route('admin.schedules.destroy', $schedule->id) }}"
                                                                        method="POST"
                                                                        class="d-inline delete-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-danger delete-btn"
                                                                            title="Delete Schedule"
                                                                            data-confirm="Delete this schedule?">
                                                                            <i class="bi bi-archive"></i>
                                                                        </button>
                                                                    </form>
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">
                                    No schedules for this faculty
                                    @if($activeSy)
                                        in active School Year ({{ $activeSy }})
                                    @endif
                                    .
                                </p>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- =========================
             Modals (Add + Edit)
        ========================== --}}
        @include('auth.admindashboard.partials.scheduleModal')
    </div> {{-- /card section --}}
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @php
        // Precompute subject data for JS (grade-filtered) – still used by Add Schedule modal
        $scheduleSubjectsData = ($subjects ?? collect())->map(function ($s) {
            return [
                'id'       => $s->id,
                'name'     => $s->subject_name,
                'grade_id' => $s->gradelvl_id,
            ];
        })->values()->all();
    @endphp

    <script>
        // ==========================
        // PRELOAD SUBJECT DATA (BY GRADE)
        // ==========================
        (function () {
            window.scheduleSubjectsData = @json($scheduleSubjectsData);
        })();

        // ==========================
        // GLOBAL SweetAlert DELETE + BACKDROP CLEANUP
        // ==========================
        (function () {
            function confirmDelete(form, msg, btn) {
                if (!window.Swal) {
                    form.submit();
                    return;
                }

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

            function bindDeleteButtons() {
                document.querySelectorAll('.delete-btn').forEach((button) => {
                    if (button.dataset.bound === '1') return;
                    button.dataset.bound = '1';

                    button.addEventListener('click', function () {
                        const form = this.closest('form.delete-form');
                        if (!form) return;
                        const msg = this.dataset.confirm || "You can't undo this action.";
                        confirmDelete(form, msg, this);
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', bindDeleteButtons);
            document.addEventListener('shown.bs.modal', bindDeleteButtons);

            document.addEventListener('hidden.bs.modal', function () {
                const anyOpen = document.querySelector('.modal.show');
                if (!anyOpen) {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
                }
            });
        })();

        // ==========================
        // DataTables init
        // ==========================
        $(function () {
            const table = $('#facultySchedTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                columnDefs: [
                    { targets: -1, orderable: false } // Schedule column unsortable
                ],
                language: {
                    emptyTable: 'No faculty records found.'
                }
            });

            const qa = document.getElementById('qaFacultySearch');
            qa?.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                table.search(qa.value || '').draw();
            });
        });

        // ==========================
        // ADD SCHEDULE: SUBJECT ROWS BY GRADE
        // ==========================
        (function () {
            const tbodyId = 'scheduleSubjectsBody';
            let entryIndex = 0;
            let rowIndex   = 0;

            function getSubjectsForGrade(gradeId) {
                const data = window.scheduleSubjectsData || [];
                return data.filter(s => String(s.grade_id) === String(gradeId));
            }

            function buildDayButtonsAndContainerHtml() {
                const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                const buttons = days.map(d => `
                    <button type="button"
                            class="btn btn-outline-primary btn-sm day-toggle-btn"
                            data-mode="add"
                            data-day="${d}">
                        ${d}
                    </button>
                `).join('');

                return `
                    <div class="day-button-group mb-2" role="group">
                        <div class="btn-group w-100 flex-wrap" role="group">
                            ${buttons}
                        </div>
                    </div>
                    <div class="day-time-container"></div>
                `;
            }

            function addScheduleRow(subjectId, subjectName) {
                const tbody = document.getElementById(tbodyId);
                if (!tbody) return;

                const tr = document.createElement('tr');
                tr.setAttribute('data-subject-id', subjectId);
                tr.dataset.rowIndex = rowIndex++;

                tr.innerHTML = `
                    <td>${subjectName}</td>
                    <td>
                        <div class="row g-2">
                            <div class="col-12">
                                ${buildDayButtonsAndContainerHtml()}
                            </div>
                        </div>
                    </td>
                `;

                tbody.appendChild(tr);
            }

            function clearScheduleRows() {
                const tbody = document.getElementById(tbodyId);
                if (tbody) tbody.innerHTML = '';
                entryIndex = 0;
                rowIndex   = 0;
            }

            function addDayTimeEntry(container, subjectId, day) {
                if (container.querySelector(`.day-time-row[data-day="${day}"]`)) {
                    return;
                }

                const idx = entryIndex++;
                const row = document.createElement('div');
                row.className = 'day-time-row mb-2';
                row.dataset.day = day;
                row.dataset.subjectId = subjectId;

                row.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary">${day}</span>
                        <div class="flex-fill">
                            <input type="time"
                                   name="entries[${idx}][class_start]"
                                   class="form-control"
                                   required>
                        </div>
                        <div class="flex-fill">
                            <input type="time"
                                   name="entries[${idx}][class_end]"
                                   class="form-control"
                                   required>
                        </div>
                        <input type="hidden"
                               name="entries[${idx}][subject_id]"
                               value="${subjectId}">
                        <input type="hidden"
                               name="entries[${idx}][day]"
                               value="${day}">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger js-remove-day">
                            &times;
                        </button>
                    </div>
                `;

                container.appendChild(row);
            }

            $(document).on('change', '.js-sched-grade-select', function () {
                const gradeId = $(this).val();
                clearScheduleRows();
                if (!gradeId) return;

                const subjects = getSubjectsForGrade(gradeId);
                subjects.forEach(s => addScheduleRow(s.id, s.name));
            });

            $(document).on('click', '.js-remove-day', function () {
                const row = this.closest('.day-time-row');
                if (!row) return;
                const day = row.dataset.day;
                const tr  = row.closest('tr');

                row.remove();

                if (tr) {
                    const group = tr.querySelector('.day-button-group');
                    if (group) {
                        group.querySelectorAll('.day-toggle-btn[data-mode="add"]').forEach(b => {
                            if (b.dataset.day === day) {
                                b.classList.remove('active');
                            }
                        });
                    }
                }
            });

            $(document).on('click', '.day-toggle-btn[data-mode="add"]', function () {
                const btn = $(this);
                const tr  = btn.closest('tr');
                if (!tr.length) return;

                const subjectId = tr.data('subject-id');
                if (!subjectId) return;

                const container = tr.find('.day-time-container').get(0);
                if (!container) return;

                if (container.querySelector(`.day-time-row[data-day="${btn.data('day')}"]`)) {
                    return;
                }

                addDayTimeEntry(container, subjectId, btn.data('day'));
                btn.addClass('active');
            });

            $('#addScheduleModal').on('show.bs.modal', function () {
                clearScheduleRows();
                const select = document.querySelector('.js-sched-grade-select');
                if (select && select.value) {
                    const subjects = getSubjectsForGrade(select.value);
                    subjects.forEach(s => addScheduleRow(s.id, s.name));
                }
            });
        })();

        // ==========================
        // DAY TOGGLE BUTTON HANDLER (EDIT MODE)
        // ==========================
        (function () {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.day-toggle-btn[data-mode="edit"]');
                if (!btn) return;

                const group    = btn.closest('.btn-group');
                const targetId = btn.dataset.targetInput;
                const hidden   = document.getElementById(targetId);

                if (!group || !hidden) return;

                group.querySelectorAll('.day-toggle-btn[data-mode="edit"]').forEach(b => b.classList.remove('active'));

                btn.classList.add('active');
                hidden.value = btn.dataset.day;
            });
        })();

        // ==========================
        // EDIT SCHEDULE: RETURN TO VIEW MODAL
        // ==========================
        (function () {
            // When an edit modal is shown, remember which view modal it came from
            document.addEventListener('show.bs.modal', function (event) {
                const modal = event.target;
                if (!modal.id || !modal.id.startsWith('editScheduleModal')) return;

                const trigger = event.relatedTarget;
                if (!trigger) return;

                const parentSelector = trigger.getAttribute('data-parent-modal');
                if (!parentSelector) return;

                modal.dataset.parentModalSelector = parentSelector;

                const parentModalEl = document.querySelector(parentSelector);
                if (parentModalEl && window.bootstrap && window.bootstrap.Modal) {
                    const parentInstance =
                        bootstrap.Modal.getInstance(parentModalEl) ||
                        new bootstrap.Modal(parentModalEl);
                    parentInstance.hide();
                }
            });

            // When edit modal is hidden (X, Cancel, backdrop), re-open its parent view modal
            document.addEventListener('hidden.bs.modal', function (event) {
                const modal = event.target;
                if (!modal.id || !modal.id.startsWith('editScheduleModal')) return;

                const parentSelector = modal.dataset.parentModalSelector;
                if (!parentSelector) return;

                const parentModalEl = document.querySelector(parentSelector);
                if (!parentModalEl || !window.bootstrap || !window.bootstrap.Modal) return;

                const parentInstance =
                    bootstrap.Modal.getInstance(parentModalEl) ||
                    new bootstrap.Modal(parentModalEl);
                parentInstance.show();
            });

            // Before submitting an edit form, mark which view modal must be re-opened after reload
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!form.id || !form.id.startsWith('updateSchedule')) return;

                const modal = form.closest('.modal');
                if (!modal) return;

                const parentSelector = modal.dataset.parentModalSelector;
                if (!parentSelector) return;

                try {
                    localStorage.setItem('facultyViewToOpenOnLoad', parentSelector);
                } catch (e) {
                    // Storage errors ignored
                }
            });

            // On page load (after a redirect), reopen any pending view modal
            document.addEventListener('DOMContentLoaded', function () {
                let selector = null;
                try {
                    selector = localStorage.getItem('facultyViewToOpenOnLoad');
                } catch (e) {
                    selector = null;
                }
                if (!selector) return;

                const el = document.querySelector(selector);
                if (!el || !window.bootstrap || !window.bootstrap.Modal) {
                    try {
                        localStorage.removeItem('facultyViewToOpenOnLoad');
                    } catch (e) {}
                    return;
                }

                const instance =
                    bootstrap.Modal.getInstance(el) ||
                    new bootstrap.Modal(el);
                instance.show();

                try {
                    localStorage.removeItem('facultyViewToOpenOnLoad');
                } catch (e) {}
            });
        })();
    </script>
@endpush
