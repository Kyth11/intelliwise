@extends('layouts.guardian')

@section('title', 'Guardian Dashboard')

@push('styles')
<style>
    .gcashpay img { height:24px; max-width:100px; }
</style>
@endpush

@section('content')
    <div class="topbar">
        <h3 class="mb-3">
            Welcome, {{ Auth::check() ? Auth::user()->name : 'Guardian' }}!
        </h3>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card p-4 text-center">
                <h5>Enrolled Children</h5>
                <h2>{{ ($stats['children_count'] ?? 0) }}</h2>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card p-4 text-center">
                <h5>Pending Reports</h5>
                <h2>{{ ($stats['pending_reports'] ?? 0) }}</h2>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card p-4 text-center">
                <h5>Messages</h5>
                <h2>{{ ($stats['messages_count'] ?? 0) }}</h2>
            </div>
        </div>
    </div>

    <div class="card p-4 mb-3">
        <h5>Announcements</h5>
        <p>No announcements yet.</p>
    </div>

    <div class="gcashpay card p-4 text-center">
        <a href="#" class="btn btn-white d-inline-flex align-items-center"
           style="background:#fff; color:#14006f; border:1px solid #ced4da;">
            Pay via
            <img src="{{ asset('images/Gcashtext.png') }}" alt="G-Cash" class="ms-2">
        </a>
    </div>
@endsection
