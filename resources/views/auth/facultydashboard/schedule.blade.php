{{-- resources/views/faculty/schedules/index.blade.php --}}
@extends('layouts.faculty')
@section('title', 'Faculty · Schedule')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        @page { size: A4 portrait; margin: 14mm 12mm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table { page-break-inside: auto; }
        tr, td, th { page-break-inside: avoid; }
    }
</style>
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

        // If an active SY is known and schedules contain school_year, filter by it
        $displaySchedules = $activeSy
            ? $schedules->where('school_year', $activeSy)
            : $schedules;

        $classCount   = $displaySchedules->count();
        $facultyCount = $canSeeAll
            ? $displaySchedules->pluck('faculty_id')->filter()->unique()->count()
            : null;
        $daysCount    = $displaySchedules->pluck('day')->filter()->unique()->count();
    @endphp

    {{-- Header + KPIs + Quick Action --}}
    <div id="dashboard-header" class="mb-3 d-flex flex-wrap justify-content-between gap-3">
        <div class="intro">
            <div>
                <h5 class="mb-1">{{ $canSeeAll ? 'All Faculty Schedules' : 'My Schedule' }}</h5>
                <div class="text-muted small">Weekly timetable with subjects and grade levels.</div>
                @if($activeSy)
                    <div class="small mt-1">
                        <span class="badge bg-light text-dark border">
                            Active School Year: {{ $activeSy }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-stretch">
            <div class="kpi-strip d-flex gap-2">
                <div class="kpi-card">
                    <div class="kpi-number">{{ $classCount }}</div>
                    <div class="kpi-label">Classes</div>
                </div>
                @if($canSeeAll)
                    <div class="kpi-card">
                        <div class="kpi-number">{{ $facultyCount }}</div>
                        <div class="kpi-label">Faculty</div>
                    </div>
                @endif
                <div class="kpi-card">
                    <div class="kpi-number">{{ $daysCount }}</div>
                    <div class="kpi-label">Days</div>
                </div>
            </div>

            <div class="pay-card p-3 text-center no-print" style="min-width: 180px;">
                <h6 class="mb-1">Quick Action</h6>
                <p class="text-muted mb-3 small">Print this schedule.</p>
                <button id="btnPrint" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>

    {{-- Schedule table in its own card --}}
    <div class="card mt-3 p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        @if($canSeeAll)
                            <th>Faculty</th>
                        @endif
                        <th>Subject</th>
                        <th>Grade Level</th>
                        <th>Day</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displaySchedules as $sch)
                        <tr>
                            @if($canSeeAll)
                                <td>
                                    {{ trim(($sch->faculty?->f_firstname).' '.($sch->faculty?->f_lastname)) ?: '—' }}
                                </td>
                            @endif
                            <td>{{ $sch->subject->subject_name ?? '—' }}</td>
                            <td>{{ $sch->gradelvl->grade_level ?? '—' }}</td>
                            <td class="text-center">{{ $sch->day ?? '—' }}</td>
                            <td class="text-center">
                                {{ $sch->class_start ? \Carbon\Carbon::parse($sch->class_start)->format('h:i A') : '—' }}
                                –
                                {{ $sch->class_end ? \Carbon\Carbon::parse($sch->class_end)->format('h:i A') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAll ? 5 : 4 }}" class="text-center text-muted">
                                No schedules found{{ $activeSy ? ' for the active School Year (' . $activeSy . ')' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const btn = document.getElementById('btnPrint');
    if (!btn) return;
    let printing = false;
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        if (printing) return;
        printing = true;
        requestAnimationFrame(() => {
            window.print();
            setTimeout(() => { printing = false; }, 1200);
        });
    });
})();
</script>
@endpush
