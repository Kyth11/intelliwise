@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- Global Admin Styles (merged) --}}
    <link rel="stylesheet" href="{{ asset('css/css.css') }}">
@endpush

@section('content')
    <div class="card section p-4">
        <!-- =========================
             Header: Intro | KPIs | Right: Quick Actions
        ========================== -->
        <div id="dashboard-header" class="mb-3">
            <!-- Intro -->
            <div class="intro">
                <div>
                    <h5 class="mb-1">Welcome, {{ Auth::check() ? Auth::user()->name : 'Admin' }}!</h5>
                    <div class="text-muted small">Here’s a quick system snapshot and your tools.</div>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $students->count() }}</div>
                    <div class="kpi-label">Total Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $faculties->count() }}</div>
                    <div class="kpi-label">Teachers</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $guardians->count() + $faculties->count() }}</div>
                    <div class="kpi-label">System Users</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $announcements->count() }}</div>
                    <div class="kpi-label">Announcements</div>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="right-stack">
                <div class="card quick-actions p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Quick Actions</h6>
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-secondary" title="Settings">
                            <i class="bi bi-gear"></i>
                        </a>
                    </div>
                    <div class="position-relative">
                        <i class="bi bi-search icon-left"></i>
                        <input type="text" id="quickSearch" class="form-control form-control-sm"
                            placeholder="Type e.g. “pay balance”, “settings”, “add subject”, “students”… then Enter">
                    </div>
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.finances') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-cash-coin me-1"></i> Finances
                        </a>
                        <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-person-plus me-1"></i> Enroll Student
                        </a>
                        <a href="{{ route('admin.settings.index') }}?tab=subjects" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-journal-plus me-1"></i> Add Subject
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================
             Below Header: Left (charts + Announcements + Schedule) | Right (Recent Payments)
        ========================== -->
        @php
            $tuitionMap = collect($tuitions ?? collect())->keyBy('grade_level');

            $gradeCounts = collect();
            $paidTotal = 0;
            $balTotal = 0;

            $recentPaymentsView = collect();

            foreach ($students as $s) {
                $gradeKey = $s->s_gradelvl ?? optional(optional($s)->gradelvl)->grade_level ?? '— No Grade —';
                $gradeCounts[$gradeKey] = ($gradeCounts[$gradeKey] ?? 0) + 1;

                $row = $tuitionMap->get($s->s_gradelvl);
                $base = $row ? (float) $row->total_yearly
                    : ((isset($s->s_tuition_sum) && $s->s_tuition_sum !== '') ? (float) $s->s_tuition_sum : 0);

                $optCollection = collect($s->optionalFees ?? []);
                $filtered = $optCollection->filter(function ($f) {
                    $scopeOk = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
                    $activeOk = !property_exists($f, 'active') || (bool) $f->active;
                    return $scopeOk && $activeOk;
                });

                $opt = (float) $filtered->sum(function ($f) {
                    $amt = $f->pivot->amount_override ?? $f->amount;
                    return (float) $amt;
                });

                $originalTotal = $base + $opt;
                $currentBalance = (float) ($s->s_total_due ?? $originalTotal);
                $paid = max($originalTotal - $currentBalance, 0);

                $paidTotal += $paid;
                $balTotal += $currentBalance;

                $g = $s->guardian ?? null;
                $mFirst = trim(collect([data_get($g, 'm_firstname'), data_get($g, 'm_middlename')])->filter()->implode(' '));
                $mLast = (string) data_get($g, 'm_lastname', '');
                $fFirst = trim(collect([data_get($g, 'f_firstname'), data_get($g, 'f_middlename')])->filter()->implode(' '));
                $fLast = (string) data_get($g, 'f_lastname', '');
                $motherFull = trim(($mFirst ? $mFirst . ' ' : '') . $mLast);
                $fatherFull = trim(($fFirst ? $fFirst . ' ' : '') . $fLast);

                $parents = '—';
                if ($motherFull || $fatherFull) {
                    if ($motherFull && $fatherFull) {
                        if ($mLast && $fLast && strcasecmp($mLast, $fLast) === 0) {
                            $firstToUse = $fFirst ?: $mFirst;
                            $lastToUse = $fLast ?: $mLast;
                            $parents = 'Mr. & Mrs. ' . trim(($firstToUse ? $firstToUse . ' ' : '') . $lastToUse);
                        } else {
                            $parents = $motherFull . ' & ' . $fatherFull;
                        }
                    } else {
                        $parents = $fatherFull ?: $motherFull;
                    }
                }

                $guardianName = null;
                if (isset($g) && isset($g->guardian_name) && $g->guardian_name) {
                    $guardianName = $g->guardian_name;
                } elseif (isset($g) && (isset($g->g_firstname) || isset($g->g_lastname))) {
                    $guardianName = trim(collect([$g->g_firstname ?? null, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' ')) ?: null;
                }

                $household = $parents;
                if ($guardianName && stripos($parents, $guardianName) === false) {
                    $household .= ' / ' . $guardianName;
                }
                $household = $household ?: '—';

                if ($paid > 0) {
                    $recentPaymentsView->push([
                        'household' => $household,
                        'student' => trim(($s->s_firstname ?? '') . ' ' . ($s->s_middlename ?? '') . ' ' . ($s->s_lastname ?? '')) ?: 'Unknown Student',
                        'grade' => $gradeKey,
                        'paid' => $paid,
                        'balance' => $currentBalance,
                        'when' => optional($s->updated_at)->format('Y-m-d g:i A'),
                        'raw_when' => optional($s->updated_at)->timestamp ?? 0,
                    ]);
                }
            }

            $gradeCounts = collect($gradeCounts)->sortKeys();

            $recentPaymentsView = $recentPaymentsView
                ->sortByDesc('raw_when')
                ->take(20)
                ->values();
        @endphp

        <div class="below-header">
            <!-- LEFT: charts + announcements + schedule -->
            <div class="left-stack">
                <!-- Charts -->
                <div class="card p-3">
                    <div class="row g-3">
                        <div class="col-12 col-xl-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Students per Grade Level</h6>
                                    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-primary">View
                                        details</a>
                                </div>
                                <div class="card-body chart-wrap">
                                    <canvas id="chartGradeLevels"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Paid vs Outstanding Balance</h6>
                                    <a href="{{ route('admin.finances') }}" class="btn btn-sm btn-outline-primary">View
                                        details</a>
                                </div>
                                <div class="card-body chart-wrap">
                                    <canvas id="chartPaidBalance"></canvas>
                                    <div class="small text-muted mt-2">
                                        <span class="me-3">Paid: <strong>₱{{ number_format($paidTotal, 2) }}</strong></span>
                                        <span>Outstanding: <strong>₱{{ number_format($balTotal, 2) }}</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="card" id="announcements-section">
                    <div class="card-header d-flex justify-content-between align-items-center p-3">
                        <h6 class="mb-0">Announcements</h6>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addAnnouncementModal">
                                <i class="bi bi-megaphone me-1"></i> Add
                            </button>
                            <button class="btn btn-sm btn-outline-secondary collapse-toggle"
                                data-bs-target="#announcementsCollapse" aria-expanded="false"
                                aria-controls="announcementsCollapse">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div id="announcementsCollapse" class="collapse">
                        <div class="card-body">
                            @if($announcements->isEmpty())
                                <p class="text-muted">No announcements yet.</p>
                            @else
                                <ul class="list-group" id="announcementsList">
                                    @foreach($announcements as $a)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $a->title ?? 'Untitled' }}</strong>
                                                @if($a->content) — {{ $a->content }} @endif
                                                <br>
                                                <small class="text-muted d-block">
                                                    @if($a->date_of_event)
                                                        <span class="me-3">Event: {{ $a->date_of_event->format('Y-m-d') }}</span>
                                                    @endif
                                                    @if($a->deadline)
                                                        <span class="me-3">Deadline: {{ $a->deadline->format('Y-m-d') }}</span>
                                                    @endif
                                                    <span class="me-3">
                                                        For:
                                                        @php $names = $a->gradelvls->pluck('grade_level')->filter()->values(); @endphp
                                                        {{ $names->isNotEmpty() ? $names->implode(', ') : 'All Grade Levels' }}
                                                    </span>
                                                    <span>Posted: {{ $a->created_at->format('Y-m-d g:i A') }}</span>
                                                </small>
                                            </div>

                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                    data-bs-target="#editAnnouncementModal{{ $a->id }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <form action="{{ route('admin.announcements.destroy', $a->id) }}" method="POST"
                                                    class="d-inline js-confirm-delete" data-confirm="Delete this announcement?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger js-delete-btn"
                                                        aria-label="Delete announcement">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </li>

                                        {{-- Per-row edit modal --}}
                                        @include('auth.admindashboard.partials.edit-announcement-modal', ['a' => $a, 'gradelvls' => $gradelvls])
                                    @endforeach
                                </ul>

                                <div id="announcementsToggle" class="list-toggle-wrap mt-2"></div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="card" id="schedule-section">
                    <div class="card-header d-flex justify-content-between align-items-center p-3">
                        <h6 class="mb-0">Schedule Notes</h6>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="scheduleSearch" class="form-control form-control-sm"
                                placeholder="Search schedule...">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#addScheduleModal">
                                <i class="bi bi-plus-circle me-1"></i> Add
                            </button>
                            <button class="btn btn-sm btn-outline-secondary collapse-toggle"
                                data-bs-target="#scheduleCollapse" aria-expanded="false" aria-controls="scheduleCollapse">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div id="scheduleCollapse" class="collapse">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="scheduleTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Subject</th>
                                            <th>Grade Level</th>
                                            <th>School Year</th>
                                            <th>Faculty</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($schedules->isNotEmpty())
                                            @foreach($schedules as $schedule)
                                                <tr>
                                                    <td><span class="badge bg-light text-dark border">{{ $schedule->day }}</span></td>
                                                    <td>{{ $schedule->class_start }} - {{ $schedule->class_end }}</td>
                                                    <td>{{ $schedule->subject->subject_name ?? '-' }}</td>
                                                    <td>{{ $schedule->gradelvls->grade_level ?? $schedule->gradelvl->grade_level ?? '-' }}</td>
                                                    <td>{{ $schedule->school_year ?? '—' }}</td>
                                                    <td>{{ $schedule->faculty->user->name ?? '—' }}</td>
                                                    <td class="text-nowrap">
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#editScheduleModal{{ $schedule->id }}">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <form action="{{ route('admin.schedules.destroy', $schedule->id) }}" method="POST"
                                                            class="d-inline js-confirm-delete"
                                                            data-confirm="Delete this schedule record?">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger js-delete-btn"
                                                                aria-label="Delete schedule">
                                                                <i class="bi bi-archive"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: recent payments -->
            <div class="right-stack">
                <div class="card p-3 pay-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Recent Payments</h6>
                        <div class="d-flex align-items-center gap-2">
                            <label for="paymentsShowCount" class="small text-muted">Show:</label>
                            <select id="paymentsShowCount" class="form-select form-select-sm" style="width:auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </div>

                    @if($recentPaymentsView->isNotEmpty())
                        <ul id="recentPaymentsList" class="list-unstyled list-unstyled-tight mb-0">
                            @foreach($recentPaymentsView as $rp)
                                <li class="recent-payment-item d-flex justify-content-between align-items-start">
                                    <div class="me-2">
                                        <strong>{{ $rp['household'] }}</strong>
                                        <div>{{ $rp['student'] }}</div>
                                        <div class="small text-muted">{{ $rp['grade'] }}</div>
                                        <div class="small text-muted">
                                            Paid so far: <strong>₱{{ number_format($rp['paid'], 2) }}</strong>
                                            • Balance: <strong>₱{{ number_format($rp['balance'], 2) }}</strong>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border meta-badge">{{ $rp['when'] ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">No payment activity yet.</p>
                    @endif
                </div>
            </div>

        </div> <!-- /below-header -->
    </div>

    {{-- Edit Schedule Modals (one per schedule) --}}
    @foreach($schedules as $schedule)
        <div class="modal fade" id="editScheduleModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    @include('auth.admindashboard.partials.edit-schedule-modal', ['schedule' => $schedule])
                </div>
            </div>
        </div>
    @endforeach

    {{-- Include Modals --}}
    @include('auth.admindashboard.partials.add-announcement-modal')
    @include('auth.admindashboard.partials.add-schedule-modal')
@endsection

@push('scripts')
    {{-- jQuery + DataTables + Bootstrap 5 adapter --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Chart.js for graphs --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        // SweetAlert2 delete
        (function () {
            function confirmDelete(form, msg, btn) {
                if (!window.Swal) { form.submit(); return; }
                Swal.fire({
                    title: 'Are you sure to delete this record?',
                    text: "You can't undo this action.",
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
    </script>

    <script>
        // DataTables for #scheduleTable
        $(function () {
            const scheduleDT = $('#scheduleTable').DataTable({
                dom: 'lrtip',
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
                order: [],
                language: { emptyTable: "No schedules available." },
                columnDefs: [{ targets: -1, orderable: false }]
            });
            $('#scheduleSearch').on('input', function () { scheduleDT.search(this.value).draw(); });
        });
    </script>

    <script>
        // Show more/less for Announcements UL
        (function attachListShowMore(listId, toggleWrapId, maxVisible = 10) {
            const ul = document.getElementById(listId);
            const wrap = document.getElementById(toggleWrapId);
            if (!ul || !wrap) return;

            const items = Array.from(ul.querySelectorAll('li'));
            if (items.length <= maxVisible) { wrap.innerHTML = ''; return; }

            let collapsed = true;
            function render() {
                items.forEach((li, idx) => { li.style.display = (collapsed && idx >= maxVisible) ? 'none' : ''; });
                wrap.innerHTML = '';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary btn-sm';
                btn.innerHTML = collapsed
                    ? `<i class="bi bi-chevron-down me-1"></i> Show more (${items.length - maxVisible})`
                    : `<i class="bi bi-chevron-up me-1"></i> Show less`;
                btn.addEventListener('click', () => { collapsed = !collapsed; render(); });
                wrap.appendChild(btn);
            }
            render();
        })('announcementsList', 'announcementsToggle', 10);
    </script>

    <script>
        // Charts
        (function () {
            const gradeLabels = {!! $gradeCounts->keys()->values()->toJson() !!};
            const gradeData = {!! $gradeCounts->values()->toJson() !!};

            const ctxA = document.getElementById('chartGradeLevels');
            if (ctxA) {
                new Chart(ctxA, {
                    type: 'bar',
                    data: { labels: gradeLabels, datasets: [{ label: 'Students', data: gradeData, borderWidth: 1 }] },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }

            const totalPaid = {{ (float) $paidTotal }};
            const totalBal = {{ (float) $balTotal }};
            const ctxB = document.getElementById('chartPaidBalance');
            if (ctxB) {
                new Chart(ctxB, {
                    type: 'doughnut',
                    data: { labels: ['Paid', 'Outstanding'], datasets: [{ data: [totalPaid, totalBal] }] },
                    options: {
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ₱${new Intl.NumberFormat().format(ctx.parsed)}` } }
                        }
                    }
                });
            }
        })();
    </script>

    <script>
        // Single-button collapse toggles (chevron up/down)
        (function () {
            document.querySelectorAll('.collapse-toggle').forEach(function (btn) {
                const targetSel = btn.getAttribute('data-bs-target') || btn.getAttribute('data-target');
                const targetEl = document.querySelector(targetSel);
                if (!targetEl) return;

                const coll = bootstrap.Collapse.getOrCreateInstance(targetEl, { toggle: false });

                function setIcon(expanded) {
                    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    btn.innerHTML = expanded
                        ? '<i class="bi bi-chevron-up"></i>'
                        : '<i class="bi bi-chevron-down"></i>';
                }

                setIcon(false);

                btn.addEventListener('click', function () {
                    const isExpanded = btn.getAttribute('aria-expanded') === 'true';
                    isExpanded ? coll.hide() : coll.show();
                });

                targetEl.addEventListener('shown.bs.collapse', () => setIcon(true));
                targetEl.addEventListener('hidden.bs.collapse', () => setIcon(false));
            });
        })();
    </script>

    <script>
        // Recent Payments: length control
        (function () {
            const list = document.getElementById('recentPaymentsList');
            const sel = document.getElementById('paymentsShowCount');
            if (!list || !sel) return;

            const items = Array.from(list.querySelectorAll('.recent-payment-item'));

            function applyLength() {
                const max = parseInt(sel.value, 10) || 10;
                items.forEach((li, idx) => {
                    li.classList.toggle('d-none', idx >= max);
                });
            }

            sel.addEventListener('change', applyLength);
            applyLength();
        })();
    </script>

    <script>
        // Quick Actions Search
        (function () {
            const input = document.getElementById('quickSearch');
            if (!input) return;

            const routes = {
                finances: "{{ route('admin.finances') }}",
                settings: "{{ route('admin.settings.index') }}",
                addSubject: "{{ route('admin.settings.index') }}?tab=subjects",
                enroll: "{{ route('admin.students.create') }}",
                students: "{{ route('admin.students.index') }}",
                grades: "{{ route('admin.grades') }}"
            };

            function go(q) {
                q = (q || '').toLowerCase().trim();
                if (!q) return;

                if (q.includes('pay') || q.includes('balance') || q.includes('finance')) { location.href = routes.finances; return; }
                if (q.includes('setting')) { location.href = routes.settings; return; }
                if (q.includes('subject')) { location.href = routes.addSubject; return; }
                if (q.includes('enroll')) { location.href = routes.enroll; return; }
                if (q.includes('student')) { location.href = routes.students; return; }
                if (q.includes('grade')) { location.href = routes.grades; return; }

                const moneyish = ['payment', 'receipt', 'fee', 'tuition', 'cash', 'ledger', 'invoice'];
                if (moneyish.some(w => q.includes(w))) { location.href = routes.finances; return; }

                location.href = routes.settings;
            }

            input.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(input.value); });
        })();
    </script>
@endpush
