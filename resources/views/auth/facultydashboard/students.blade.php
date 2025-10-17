{{-- resources/views/faculty/students/index.blade.php --}}
@extends('layouts.faculty')
@section('title', 'Faculty · Students')

@push('styles')
<style>
    .students-page .section-title { display:flex; gap:.5rem; align-items:baseline; }
    .students-page .search-wrap { display:flex; gap:.5rem; align-items:center; }
    .students-page .search-wrap input { min-width:260px; }
    .students-page .grade-card { border:1px solid rgba(0,0,0,.075); box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .students-page .table td, .students-page .table th { vertical-align: middle; }
    .badge-grade { font-size:.825rem; }

    .filters .form-control, .filters .form-select {
        height: calc(1.5rem + .5rem + 2px);
        padding:.25rem .5rem;
        font-size:.85rem;
    }

    @media print {
        .no-print { display: none !important; }
        @page { size: A4 portrait; margin: 14mm 12mm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
<div class="card section p-4 students-page">
    <div id="dashboard-header" class="mb-3">
        <div class="intro">
            <div>
                <h5 class="mb-1">
                    {{ ($canSeeAll ?? false) ? 'Students (Grouped by Grade Level)' : 'My Students' }}
                </h5>
                <div class="text-muted small">
                    {{ ($canSeeAll ?? false) ? 'Browse and search all learners.' : 'Browse and search your learners.' }}
                </div>
            </div>
        </div>

        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($studentsByGrade)->flatten(1)->count() }}</div>
                <div class="kpi-label">Students</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-number">{{ collect($studentsByGrade)->keys()->count() }}</div>
                <div class="kpi-label">Grade Levels</div>
            </div>
        </div>

        <div class="pay-card p-3 text-center">
            <h6 class="mb-1">Quick Action</h6>
            <p class="text-muted mb-3 small">Print this page.</p>
            <button class="btn btn-outline-dark btn-sm no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Simple filter row (search only) -->
    <form class="filters row g-2 align-items-end mt-1 mb-2">
        <div class="col-auto">
            <label class="form-label mb-0 small">Search</label>
            <input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Type to filter…">
        </div>
    </form>

    @php $byGrade = $studentsByGrade ?? collect(); @endphp

    @forelse($byGrade as $grade => $group)
        <div class="card mt-2 p-3 grade-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">
                    <span class="badge bg-light text-dark border badge-grade">{{ $grade ?: '— No Grade —' }}</span>
                    <span class="text-muted">({{ $group->count() }})</span>
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle student-table">
                    <thead class="table-primary">
                        <tr>
                            <th>Name</th>
                            <th>Birthdate</th>
                            <th>Parents / Guardian</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group as $s)
                            @php
                                $g = $s->guardian;

                                $mFirst = trim(collect([data_get($g,'m_firstname'), data_get($g,'m_middlename')])->filter()->implode(' '));
                                $mLast  = (string) data_get($g,'m_lastname', '');
                                $fFirst = trim(collect([data_get($g,'f_firstname'), data_get($g,'f_middlename')])->filter()->implode(' '));
                                $fLast  = (string) data_get($g,'f_lastname', '');
                                $motherFull = trim(($mFirst ? $mFirst.' ' : '').$mLast);
                                $fatherFull = trim(($fFirst ? $fFirst.' ' : '').$fLast);

                                $parents = '—';
                                if ($motherFull || $fatherFull) {
                                    if ($motherFull && $fatherFull) {
                                        if ($mLast && $fLast && strcasecmp($mLast,$fLast) === 0) {
                                            $firstToUse = $fFirst ?: $mFirst;
                                            $lastToUse  = $fLast ?: $mLast;
                                            $parents = 'Mr. & Mrs. '.trim(($firstToUse ? $firstToUse.' ' : '').$lastToUse);
                                        } else {
                                            $parents = $motherFull.' & '.$fatherFull;
                                        }
                                    } else {
                                        $parents = $fatherFull ?: $motherFull;
                                    }
                                }

                                $guardianName = null;
                                if (isset($g) && ($g->guardian_name ?? null)) {
                                    $guardianName = $g->guardian_name;
                                } elseif (isset($g) && (isset($g->g_firstname) || isset($g->g_lastname))) {
                                    $legacy = trim(collect([$g->g_firstname ?? null, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' '));
                                    $guardianName = $legacy ?: null;
                                }

                                $household = $parents;
                                if ($guardianName && stripos($parents, $guardianName) === false) {
                                    $household = $parents === '—' ? $guardianName : ($parents.' / '.$guardianName);
                                }
                            @endphp

                            <tr>
                                <td>{{ trim(($s->s_firstname ?? '').' '.($s->s_middlename ?? '').' '.($s->s_lastname ?? '')) }}</td>
                                <td>{{ $s->s_birthdate ? \Illuminate\Support\Carbon::parse($s->s_birthdate)->format('Y-m-d') : '—' }}</td>
                                <td>{{ $household }}</td>
                                <td>{{ $s->s_contact ?? '—' }}</td>
                                <td>{{ $s->s_email ?? '—' }}</td>
                                <td>{{ $s->s_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card mt-3 p-3">
            <p class="text-muted mb-0">No students found.</p>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
(function() {
    const input = document.getElementById('studentSearch');
    if (!input) return;

    const cards = Array.from(document.querySelectorAll('.grade-card'));
    const norm = (s) => (s || '').toLowerCase();

    function filter() {
        const q = norm(input.value);
        cards.forEach(card => {
            const rows = card.querySelectorAll('tbody tr');
            let any = false;
            rows.forEach(tr => {
                const match = norm(tr.innerText).includes(q);
                tr.style.display = match ? '' : 'none';
                if (match) any = true;
            });
            // hide entire grade card if no rows match during search
            card.style.display = (q && !any) ? 'none' : '';
        });
    }

    input.addEventListener('input', filter);
    filter();
})();
</script>
@endpush
