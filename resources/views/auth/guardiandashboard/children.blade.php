@extends('layouts.guardian')

@section('title', 'Children')

@section('content')
    <div class="topbar">
        <h3 class="mb-3">Children</h3>
    </div>

    <div class="card p-4">
        <p>Manage your children’s records here.</p>
        {{-- Example table (hook real data later) --}}
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Grade Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse(($guardian->students ?? []) as $child)
                    <tr>
                        <td>{{ $child->s_firstname }} {{ $child->s_middlename }} {{ $child->s_lastname }}</td>
                        <td>{{ $child->s_gradelvl ?? '—' }}</td>
                        <td>{{ $child->enrollment_status ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">No children found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
