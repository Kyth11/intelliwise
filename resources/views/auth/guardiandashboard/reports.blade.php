{{-- resources/views/auth/guardiandashboard/reports.blade.php --}}
@extends('layouts.guardian')

@section('title', 'Reports')

@push('styles')
    {{-- Shared dashboard styling --}}
    <link rel="stylesheet" href="{{ asset('css/app-dashboard.css') }}">

    <style>
        /* ----- Section headers & helpers ----- */
        .subtle { color: var(--bs-secondary-color, #6c757d); }

        /* =======================================================================
           ENROLLMENT REPORTS (list cards, filters, print)
        ======================================================================= */
        .students-page .section-title { display:flex; gap:.5rem; align-items:baseline; }
        .students-page .search-wrap { display:flex; gap:.5rem; align-items:center; }
        .students-page .search-wrap input { min-width:260px; }
        .report-list { display:grid; gap:1rem; }
        .grade-section { margin-top:1rem; }
        .grade-section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
        .grade-title { display:inline-flex; align-items:center; gap:.5rem; }
        .grade-title .count { color: var(--bs-secondary-color, #6c757d); font-size:.875rem; }
        .report-card {
            border: 1px solid rgba(0,0,0,.075);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            padding: 1rem;
        }
        .report-card-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .75rem;
            align-items: start;
            margin-bottom: .5rem;
        }
        .report-card h5 { margin:0; font-size:1.05rem; color:#2a5298; }
        .status-chips { display:inline-flex; gap:.4rem; flex-wrap:wrap; }
        .status-chips .badge { font-weight:600; }
        .report-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap: .6rem 1rem;
            margin-top: .25rem;
        }
        .kv { display: grid; grid-template-columns: 160px 1fr; gap: .4rem; }
        .kv .k { color: var(--bs-secondary-color, #6c757d); }
        .kv .v { font-weight: 600; white-space: pre-line; }
        .money-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(120px, 1fr));
            gap: .5rem;
            margin-top: .75rem;
        }
        .money-box {
            border: 1px dashed rgba(0,0,0,.15);
            border-radius: 10px;
            padding: .6rem .75rem;
            background: #f8fafc;
        }
        .money-box .label { font-size:.75rem; color:#6b7280; }
        .money-box .amt { font-weight:700; font-size:1rem; line-height:1.1; }
        .payments-section {
            margin-top: .75rem;
            background: #fafafa;
            border: 1px solid rgba(0,0,0,.06);
            border-radius: 10px;
            padding: .6rem;
        }
        .payments-title { display:flex; align-items:center; justify-content:space-between; margin-bottom:.4rem; }
        .payments-table { width:100%; border-collapse:collapse; font-size:.875rem; }
        .payments-table th, .payments-table td { border:1px solid rgba(0,0,0,.08); padding:.4rem .5rem; vertical-align:middle; }
        .payments-table thead th { background:#e9f2ff; font-weight:600; }
        .payments-footer { margin-top:.4rem; font-size:.875rem; color:#475569; }
        .card-actions { display:flex; gap:.5rem; align-items:center; }
        .btn-print-card { white-space:nowrap; }
        .payments-scroll { max-height: 220px; overflow: auto; border-radius: 8px; }

        /* Print rules */
        @media print {
            .btn-print-card, .toolbar, .filters, #pageSearch, .no-print { display: none !important; }
            .report-card { break-inside: avoid-page; page-break-inside: avoid; border-color:#ddd; box-shadow:none; }
            .payments-scroll { max-height:none !important; overflow:visible !important; }
        }

        /* =======================================================================
           GRADES REPORT (DepEd format)
        ======================================================================= */
        .table-report thead th { position: sticky; top: 0; background: var(--bs-body-bg); z-index: 1; }
        .table-report { table-layout: fixed; width: 100%; }
        .table-report th, .table-report td { padding: 6px 8px; }
        .table-report td { word-wrap: break-word; }
        .report-meta-compact { display:grid; grid-template-columns: repeat(2, minmax(220px,1fr)); gap:.35rem 1rem; margin-top:.5rem; }
        .report-meta-compact .kv{ display:grid; grid-template-columns:140px 1fr; gap:.35rem; }
        .report-meta-compact .k{ color: var(--bs-secondary-color,#6c757d); }
        .report-meta-compact .v{ font-weight:600; }
        @page { size: A4 portrait; margin: 10mm 12mm; }
        @media print {
            .table-report { font-size: 11px; }
            .table-report tr, .table-report td, .table-report th { page-break-inside: avoid; }
            .report-meta-compact .kv { grid-template-columns: 120px 1fr; }
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;

        $user       = Auth::user();
        $isGuardian = $user && ($user->role === 'guardian');

        // Guardian + children (load relations needed for both Enrollment & Grades)
        $guardianModel = $guardian
            ?? ($isGuardian && $user->guardian_id
                ? \App\Models\Guardian::with('students.gradelvl')->find($user->guardian_id)
                : null);

        $isDefaultGuardian = $isGuardian && (
            (($user->username ?? null) === 'guardian') ||
            ($guardianModel && $guardianModel->students->isEmpty())
        );

        $withRels = ['gradelvl','payments','optionalFees','tuition','guardian'];

        if ($isGuardian) {
            if ($isDefaultGuardian) {
                $children = \App\Models\Student::with($withRels)
                    ->orderBy('s_lastname')->orderBy('s_firstname')->get();
            } else {
                $children = optional($guardianModel)->students ?? collect();
                if ($children instanceof \Illuminate\Database\Eloquent\Collection) {
                    $children->loadMissing($withRels);
                }
            }
        } else {
            $children = \App\Models\Student::with($withRels)
                ->orderBy('s_lastname')->orderBy('s_firstname')->get();
        }

        // KPIs (Children-style header)
        $totalChildren = $children->count();
        $enrolledCount = $children->where('enrollment_status', 'Enrolled')->count();
        $uniqueGrades  = $children->map(fn($c) => $c->s_gradelvl ?? optional($c->gradelvl)->grade_level)->filter()->unique()->values();
        $gradeCount    = $uniqueGrades->count();

        // ================= Enrollment: group + money
        $byGrade = $children->groupBy(fn($s) => $s->s_gradelvl ?? optional($s->gradelvl)->grade_level ?? '— No Grade —');
        $gradeLabels = $byGrade->keys()->values();

        $pageTotals = ['tuition'=>0,'optional'=>0,'total_due'=>0,'paid'=>0,'balance'=>0];
        $moneyCache = [];

        foreach ($children as $s) {
            $base = 0;
            if (optional($s->tuition)->total_yearly !== null) {
                $base = (float) $s->tuition->total_yearly;
            } elseif ($s->s_tuition_sum !== null && $s->s_tuition_sum !== '') {
                $base = (float) $s->s_tuition_sum;
            }
            $optCollection = collect($s->optionalFees ?? []);
            $filtered = $optCollection->filter(function ($f) {
                $scopeOk = !isset($f->scope) || in_array($f->scope, ['student', 'both']);
                $activeOk = !property_exists($f, 'active') || (bool) $f->active;
                return $scopeOk && $activeOk;
            });
            $optional = (float) $filtered->sum(function($f){
                $amt = $f->pivot->amount_override ?? $f->amount;
                return (float) $amt;
            });
            $total = $base + $optional;

            $paidRecords = (float) ($s->payments()->sum('amount') ?? 0);
            if ($s->s_total_due !== null && $s->s_total_due !== '') {
                $balance = max(0, (float) $s->s_total_due);
                $paid    = max($total - $balance, 0);
            } else {
                $paid    = min($paidRecords, $total);
                $balance = max(0, round($total - $paid, 2));
            }

            $moneyCache[$s->id] = compact('base','optional','total','paid','balance');
            $pageTotals['tuition']   += $base;
            $pageTotals['optional']  += $optional;
            $pageTotals['total_due'] += $total;
            $pageTotals['paid']      += $paid;
            $pageTotals['balance']   += $balance;
        }

        // ================= Grades: filter lists
        $allStudentsList = $children->map(function ($s) {
            $mid = trim((string) ($s->s_middlename ?? ''));
            $name = trim(implode(' ', array_filter([$s->s_firstname ?? '', $mid, $s->s_lastname ?? ''])));
            return ['id' => $s->id, 'name' => $name !== '' ? $name : ('Student #' . $s->id), 'grade' => $s->s_gradelvl ?? optional($s->gradelvl)->grade_level ?? ''];
        })->values();

        $studentsByGrade     = $allStudentsList->groupBy('grade')->map->values();
        $guardianGradeLevels = $gradeLabels;

        $selectedSchoolYr = $schoolyrId ?? request('schoolyr_id');
        $selectedGrade    = $gradeLevel  ?? request('grade_level');
        $selectedStudent  = $studentId   ?? request('student_id');

        $currentStudent = isset($students)
            ? collect($students)->firstWhere('id', (int) $selectedStudent)
            : \App\Models\Student::find((int) $selectedStudent);

        $studentName = $currentStudent
            ? trim(implode(' ', array_filter([$currentStudent->s_firstname ?? '', $currentStudent->s_middlename ?? '', $currentStudent->s_lastname ?? ''])))
            : null;

        $gradeForReport = ($selectedGrade ?: null)
            ?? ($currentStudent->s_gradelvl ?? optional($currentStudent?->gradelvl)->grade_level ?? null)
            ?? '—';

        $schoolYrModel = isset($schoolyrs) ? collect($schoolyrs)->firstWhere('id', (int) $selectedSchoolYr) : null;
        $schoolYrText  = $schoolYrModel->display_year
            ?? ($schoolYrModel->school_year ?? ($selectedSchoolYr ? ('SY #' . $selectedSchoolYr) : '—'));

        $printedOn = now()->format('Y-m-d');

        // Safe defaults (controller might not pass these in view-only route)
        $gradeRows = $rows ?? [];
    @endphp

    {{-- =========================
         Header (Children-style)
    ========================== --}}
    <div class="card section p-4">
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <div>
                    <h5 class="mb-1">Reports</h5>
                    <div class="text-muted small">
                        @if($isGuardian && $isDefaultGuardian)
                            You’re viewing all learners (default guardian).
                        @elseif($isGuardian)
                            Your linked learners at a glance.
                        @else
                            Learners overview.
                        @endif
                    </div>
                </div>
            </div>

            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $totalChildren }}</div>
                    <div class="kpi-label">Students</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $enrolledCount }}</div>
                    <div class="kpi-label">Enrolled</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ $gradeCount }}</div>
                    <div class="kpi-label">Grade Levels</div>
                </div>
            </div>

            <div class="pay-card p-3 text-center">
                <h6 class="mb-1">Quick Action</h6>
                <p class="text-muted mb-3 small">Print your enrollment or grades below.</p>
                <button class="btn btn-outline-dark btn-sm no-print" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Page
                </button>
            </div>
        </div>

        @if($isGuardian && $isDefaultGuardian)
            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-people-fill me-2"></i>
                You are logged in as the default guardian. All enrolled students are shown below.
            </div>
        @elseif($isGuardian)
            <p class="mb-3 text-muted">View enrollment status and grade reports for your children.</p>
        @endif

        {{-- ==========================================================
             A) Enrollment Reports
        =========================================================== --}}
        <div class="card section p-4 students-page">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="section-title">
                    <h5 class="mb-0">Enrollment Reports</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-wrap">
                        <input type="text" id="pageSearch" class="form-control form-control-sm" placeholder="Quick search (name/contact/email)…">
                    </div>
                    <div class="toolbar d-flex gap-2">
                        <a id="printLink" class="btn btn-outline-secondary btn-sm" href="#">
                            <i class="bi bi-printer"></i> Print All
                        </a>
                    </div>
                </div>
            </div>

            {{-- Client-side Filters --}}
            <form class="filters row g-2 align-items-end mb-3" id="filtersForm" onsubmit="return false;">
                <div class="col-auto">
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" id="gradelvlSelect">
                        <option value="" data-label="">All</option>
                        @foreach ($gradeLabels as $gl)
                            <option value="{{ $gl }}" data-label="{{ $gl }}">{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto" style="min-width:260px">
                    <label class="form-label">Student</label>
                    <select id="studentSelect" class="form-select">
                        <option value="">— All Students —</option>
                        @foreach ($allStudentsList as $st)
                            <option value="{{ $st['id'] }}" data-grade="{{ $st['grade'] }}">{{ $st['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label">Enroll Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="">All</option>
                        <option>Enrolled</option>
                        <option>Not Enrolled</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label">Pay Status</label>
                    <select class="form-select" id="payStatusSelect">
                        <option value="">All</option>
                        <option>Paid</option>
                        <option>Partial</option>
                        <option>Unpaid</option>
                    </select>
                </div>
            </form>

            {{-- Grouped list by Grade --}}
            @forelse($byGrade as $grade => $rowsList)
                <section class="grade-section" data-grade-name="{{ $grade ?: '— No Grade —' }}">
                    <div class="grade-section-header">
                        <div class="grade-title">
                            <span class="badge bg-light text-dark border">{{ $grade ?: '— No Grade —' }}</span>
                            <span class="count">({{ $rowsList->count() }})</span>
                        </div>
                    </div>

                    <div class="report-list">
                        @foreach ($rowsList as $s)
                            @php
                                $g = $s->guardian;
                                $syText = $s->current_school_year ?? '—';

                                $mFirst = trim(collect([data_get($g,'m_firstname'), data_get($g,'m_middlename')])->filter()->implode(' '));
                                $mLast  = (string) data_get($g,'m_lastname', '');
                                $fFirst = trim(collect([data_get($g,'f_firstname'), data_get($g,'f_middlename')])->filter()->implode(' '));
                                $fLast  = (string) data_get($g,'f_lastname', '');
                                $motherFull = trim(($mFirst ? $mFirst.' ' : '').$mLast);
                                $fatherFull = trim(($fFirst ? $fFirst.' ' : '').$fLast);

                                $guardianPresent = null;
                                if ($g && isset($g->guardian_name) && $g->guardian_name) {
                                    $guardianPresent = $g->guardian_name;
                                } elseif ($g && (isset($g->g_firstname) || isset($g->g_lastname))) {
                                    $guardianPresent = trim(collect([$g->g_firstname ?? null, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' '));
                                }
                                $pgLines = [];
                                $pgLines[] = 'Father: ' . ($fatherFull ?: '—');
                                $pgLines[] = 'Mother: ' . ($motherFull ?: '—');
                                $pgLines[] = 'Guardian: ' . ($guardianPresent ?: '—');
                                $parentsGuardianDisplay = implode("\n", $pgLines);

                                $guardianContact = $g?->g_contact ?: ($g?->m_contact ?: ($g?->f_contact ?: ($g?->g_email ?: '—')));

                                $cardId = 'rep-card-st-' . $s->id;

                                $base     = $moneyCache[$s->id]['base'] ?? 0;
                                $optional = $moneyCache[$s->id]['optional'] ?? 0;
                                $total    = $moneyCache[$s->id]['total'] ?? 0;
                                $paid     = $moneyCache[$s->id]['paid'] ?? 0;
                                $balance  = $moneyCache[$s->id]['balance'] ?? 0;

                                $derivedPay = $balance <= 0.01 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');
                                $enrollStatus = $s->enrollment_status ?? ($balance < $total ? 'Enrolled' : 'Not Enrolled');
                            @endphp

                            <article class="report-card searchable-card"
                                     id="{{ $cardId }}"
                                     data-id="{{ $s->id }}"
                                     data-student-id="{{ $s->id }}"
                                     data-grade="{{ $grade }}"
                                     data-paystatus="{{ $derivedPay }}"
                                     data-enrollstatus="{{ $enrollStatus }}">
                                <div class="report-card-header">
                                    <div>
                                        <h5 title="{{ $s->full_name ?? '—' }}">
                                            {{ $s->full_name ?? trim(implode(' ', array_filter([$s->s_firstname ?? '', $s->s_middlename ?? '', $s->s_lastname ?? '']))) ?: '—' }}
                                        </h5>

                                        <div class="status-chips mt-1">
                                            @if($enrollStatus === 'Enrolled')
                                                <span class="badge bg-success badge-pill">Enrolled</span>
                                            @else
                                                <span class="badge bg-secondary badge-pill">Not Enrolled</span>
                                            @endif

                                            <span class="badge {{ $derivedPay === 'Paid' ? 'bg-success' : ($derivedPay === 'Partial' ? 'bg-warning text-dark' : 'bg-danger') }} badge-pill">
                                                {{ $derivedPay }}
                                            </span>

                                            <span class="badge bg-light text-dark border">{{ $syText }}</span>
                                        </div>

                                        <div class="report-meta mt-2">
                                            <div class="kv">
                                                <div class="k">Student Address</div>
                                                <div class="v">{{ $s->s_address ?? '—' }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">Birthdate</div>
                                                <div class="v">{{ $s->s_birthdate ? \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') : '—' }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">Parents / Guardian</div>
                                                <div class="v">{{ $parentsGuardianDisplay }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">Household Contact</div>
                                                <div class="v">{{ $guardianContact }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-actions">
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-print-card" data-card-id="{{ $cardId }}">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                    </div>
                                </div>

                                <div class="money-row">
                                    <div class="money-box"><div class="label">Tuition</div><div class="amt">{{ number_format($base, 2) }}</div></div>
                                    <div class="money-box"><div class="label">Optional</div><div class="amt">{{ number_format($optional, 2) }}</div></div>
                                    <div class="money-box"><div class="label">Total Due</div><div class="amt">{{ number_format($total, 2) }}</div></div>
                                    <div class="money-box"><div class="label">Balance</div>
                                        <div class="amt {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balance, 2) }}</div>
                                    </div>
                                </div>

                                <section class="payments-section">
                                    <div class="payments-title">
                                        <div class="fw-semibold">Payments ({{ $s->payments?->count() ?? 0 }})</div>
                                    </div>
                                    <div class="payments-scroll">
                                        @if(($s?->payments && $s->payments->count()))
                                            <table class="payments-table">
                                                <thead><tr><th style="width:110px">Date</th><th>Method</th><th>Status</th><th class="text-end" style="width:140px">Amount</th></tr></thead>
                                                <tbody>
                                                    @foreach ($s->payments->sortBy('created_at') as $p)
                                                        <tr>
                                                            <td>{{ optional($p->created_at)->format('Y-m-d') }}</td>
                                                            <td>{{ $p->payment_method }}</td>
                                                            <td>{{ $p->payment_status }}</td>
                                                            <td class="text-end">{{ number_format($p->amount, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="text-muted small">No payments recorded.</div>
                                        @endif
                                    </div>
                                    <div class="payments-footer">
                                        @php $paid = $moneyCache[$s->id]['paid'] ?? 0; $balance = $moneyCache[$s->id]['balance'] ?? 0; @endphp
                                        <strong>Total Paid:</strong> {{ number_format($paid, 2) }} |
                                        <strong>Remaining:</strong> {{ number_format($balance, 2) }}
                                    </div>
                                </section>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="card mt-3 p-3"><p class="text-muted mb-0">No learners found.</p></div>
            @endforelse

            {{-- Page totals --}}
            <div class="card mt-3 p-3">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                        <tr class="totals-row">
                            <td class="text-end" style="width:70%"><strong>Totals:</strong></td>
                            <td class="text-end"><strong>Tuition:</strong> {{ number_format($pageTotals['tuition'], 2) }}</td>
                            <td class="text-end"><strong>Optional:</strong> {{ number_format($pageTotals['optional'], 2) }}</td>
                            <td class="text-end"><strong>Total Due:</strong> {{ number_format($pageTotals['total_due'], 2) }}</td>
                            <td class="text-end"><strong>Paid:</strong> {{ number_format($pageTotals['paid'], 2) }}</td>
                            <td class="text-end"><strong>Balance:</strong> {{ number_format($pageTotals['balance'], 2) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 subtle">* Totals reflect all learners listed above; filters only hide on screen.</div>
            </div>
        </div>

        {{-- ==========================================================
             B) Grades (DepEd format)
        =========================================================== --}}
        <div class="card section p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Grades</h5>
                <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>

            {{-- Filters --}}
            <div class="card p-3 mb-3">
                <form class="row g-2 align-items-end" method="GET">
                    <div class="col-md-3">
                        <label class="form-label">School Year</label>
                        <select name="schoolyr_id" class="form-select">
                            <option value="">Select school year</option>
                            @if(!empty($schoolyrs))
                                @foreach($schoolyrs as $sy)
                                    <option value="{{ $sy->id }}" {{ (int) ($selectedSchoolYr ?? 0) === (int) $sy->id ? 'selected' : '' }}>
                                        {{ $sy->display_year ?? $sy->school_year ?? ('SY #' . $sy->id) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Grade Level</label>
                        <select name="grade_level" id="gradeLevelFilter" class="form-select">
                            <option value="">All grade levels</option>
                            @foreach($guardianGradeLevels as $gl)
                                <option value="{{ $gl }}" {{ (string) ($selectedGrade ?? '') === (string) $gl ? 'selected' : '' }}>
                                    {{ $gl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Student</label>
                        <select name="student_lrn" id="studentSelectGrades" class="form-select">
                            <option value="">Select student</option>
                            {{-- Populated by JS from the guardian’s children --}}
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 no-print">
                            <i class="bi bi-search"></i> View
                        </button>
                    </div>
                </form>
            </div>

            {{-- Report Card --}}
            <div class="report-card" id="guardianReportCard">
                <div class="report-header text-center mb-2">
                    <h6 class="mb-0">Report on Learner’s Progress</h6>
                    <div class="text-muted small">Quarterly Grades and Final Rating (DepEd Format)</div>
                </div>

                <div class="report-meta-compact">
                    <div class="kv"><div class="k">Student</div><div class="v">{{ $studentName ?: '—' }}</div></div>
                    <div class="kv"><div class="k">Grade Level</div><div class="v">{{ $gradeForReport ?: '—' }}</div></div>
                    <div class="kv"><div class="k">School Year</div><div class="v">{{ $schoolYrText }}</div></div>
                    <div class="kv"><div class="k">Printed On</div><div class="v">{{ $printedOn }}</div></div>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered align-middle table-report">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="min-width:220px;">Learning Areas</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Final Grade</th>
                                <th>Remarks</th>
                                <th>Descriptor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $__finals = []; @endphp

                            @forelse($gradeRows as $r)
                                @php
                                    // Source label resilience
                                    $subjectLabel = $r['subject'] ?? $r['subject_name'] ?? $r['name'] ?? '—';

                                    // Raw quarters for display
                                    $q1 = is_numeric($r['q1'] ?? null) ? (float)$r['q1'] : null;
                                    $q2 = is_numeric($r['q2'] ?? null) ? (float)$r['q2'] : null;
                                    $q3 = is_numeric($r['q3'] ?? null) ? (float)$r['q3'] : null;
                                    $q4 = is_numeric($r['q4'] ?? null) ? (float)$r['q4'] : null;

                                    // Normalize (any quarter <70 => 0; missing => 0)
                                    $norm = function ($v) { if ($v === null) return 0; return $v < 70 ? 0 : $v; };
                                    $n1 = $norm($q1); $n2 = $norm($q2); $n3 = $norm($q3); $n4 = $norm($q4);

                                    // Final grade = average of the four normalized quarters
                                    $computedFinal = round(($n1 + $n2 + $n3 + $n4) / 4);
                                    $__finals[] = $computedFinal;

                                    $remark = $computedFinal >= 75 ? 'PASSED' : 'FAILED';

                                    if      ($computedFinal >= 90) { $desc = 'Outstanding';          $abbr = 'O'; }
                                    elseif  ($computedFinal >= 85) { $desc = 'Very Satisfactory';    $abbr = 'VS'; }
                                    elseif  ($computedFinal >= 80) { $desc = 'Satisfactory';         $abbr = 'S'; }
                                    elseif  ($computedFinal >= 75) { $desc = 'Fairly Satisfactory';  $abbr = 'FS'; }
                                    else                           { $desc = 'Did Not Meet Expectations'; $abbr = 'DNME'; }
                                @endphp
                                <tr>
                                    <td>{{ $subjectLabel }}</td>
                                    <td class="text-center">{{ $r['q1'] ?? '—' }}</td>
                                    <td class="text-center">{{ $r['q2'] ?? '—' }}</td>
                                    <td class="text-center">{{ $r['q3'] ?? '—' }}</td>
                                    <td class="text-center">{{ $r['q4'] ?? '—' }}</td>
                                    <td class="text-center fw-semibold">{{ $computedFinal }}</td>
                                    <td class="text-center {{ $remark === 'FAILED' ? 'text-danger fw-semibold' : '' }}">
                                        {{ $remark }}
                                    </td>
                                    <td class="text-center">{{ $desc }} ({{ $abbr }})</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Select a school year and student to view grades.</td>
                                </tr>
                            @endforelse
                        </tbody>

                        @php
                            $hasRows = !empty($gradeRows) && (is_array($gradeRows) ? count($gradeRows) > 0 : (method_exists($gradeRows, 'isNotEmpty') ? $gradeRows->isNotEmpty() : true));
                            $ga = count($__finals) ? round(array_sum($__finals) / count($__finals)) : null;
                        @endphp
                        @if($hasRows)
                            <tfoot>
                                <tr class="table-light">
                                    <th>General Average</th>
                                    <th colspan="3"></th>
                                    <th></th>
                                    <th class="text-center">{{ $ga === null ? '—' : $ga }}</th>
                                    <th class="text-center">{{ $ga === null ? '—' : ($ga >= 75 ? 'PASSED' : 'FAILED') }}</th>
                                    <th class="text-center">
                                        @php
                                            $desc = null; $abbr = null;
                                            if ($ga !== null) {
                                                if      ($ga >= 90) { $desc = 'Outstanding';         $abbr = 'O'; }
                                                elseif  ($ga >= 85) { $desc = 'Very Satisfactory';   $abbr = 'VS'; }
                                                elseif  ($ga >= 80) { $desc = 'Satisfactory';        $abbr = 'S'; }
                                                elseif  ($ga >= 75) { $desc = 'Fairly Satisfactory'; $abbr = 'FS'; }
                                                else { $desc = 'Did Not Meet Expectations'; $abbr = 'DNME'; }
                                            }
                                        @endphp
                                        {{ $desc ? ($desc . ' (' . $abbr . ')') : '—' }}
                                    </th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="small text-muted mt-2">
                    Notes: Final Grade = (Q1 + Q2 + Q3 + Q4) ÷ 4, where any quarter &lt; 70 is treated as 0.
                    Remarks follow DepEd thresholds (≥75 PASSED; &lt;75 FAILED).
                    Descriptors: O (90–100), VS (85–89), S (80–84), FS (75–79), DNME (&lt;75).
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    /* ================== Helpers ================== */
    function getSelectedOptionText(sel) {
        if (!sel || sel.selectedIndex < 0) return '';
        return (sel.options[sel.selectedIndex]?.textContent || '').trim();
    }

    /* ===== Client filters for Enrollment (guardian) ===== */
    function applyClientFilters() {
        const q   = (document.getElementById('pageSearch')?.value || '').toLowerCase();
        const pay = document.getElementById('payStatusSelect')?.value || '';
        const enr = document.getElementById('statusSelect')?.value || '';
        const gradeSel = document.getElementById('gradelvlSelect');
        const chosenGradeLabel = getSelectedOptionText(gradeSel);
        const studentSel = document.getElementById('studentSelect');
        const chosenStudentId = studentSel?.value || '';

        // per-card filter
        const cards = Array.from(document.querySelectorAll('.report-card'));
        cards.forEach(card => {
            const textOk   = !q   || card.innerText.toLowerCase().includes(q);
            const payOk    = !pay || (card.dataset.paystatus === pay);
            const enrollOk = !enr || (card.dataset.enrollstatus === enr);
            const stuOk    = !chosenStudentId || (card.dataset.studentId === chosenStudentId);
            card.style.display = (textOk && payOk && enrollOk && stuOk) ? '' : 'none';
        });

        // section visibility (grade match + has visible cards)
        const sections = Array.from(document.querySelectorAll('.grade-section'));
        sections.forEach(sec => {
            const gradeName = sec.getAttribute('data-grade-name') || '';
            const matchesGrade = !chosenGradeLabel || gradeName === chosenGradeLabel;
            const hasVisible  = !!sec.querySelector('.report-card:not([style*="display: none"])');
            sec.style.display = (matchesGrade && hasVisible) ? '' : 'none';
        });
    }

    // Wire client filters
    document.getElementById('pageSearch')?.addEventListener('input', applyClientFilters);
    document.getElementById('payStatusSelect')?.addEventListener('change', applyClientFilters);
    document.getElementById('statusSelect')?.addEventListener('change', applyClientFilters);
    document.getElementById('gradelvlSelect')?.addEventListener('change', function(){
        const studentSel = document.getElementById('studentSelect');
        if (studentSel) studentSel.value = '';
        applyClientFilters();
    });
    document.getElementById('studentSelect')?.addEventListener('change', applyClientFilters);

    /* ===== Initial pass ===== */
    applyClientFilters();

    /* ================== Printing via hidden iframe (guarded) ================== */
    function printHTML(html) {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.setAttribute('aria-hidden', 'true');
        document.body.appendChild(iframe);

        const win = iframe.contentWindow;
        const doc = iframe.contentDocument || win.document;

        let printed = false;
        function finishOnce() {
            if (printed) return;
            printed = true;
            try { win.focus(); win.print(); }
            finally { setTimeout(() => iframe.remove(), 500); }
        }

        iframe.addEventListener('load', finishOnce, { once: true });
        doc.open(); doc.write(html); doc.close();
        setTimeout(finishOnce, 1200); // fallback; guarded
    }

    /* ===== Per-card Print (Enrollment) ===== */
    (function(){
        function buildCardPrintHTML(cardEl){
            const title='Enrollment Report';
            const now=new Date().toLocaleString();
            const content=cardEl.cloneNode(true);
            content.querySelectorAll('.btn-print-card').forEach(b => b.remove());
            const scroll=content.querySelector('.payments-scroll');
            if (scroll){ scroll.style.maxHeight='none'; scroll.style.overflow='visible'; }
            const css=`
                @page { size: A4 portrait; margin: 16mm 14mm; }
                * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
                h1 { margin: 0 0 6px 0; font-size: 18px; color: #1f2937; }
                .sub { color: #6b7280; margin-bottom: 12px; }
                .report-card { border: 1px solid #ddd; border-radius: 8px; padding: 14px; box-shadow:none; }
                .money-row { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:10px; }
                .money-box { border:1px dashed #ccc; border-radius:8px; padding:8px; background:#f8fafc; }
                .payments-table { width:100%; border-collapse:collapse; font-size:12px; }
                .payments-table th,.payments-table td { border:1px solid #e5e7eb; padding:6px 8px; }
                .payments-table thead th { background:#eef5ff; }
            `;
            return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>${css}</style></head>
            <body>
              <header><h1>${title}</h1><div class="sub">Generated: ${now}</div></header>
              ${content.outerHTML}
            </body></html>`;
        }
        document.addEventListener('click', e=>{
            const btn=e.target.closest('.btn-print-card'); if(!btn) return;
            const card=document.getElementById(btn.getAttribute('data-card-id')); if(!card) return;
            const html=buildCardPrintHTML(card);
            printHTML(html);
        });
    })();

    /* ===== Print All visible cards (Enrollment) ===== */
    (function(){
        function buildBulkPrintHTML() {
            const title='Enrollment Reports';
            const now=new Date().toLocaleString();
            const sections=[...document.querySelectorAll('.grade-section')].filter(s=>s.style.display!=='none');
            const cards=[];
            sections.forEach(sec=>{
                cards.push(...[...sec.querySelectorAll('.report-card')].filter(c=>c.style.display!=='none'));
            });
            const clones=cards.map(c=>{
                const x=c.cloneNode(true);
                x.querySelectorAll('.btn-print-card').forEach(b => b.remove());
                const scr=x.querySelector('.payments-scroll'); if(scr){ scr.style.maxHeight='none'; scr.style.overflow='visible'; }
                return `<div class="card-wrap" style="break-inside:avoid; page-break-inside:avoid; margin:0 0 10px">${x.outerHTML}</div>`;
            }).join('');
            const css=`
                @page { size: A4 portrait; margin: 14mm 12mm; }
                * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                body { font-family: Arial, sans-serif; font-size: 11.5px; color:#111827; }
                h1 { margin: 0 0 8px; font-size: 18px; }
                .sub { color:#6b7280; margin-bottom: 12px; }
            `;
            return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>${css}</style></head>
            <body>
              <header><h1>${title}</h1><div class="sub">Generated: ${now} · Showing ${cards.length} record(s)</div></header>
              ${clones || '<div class="sub">No records to print.</div>'}
            </body></html>`;
        }
        document.getElementById('printLink')?.addEventListener('click', function(e){
            e.preventDefault();
            const html=buildBulkPrintHTML();
            printHTML(html);
        });
    })();

    /* ===== Grades: Student dropdown filtered by Grade (using guardian’s children) ===== */
    (function () {
        const STUDENTS_BY_GRADE = @json($studentsByGrade);
        const ALL_STUDENTS     = @json($allStudentsList);
        const PRESELECT_GRADE  = @json((string) ($selectedGrade ?? ''));
        const PRESELECT_STUDENT= @json((string) ($selectedStudent ?? ''));

        function opt(v, t, sel = false, disabled = false) {
            const o = document.createElement('option');
            o.value = v; o.textContent = t;
            if (sel) o.selected = true;
            if (disabled) o.disabled = true;
            return o;
        }

        function populateStudents(grade, selectedId) {
            const sel = document.getElementById('studentSelectGrades');
            if (!sel) return;
            sel.innerHTML = '';

            const hasGrade = !!(grade && String(grade).trim() !== '');
            if (hasGrade) {
                const list = (STUDENTS_BY_GRADE && STUDENTS_BY_GRADE[grade]) ? STUDENTS_BY_GRADE[grade] : [];
                if (!list.length) {
                    sel.appendChild(opt('', '— No students in this grade —', true, true));
                    return;
                }
                sel.appendChild(opt('', 'Select student in this grade', !selectedId));
                list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
                return;
            }

            const list = ALL_STUDENTS || [];
            sel.appendChild(opt('', 'Select student', !selectedId));
            list.forEach(s => sel.appendChild(opt(String(s.id), s.name, selectedId && String(selectedId) === String(s.id))));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const gradeSel = document.getElementById('gradeLevelFilter');
            populateStudents(gradeSel?.value || PRESELECT_GRADE, PRESELECT_STUDENT);
            gradeSel?.addEventListener('change', function () {
                populateStudents(this.value, null);
            });
        });
    })();
</script>
@endpush
