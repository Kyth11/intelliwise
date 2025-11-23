{{-- resources/views/guardians/dashboard.blade.php --}}
@extends('layouts.guardian')

@section('title', 'Guardian Dashboard')

@push('styles')
    <style>
        /* QR full-screen zoom */
        .qr-zoom-overlay {
            position: fixed;
            inset: 0;
            z-index: 1080;
            background: rgba(0, 0, 0, .9);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .qr-zoom-overlay.show {
            display: flex;
        }

        .qr-zoom-overlay img {
            max-width: 95vw;
            max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .6);
        }

        .qr-zoom-close {
            position: absolute;
            top: 12px;
            left: 12px;
        }

        body.qr-no-scroll {
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Str;
        use Illuminate\Support\Facades\Storage;
        use App\Models\AppSetting;
        use App\Models\CorHeader;
    @endphp

    @php
        // ===== Resolve & embed GCash QR (works even if /storage URL 404s) =====
        $gcashRaw = AppSetting::get('gcash_qr_path'); // may be URL, public/..., storage/..., or absolute path-ish
        $gcashQrSrc = null;   // <img src> value (data:... or URL)
        $gcashOk = false;  // renderable?

        if ($gcashRaw) {
            if (Str::startsWith($gcashRaw, ['http://', 'https://'])) {
                // Absolute URL saved. Use it directly.
                $gcashQrSrc = $gcashRaw;
                $gcashOk = true;
            } else {
                // Normalize to public-disk relative path
                $candidate = str_replace('\\', '/', $gcashRaw);
                $candidate = preg_replace('#^/?:?storage/#i', '', ltrim($candidate, '/'));
                $candidate = preg_replace('#^public/#i', '', $candidate);
                if (preg_match('#storage/app/public/(.+)$#i', $candidate, $m)) {
                    $candidate = $m[1];
                }
                if (preg_match('#[A-Za-z]:/.*?/storage/app/public/(.+)$#', $candidate, $m)) {
                    $candidate = $m[1];
                }
                $candidate = ltrim($candidate, '/');

                if ($candidate && Storage::disk('public')->exists($candidate)) {
                    try {
                        $bytes = Storage::disk('public')->get($candidate);
                        $mime = Storage::disk('public')->mimeType($candidate) ?? 'image/png';
                        $gcashQrSrc = 'data:' . $mime . ';base64,' . base64_encode($bytes); // ✅ embed
                        $gcashOk = true;
                    } catch (\Throwable $e) {
                        $gcashQrSrc = null;
                        $gcashOk = false;
                    }
                } else {
                    // Last resort: build the public URL (may 404, but try)
                    $url = $candidate ? Storage::disk('public')->url($candidate) : null;
                    if ($url) {
                        $gcashQrSrc = $url;
                        $gcashOk = true;
                    }
                }
            }
        }
    @endphp

    <div class="card section p-4">
        <div id="dashboard-header" class="mb-3">
            <div class="intro">
                <div>
                    <h5 class="mb-1">Welcome, {{ Auth::check() ? Auth::user()->name : 'Guardian' }}!</h5>
                    <div class="text-muted small">Here’s a quick snapshot of your learners, balances, and announcements.
                    </div>
                </div>
            </div>

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

            <div class="card pay-card p-3 text-center">
                <h6 class="mb-1">Pay Tuition & Fees</h6>
                <p class="text-muted mb-3 small">Pay via GCash. Scan the QR, then upload your receipt.</p>
                <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#gcashPayModal">
                    <i class="bi bi-wallet2 me-2"></i> Pay Now
                </button>
            </div>
        </div>

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
                        </li>
                    @endforeach
                </ul>

                <div id="announcementsToggle" class="list-toggle-wrap mt-2"></div>
            @endif
        </div>

        <div class="card mt-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Learners</h5>
                <a href="{{ route('guardians.children') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-people me-1"></i> Manage Learners
                </a>
            </div>

            @php
                $children = $children ?? ($guardian->students ?? collect());
            @endphp

            @if($children->count())
                @php
                    $sumPaid = 0.0;
                    $sumBalance = 0.0;

                    // preload COR records per student
                    $corByStudent = CorHeader::whereIn('student_id', $children->pluck('lrn'))
                        ->orderBy('date_enrolled', 'desc')
                        ->get()
                        ->groupBy('student_id');
                @endphp
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Grade Level</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Current Balance</th>
                                <th>Last Payment</th>
                                <th>COR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($children as $st)
                                @php
                                    $name = $st->full_name
                                        ?? trim(implode(' ', array_filter([$st->s_firstname ?? '', $st->s_middlename ?? '', $st->s_lastname ?? ''])));
                                    if ($name === '')
                                        $name = 'Student #' . $st->id;

                                    $grade = $st->s_gradelvvl ?? $st->s_gradelvl ?? optional($st->gradelvl)->grade_level ?? '—';

                                    $base = (float) ($st->s_tuition_sum ?? 0);
                                    $opt = (float) ($st->s_optional_total ?? 0);
                                    $origTotal = $base + $opt;

                                    $paymentsSum = 0.0;
                                    $lastPay = null;

                                    if (method_exists($st, 'payments')) {
                                        $pq = $st->payments();
                                        $paymentsSum = (float) ($pq->sum('amount') ?? 0);
                                        $lastPay = $pq->latest()->first();
                                    }

                                    if ($st->s_total_due !== null && $st->s_total_due !== '') {
                                        $currentBalance = max(0.0, (float) $st->s_total_due);
                                        $totalPaid = min($origTotal, max($origTotal - $currentBalance, 0.0));
                                    } else {
                                        $totalPaid = min($paymentsSum, $origTotal);
                                        $currentBalance = max(0.0, round($origTotal - $totalPaid, 2));
                                    }

                                    $sumPaid += $totalPaid;
                                    $sumBalance += $currentBalance;

                                    $lastPaymentText = '—';
                                    if ($lastPay) {
                                        $lpAmt = number_format((float) ($lastPay->amount ?? 0), 2);
                                        $lpWhen = \Illuminate\Support\Carbon::parse($lastPay->created_at ?? now())->format('Y-m-d g:i A');
                                        $lpMeth = $lastPay->payment_method ?? null;
                                        $lastPaymentText = "₱{$lpAmt} on {$lpWhen}" . ($lpMeth ? " ({$lpMeth})" : '');
                                    }

                                    // COR list for this student
                                    $corList = $corByStudent[$st->lrn] ?? collect();
                                    $latestCor = $corList->first();
                                @endphp
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td>{{ $grade }}</td>
                                    <td class="text-end">₱{{ number_format($totalPaid, 2) }}</td>
                                    <td class="text-end">₱{{ number_format($currentBalance, 2) }}</td>
                                    <td>{{ $lastPaymentText }}</td>
                                    <td>
                                        @if($latestCor)
                                            {{-- Old link kept for reference
                                            <a href="{{ route('guardians.cor.show', $latestCor->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                View COR
                                            </a>
                                            --}}
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-view-cor"
                                                    data-id="{{ $latestCor->id }}">
                                                View COR
                                            </button>
                                        @else
                                            <span class="text-muted small">None yet</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Totals:</th>
                                <th class="text-end">₱{{ number_format($sumPaid, 2) }}</th>
                                <th class="text-end">₱{{ number_format($sumBalance, 2) }}</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No learners linked to your account yet.</p>
            @endif
        </div>
    </div>

    {{-- GCash Pay & Receipt Modal --}}
    <div class="modal fade" id="gcashPayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>GCash Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            {{-- Left: QR --}}
                            <div class="border rounded p-2 text-center">
                                <div class="fw-semibold mb-2">Scan to Pay</div>
                                @if($gcashOk && $gcashQrSrc)
                                    <img src="{{ $gcashQrSrc }}" alt="GCash QR" class="img-fluid js-qr-zoom"
                                         style="max-height:420px; cursor:zoom-in;">
                                @else
                                    <div class="text-muted small">No GCash QR configured yet. Please contact admin.</div>
                                @endif
                            </div>

                            <div class="small text-muted mt-2">
                                After paying, upload your receipt on the right. Accepted: JPG/PNG/WEBP/PDF (max 5MB).
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <form action="{{ route('guardians.payment-receipts.store') }}" method="POST"
                                  enctype="multipart/form-data" autocomplete="off">
                                @csrf

                                <div class="mb-2">
                                    <label class="form-label">Student</label>
                                    <select name="student_lrn" class="form-select" required>
                                        @foreach(($children ?? collect()) as $st)
                                            @php
                                                $nm = trim(implode(' ', array_filter([$st->s_firstname ?? '', $st->s_middlename ?? '', $st->s_lastname ?? ''])));
                                                if ($nm === '')
                                                    $nm = 'Student #' . $st->id;
                                            @endphp
                                            <option value="{{ $st->id }}">{{ $nm }}
                                                ({{ $st->s_gradelvvl ?? $st->s_gradelvl ?? '—' }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Amount Paid (₱)</label>
                                        <input type="number" name="amount" step="0.01" min="1" class="form-control"
                                               required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">GCash Ref. No. (optional)</label>
                                        <input type="text" name="reference_no" class="form-control" maxlength="100">
                                    </div>
                                </div>

                                <div class="mb-2 mt-2">
                                    <label class="form-label">Upload Receipt Image / PDF</label>
                                    <input type="file" name="receipt" class="form-control"
                                           accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="3" maxlength="1000"
                                              placeholder="Anything we should know?"></textarea>
                                </div>

                                <div class="d-grid">
                                    <button class="btn btn-success" type="submit">
                                        <i class="bi bi-upload me-1"></i> Submit Receipt
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div> <!-- /row -->
                </div>
            </div>
        </div>
    </div>

    {{-- COR Modal --}}
    <div class="modal fade" id="corModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Certificate of Registration</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body" id="corModalBody">
                    <p class="text-center text-muted">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Full-screen QR overlay (click to open/close) --}}
    <div id="qrZoomOverlay" class="qr-zoom-overlay" aria-hidden="true">
        <button type="button" class="btn btn-light qr-zoom-close">← Back</button>
        <img id="qrZoomImg" src="" alt="GCash QR Enlarged">
    </div>
@endsection

@push('scripts')
    <script>
        // Show more/less for announcements
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
        })();

        // QR zoom handlers
        (function () {
            const overlay = document.getElementById('qrZoomOverlay');
            const imgBig = document.getElementById('qrZoomImg');

            function openZoom(src) {
                if (!overlay || !imgBig) return;
                imgBig.src = src || '';
                overlay.classList.add('show');
                document.body.classList.add('qr-no-scroll');
            }
            function closeZoom() {
                if (!overlay || !imgBig) return;
                overlay.classList.remove('show');
                document.body.classList.remove('qr-no-scroll');
                setTimeout(() => { imgBig.src = ''; }, 100);
            }

            // Click on the small QR
            document.addEventListener('click', function (e) {
                const t = e.target.closest('.js-qr-zoom');
                if (!t) return;
                const src = t.getAttribute('src');
                if (src) openZoom(src);
            });

            // Click anywhere on the overlay (except the enlarged image) or Back button to close
            overlay?.addEventListener('click', function (e) {
                const isBackBtn = e.target.closest('.qr-zoom-close');
                const clickedImg = e.target === imgBig;
                if (isBackBtn || !clickedImg) closeZoom();
            });

            // ESC to close
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay?.classList.contains('show')) closeZoom();
            });
        })();

        // COR modal loader
        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('.btn-view-cor');
            if (!btn) return;

            const id = btn.dataset.id;
            const modalBody = document.getElementById('corModalBody');
            modalBody.innerHTML = '<p class="text-center text-muted">Loading...</p>';

            const modalEl = document.getElementById('corModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            try {
                const res = await fetch(`/guardians/cor/fetch/${id}`);
                const data = await res.json();
                modalBody.innerHTML = data.html ?? '<p class="text-muted text-center">No COR content available.</p>';
            } catch (error) {
                modalBody.innerHTML =
                    '<p class="text-danger text-center">Failed to load the Certificate of Registration.</p>';
            }
        });
    </script>
@endpush
