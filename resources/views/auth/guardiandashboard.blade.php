@extends('layouts.guardian')

@section('title', 'Guardian Dashboard')

@section('content')
    <div class="card section p-4">
        <!-- =========================
             Header: Welcome | KPIs | Pay
        ========================== -->
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <div>
                    <h5 class="mb-1">Welcome, {{ Auth::check() ? Auth::user()->name : 'Guardian' }}!</h5>
                    <div class="text-muted small">Here’s a quick snapshot of your learners, balances, and announcements.</div>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-number">{{ number_format($kpiLearners ?? 0) }}</div>
                    <div class="kpi-label">Learners</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">₱{{ number_format($kpiBalance ?? 0, 2) }}</div>
                    <div class="kpi-label">Account Balance</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-number">{{ ($announcements ?? collect())->count() }}</div>
                    <div class="kpi-label">Announcements</div>
                </div>
            </div>

            <!-- Pay card (kept minimal) -->
            <div class="card pay-card p-3 text-center">
                <h6 class="mb-1">Pay Tuition & Fees</h6>
                <p class="text-muted mb-3 small">Use GCash to settle balances conveniently.</p>
                <a href="#" class="btn btn-primary mb-2">
                    <i class="bi bi-wallet2 me-2"></i> Pay Now
                </a>
            </div>
        </div>

        <!-- =========================
             Announcements (view only)
        ========================== -->
        <div class="card mt-2 p-4" id="announcements-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Announcements</h5>
            </div>

            @if(($announcements ?? collect())->isEmpty())
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
                                    @if(!empty($a->date_of_event))
                                        <span class="me-3">Event:
                                            {{ \Illuminate\Support\Carbon::parse($a->date_of_event)->format('Y-m-d') }}</span>
                                    @endif
                                    @if(!empty($a->deadline))
                                        <span class="me-3">Deadline:
                                            {{ \Illuminate\Support\Carbon::parse($a->deadline)->format('Y-m-d') }}</span>
                                    @endif
                                    <span class="me-3">
                                        For:
                                        @php $names = optional($a->gradelvls)->pluck('grade_level')->filter()->values(); @endphp
                                        {{ ($names && $names->isNotEmpty()) ? $names->implode(', ') : 'All Grade Levels' }}
                                    </span>
                                    <span>Posted:
                                        {{ \Illuminate\Support\Carbon::parse($a->created_at)->format('Y-m-d g:i A') }}</span>
                                </small>
                            </div>
                            {{-- No extra "View" button needed here --}}
                        </li>
                    @endforeach
                </ul>

                <div id="announcementsToggle" class="list-toggle-wrap mt-2"></div>
            @endif
        </div>

        <!-- =========================
             Learners
        ========================== -->
        <div class="card mt-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Learners</h5>
                <a href="{{ route('guardians.children') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-people me-1"></i> Manage Learners
                </a>
            </div>

            @php $children = $children ?? ($guardian->students ?? collect()); @endphp

            @if($children->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Grade Level</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Current Balance</th>
                                <th>Last Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($children as $st)
                                @php
                                    // Name & grade display
                                    $name = $st->full_name
                                        ?? trim(implode(' ', array_filter([$st->s_firstname ?? '', $st->s_middlename ?? '', $st->s_lastname ?? ''])));
                                    if ($name === '') $name = 'Student #' . $st->id;

                                    $grade = $st->s_gradelvl ?? optional($st->gradelvl)->grade_level ?? '—';

                                    // Base figures from student fields
                                    $base      = (float) ($st->s_tuition_sum    ?? 0);
                                    $opt       = (float) ($st->s_optional_total ?? 0);
                                    $origTotal = $base + $opt;

                                    // Current balance from column (fallback to original total)
                                    $balance   = isset($st->s_total_due) ? (float) $st->s_total_due : $origTotal;

                                    // Totals via relation (prefer payments sum; fallback to orig - balance)
                                    $paymentsRelationLoaded = isset($st->payments) || method_exists($st, 'payments');
                                    $totalPaid = null;
                                    $lastPay = null;

                                    if ($paymentsRelationLoaded) {
                                        $totalPaid = (float) ($st->payments()->sum('amount') ?? 0);
                                        $lastPay   = $st->payments()->latest()->first();
                                    }
                                    if ($totalPaid === null) {
                                        $totalPaid = max($origTotal - $balance, 0);
                                    }

                                    // Last payment display string
                                    $lastPaymentText = '—';
                                    if ($lastPay) {
                                        $lpAmt   = number_format((float) $lastPay->amount, 2);
                                        $lpWhen  = \Illuminate\Support\Carbon::parse($lastPay->created_at)->format('Y-m-d g:i A');
                                        $lpMeth  = $lastPay->payment_method ?? null;
                                        $lastPaymentText = "₱{$lpAmt} on {$lpWhen}" . ($lpMeth ? " ({$lpMeth})" : '');
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td>{{ $grade }}</td>
                                    <td class="text-end">₱{{ number_format($totalPaid, 2) }}</td>
                                    <td class="text-end">₱{{ number_format($balance, 2) }}</td>
                                    <td>{{ $lastPaymentText }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No learners linked to your account yet.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Show more/less for Announcements UL (minimal; no extra buttons elsewhere)
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
@endpush
