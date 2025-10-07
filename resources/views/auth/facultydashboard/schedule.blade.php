@extends('layouts.faculty')

@section('title', 'Faculty · Schedule')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">
                {{ $canSeeAll ? 'All Faculty Schedules' : 'My Schedule' }}
            </h5>
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
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
                                    {{ $sch->faculty?->f_firstname }} {{ $sch->faculty?->f_lastname }}
                                    @if(!$sch->faculty) <span class="text-muted">—</span> @endif
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
                            <td colspan="{{ $canSeeAll ? 7 : 6 }}" class="text-center text-muted">
                                No schedules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
