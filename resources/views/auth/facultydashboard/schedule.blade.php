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
    <div id="dashboard-header" class="mb-3">
        <div class="intro">
            <div>
                <h5 class="mb-1">{{ $canSeeAll ? 'All Faculty Schedules' : 'My Schedule' }}</h5>
                <div class="text-muted small">Weekly timetable with subjects and grade levels.</div>
            </div>
        </div>

        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-number">{{ $schedules->count() }}</div>
                <div class="kpi-label">Classes</div>
            </div>
            @if($canSeeAll)
            <div class="kpi-card">
                <div class="kpi-number">{{ $schedules->pluck('faculty_id')->filter()->unique()->count() }}</div>
                <div class="kpi-label">Faculty</div>
            </div>
            @endif
            <div class="kpi-card">
                <div class="kpi-number">{{ $schedules->pluck('day')->filter()->unique()->count() }}</div>
                <div class="kpi-label">Days</div>
            </div>
        </div>

        <div class="pay-card p-3 text-center">
            <h6 class="mb-1">Quick Action</h6>
            <p class="text-muted mb-3 small">Print this schedule.</p>
            <button id="btnPrint" class="btn btn-outline-dark btn-sm no-print">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

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
                @forelse($schedules as $sch)
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
                            No schedules found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
