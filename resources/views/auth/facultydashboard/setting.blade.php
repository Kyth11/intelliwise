@extends('layouts.faculty')

@section('title', 'Faculty · Settings')

@push('styles')
<style>
    .theme-chip { cursor:pointer; user-select:none; }
</style>
@endpush

@section('content')
<div class="card p-4">
    <h5 class="mb-3">Settings</h5>

    <div class="mb-3">
        <label class="form-label">Theme</label>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary theme-chip" data-theme="light">
                <i class="bi bi-sun"></i> Light
            </button>
            <button type="button" class="btn btn-outline-secondary theme-chip" data-theme="dark">
                <i class="bi bi-moon-stars"></i> Dark
            </button>
        </div>
        <small class="text-muted">Applies instantly and is remembered.</small>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.theme-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const t = btn.dataset.theme;
            localStorage.setItem('theme', t);
            document.documentElement.classList.toggle('theme-dark', t === 'dark');
            document.body.classList.toggle('theme-dark', t === 'dark');
        });
    });
</script>
@endpush
