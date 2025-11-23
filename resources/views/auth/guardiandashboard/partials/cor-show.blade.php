{{-- resources/views/guardians/cor-show.blade.php --}}
@extends('layouts.guardian')

@section('title', 'Certificate of Registration')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                Certificate of Registration
                @if($cor->student?->s_firstname || $cor->student?->s_lastname)
                    — {{ trim(($cor->student->s_firstname ?? '') . ' ' . ($cor->student->s_lastname ?? '')) }}
                @endif
            </h5>

            <a href="{{ route('guardians.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                ← Back to Dashboard
            </a>
        </div>

        @if(!empty($cor->html_snapshot))
            <div class="border rounded bg-white p-3">
                {{-- Render the stored COR HTML snapshot --}}
                {!! $cor->html_snapshot !!}
            </div>
        @else
            <p class="text-muted mb-0">
                No Certificate of Registration snapshot is stored for this record.
            </p>
        @endif
    </div>
@endsection
