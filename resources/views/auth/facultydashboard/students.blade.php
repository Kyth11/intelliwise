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
</style>
@endpush

@section('content')
<div class="card section p-4 students-page">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="section-title">
            <h4 class="mb-1">
                {{ ($canSeeAll ?? false) ? 'All Students' : 'My Students' }}
            </h4>
        </div>

        <div class="search-wrap">
            <input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Search students...">
        </div>
    </div>

    @php $byGrade = $studentsByGrade ?? collect(); @endphp

    @forelse($byGrade as $grade => $group)
        <div class="card mt-3 p-3 grade-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">
                    <span class="badge bg-light text-dark border badge-grade">{{ $grade ?: '— No Grade —' }}</span>
                    <span class="text-muted">({{ $group->count() }})</span>
                </h5>
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

                                // Build parents/guardian label (same idea as admin page, simplified)
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
                                }
                                if (!$guardianName && isset($g) && property_exists($g,'g_firstname')) {
                                    $legacy = trim(collect([$g->g_firstname, $g->g_middlename ?? null, $g->g_lastname ?? null])->filter()->implode(' '));
                                    $guardianName = $legacy ?: null;
                                }

                                $household = $parents;
                                if ($guardianName && stripos($parents, $guardianName) === false) {
                                    $household = $parents === '—' ? $guardianName : ($parents.' / '.$guardianName);
                                }
                            @endphp

                            <tr>
                                <td>{{ $s->s_firstname }} {{ $s->s_middlename }} {{ $s->s_lastname }}</td>
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

    function norm(s){ return (s || '').toLowerCase(); }

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
            // hide whole card if no visible rows during search
            if (q && !any) { card.style.display = 'none'; }
            else { card.style.display = ''; }
        });
    }

    input.addEventListener('input', filter);
    filter();
})();
</script>
@endpush
