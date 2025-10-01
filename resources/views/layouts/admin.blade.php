<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/intelliwise.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IGCA - Admin Dashboard')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dash.css') }}">
</head>

<body>
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

            <a href="{{ route('admin.students') }}"
               class="sidebar-link {{ request()->routeIs('admin.students') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span> Students</span>
            </a>

            <a href="{{ route('admin.faculties') }}"
               class="sidebar-link {{ request()->routeIs('admin.faculties') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i><span> Faculty</span>
            </a>

            <a href="{{ route('admin.settings') }}"
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
    </script>

    <!-- Auto-dismiss Flash Messages -->
    <script>
        setTimeout(() => {
            document.querySelectorAll('.flash-messages .alert').forEach(alertEl => {
                let alert = new bootstrap.Alert(alertEl);
                alert.close();
            });
        }, 3000); // 3 seconds
    </script>
</body>
</html>
