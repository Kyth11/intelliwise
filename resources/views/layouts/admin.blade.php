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
                if (t === 'dark') {
                    document.documentElement.classList.add('theme-dark');
                }
            } catch (e) { }
        })();
    </script>

    <title>@yield('title', 'IGCA - Admin Dashboard')</title>
    @stack('styles')

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/dataTables.bootstrap5.min.css') }}">

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
            <h4>Admin Panel</h4>

            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span> Dashboard</span>
            </a>

            <a href="{{ route('admin.accounts') }}"
                class="sidebar-link {{ request()->routeIs('admin.accounts') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i><span> Manage Accounts</span>
            </a>

            <a href="{{ route('admin.faculties.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.faculties') ? 'active' : '' }}">
                <i class="bi bi-person-workspace"></i></i><span> Schedule </span>
            </a>
            {{-- Finances --}}
            <a href="{{ route('admin.finances') }}"
                class="sidebar-link {{ request()->routeIs('admin.finances') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i><span> Finances</span>
            </a>

            <a href="{{ route('admin.students.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.students') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span> Students</span>
            </a>


            <a href="{{ route('admin.grades') }}"
                class="sidebar-link {{ request()->routeIs('admin.grades') ? 'active' : '' }}">
                <i class="bi bi-journal-check"></i><span> Grades</span>
            </a>



            {{-- NEW: Reports (Enrollment) --}}
            <a href="{{ route('admin.reports.enrollments') }}"
                class="sidebar-link {{ request()->routeIs('admin.reports.enrollments') ? 'active' : '' }}">
                <i class="bi bi-table"></i><span> Reports</span>
            </a>

            <a href="{{ route('admin.settings.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="bi bi-gear"></i><span> Settings</span>
            </a>

            <a href="{{ route('login') }}">
                <i class="bi bi-box-arrow-right"></i><span> Logout</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="content" id="content">
            <!-- Flash Messages -->
            <div class="flash-messages position-fixed top-5 start-50 translate-middle-x mt-3" style="z-index: 1050;">
                @foreach (['success', 'error'] as $msg)
                    @if(session($msg))
                        <div class="alert alert-{{ $msg == 'success' ? 'success' : 'danger' }} alert-dismissible fade show"
                            role="alert">
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

    <!-- Sidebar Collapse Toggle -->
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

        // Apply saved theme to body as well (head script sets html class)
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                document.body.classList.toggle('theme-dark', t === 'dark');
            } catch (e) { }
        })();
    </script>

    <!-- Auto-dismiss Flash Messages -->
    <script>
        setTimeout(() => {
            document.querySelectorAll('.flash-messages .alert').forEach(alertEl => {
                let alert = new bootstrap.Alert(alertEl);
                alert.close();
            });
        }, 2000);
    </script>

    @stack('scripts')
</body>

</html>
