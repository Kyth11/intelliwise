<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/intelliwise.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Apply saved theme ASAP to avoid FOUC --}}
    <script>
        (function () {
            try { if ((localStorage.getItem('theme') || 'light') === 'dark') document.documentElement.classList.add('theme-dark'); } catch (e) { }
        })();
    </script>

    <title>@yield('title', 'IGCA - Faculty')</title>
    @stack('styles')

<!-- Local Bootstrap CSS -->
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">

<!-- Local Bootstrap Icons -->
<link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">

<!-- Local Bootstrap JS -->
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/dash.css') }}">
</head>
<body>
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo" id="logo">
        <h4>Faculty Panel</h4>

        <a href="{{ route('faculty.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i><span> Dashboard</span>
        </a>

        <a href="{{ route('faculty.students') }}"
           class="sidebar-link {{ request()->routeIs('faculty.students') ? 'active' : '' }}">
            <i class="bi bi-people"></i><span> Students</span>
        </a>

        {{-- ✅ New Grades link --}}
        <a href="{{ route('faculty.grades.index') }}"
           class="sidebar-link {{ request()->routeIs('faculty.grades.*') ? 'active' : '' }}">
            <i class="bi bi-journal-check"></i><span> Grades</span>
        </a>

        <a href="{{ route('faculty.schedule') }}"
           class="sidebar-link {{ request()->routeIs('faculty.schedule') ? 'active' : '' }}">
            <i class="bi bi-calendar-event"></i><span> Schedule</span>
        </a>

        <a href="{{ route('faculty.settings') }}"
           class="sidebar-link {{ request()->routeIs('faculty.settings') ? 'active' : '' }}">
            <i class="bi bi-gear"></i><span> Settings</span>
        </a>

        <a href="{{ route('login') }}">
            <i class="bi bi-box-arrow-right"></i><span> Logout</span>
        </a>
    </div>

    <!-- Content -->
    <div class="content" id="content">
        {{-- Flash messages --}}
        <div class="flash-messages position-fixed top-5 start-50 translate-middle-x mt-3" style="z-index:1050;">
            @foreach (['success', 'error'] as $msg)
                @if(session($msg))
                    <div class="alert alert-{{ $msg == 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                        {{ session($msg) }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            @endforeach
        </div>

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar collapse
    const sidebar = document.getElementById('sidebar'), logo = document.getElementById('logo');
    if (localStorage.getItem('sidebar-collapsed') === 'true') sidebar.classList.add('collapsed');
    logo.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });

    // Ensure theme on body too
    (function () { try {
        const t = localStorage.getItem('theme') || 'light';
        document.body.classList.toggle('theme-dark', t === 'dark');
    } catch (e) { } })();

    // Auto-dismiss flash
    setTimeout(() => document.querySelectorAll('.flash-messages .alert').forEach(el => new bootstrap.Alert(el).close()), 2000);
</script>
@stack('scripts')
</body>
</html>
