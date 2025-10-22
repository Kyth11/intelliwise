{{-- resources/views/layouts/guardian.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/intelliwise.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Apply saved theme ASAP to avoid FOUC --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                if (t === 'dark') document.documentElement.classList.add('theme-dark');
            } catch (e) {}
        })();
    </script>

    <title>@yield('title', 'IGCA - Guardian Dashboard')</title>
    @stack('styles')

    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">

    <!-- Local Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Local Bootstrap JS -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/dash.css') }}">
</head>

<body class="{{ (session('theme') === 'dark') ? 'theme-dark' : '' }}">
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo" id="logo">
        <h4>Guardian Panel</h4>

        <a href="{{ route('guardians.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('guardians.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i><span> Dashboard</span>
        </a>

        <a href="{{ route('guardians.children') }}"
           class="sidebar-link {{ request()->routeIs('guardians.children') ? 'active' : '' }}">
            <i class="bi bi-person-lines-fill"></i><span> Children</span>
        </a>

        <a href="{{ route('guardians.reports') }}"
           class="sidebar-link {{ request()->routeIs('guardians.reports') ? 'active' : '' }}">
            <i class="bi bi-card-checklist"></i><span> Reports</span>
        </a>

        <a href="{{ route('guardians.settings') }}"
           class="sidebar-link {{ request()->routeIs('guardians.settings') ? 'active' : '' }}">
            <i class="bi bi-gear"></i><span> Settings</span>
        </a>

        {{-- Logout (SweetAlert confirm) --}}
        <a href="{{ route('login') }}" class="sidebar-link js-logout" role="button">
            <i class="bi bi-box-arrow-right"></i><span> Logout</span>
        </a>
        @if(Route::has('logout'))
            <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        @endif
    </div>

    <!-- Main Content -->
    <div class="content" id="content">
        <!-- Flash Messages -->
        <div class="flash-messages position-fixed top-5 start-50 translate-middle-x mt-3" style="z-index:1050;">
            @foreach (['success', 'error'] as $msg)
                @if(session($msg))
                    <div class="alert alert-{{ $msg == 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                        {{ session($msg) }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endforeach
        </div>

        @yield('content')
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Sidebar Collapse Toggle + Theme -->
<script>
    const sidebar = document.getElementById('sidebar');
    const logo = document.getElementById('logo');

    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    logo.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });

    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function () {
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });
    });

    // Ensure body gets theme class (html handled in <head>)
    (function () {
        try {
            var t = localStorage.getItem('theme') || 'light';
            document.body.classList.toggle('theme-dark', t === 'dark');
        } catch (e) {}
    })();

    // Logout confirmation
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.js-logout');
        if (!link) return;
        e.preventDefault();

        Swal.fire({
            title: 'Sign out?',
            text: 'You will be returned to the login screen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, log me out',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            background: '#fff',
            backdrop: false,
            allowOutsideClick: true,
            allowEscapeKey: true
        }).then((r) => {
            if (!r.isConfirmed) return;
            const form = document.getElementById('logoutForm');
            if (form) form.submit();
            else window.location.href = link.getAttribute('href') || '{{ route('login') }}';
        });
    });

    // Auto-dismiss Flash Messages
    setTimeout(() => {
        document.querySelectorAll('.flash-messages .alert').forEach(el => new bootstrap.Alert(el).close());
    }, 2000);
</script>

@stack('scripts')
</body>
</html>
