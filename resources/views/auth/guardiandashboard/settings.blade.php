@extends('layouts.guardian')

@section('title', 'Settings')

@section('content')
    <div class="topbar">
        <h3 class="mb-3">Settings</h3>
    </div>

    <div class="card p-4">
        <p>Account & notification settings.</p>
        {{-- Example: self-update form can post to route('guardians.self.update', Auth::user()->guardian_id) --}}
    </div>
@endsection
