<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Dashboard</title>
  <!-- Bootstrap -->
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
      <h4>Faculty Dashboard</h4>
      <a href="#" data-section="dashboard"><i class="bi bi-speedometer2"></i><span> Dashboard</span></a>
      <a href="#" data-section="students"><i class="bi bi-people"></i><span> Students</span></a>
      <a href="#" data-section="assignments"><i class="bi bi-journal-text"></i><span> Assignments</span></a>
      <a href="#" data-section="schedule"><i class="bi bi-calendar-event"></i><span> Schedule</span></a>
      <a href="#" data-section="settings"><i class="bi bi-gear"></i><span> Settings</span></a>
      <a href="{{ route('login') }}"><i class="bi bi-box-arrow-right"></i><span> Logout</span></a>
  </div>

  <!-- Content -->
  <div class="content">
      <div class="topbar d-flex justify-content-between align-items-center mb-4">
          <h3 class="mb-0">
              Welcome,
              @if(Auth::check())
                  {{ Auth::user()->name }}
              @else
                  Faculty
              @endif
              !
          </h3>
      </div>

      <!-- Sections -->
      <div id="dashboard" class="section active">
          <div class="row">
              <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Assignments</h5><h2>3</h2></div></div>
              <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Students</h5><h2>8</h2></div></div>
              <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Messages</h5><h2>5</h2></div></div>
          </div>
          <div class="card p-4">
              <h5>Upcoming Activities</h5>
              <ul>
                  <li>Math Examination – March 2, 2026</li>
                  <li>Foundation Day – Feb 20, 2026</li>
                  <li>PTA Meeting – Feb 15, 2026</li>
              </ul>
          </div>
      </div>

      <div id="students" class="section d-none">
          <div class="card p-4"><h5>Students</h5><p>Manage your students here.</p></div>
      </div>

      <div id="assignments" class="section d-none">
          <div class="card p-4"><h5>Assignments</h5><p>Upload and track assignments.</p></div>
      </div>

      <div id="schedule" class="section d-none">
          <div class="card p-4"><h5>Schedule</h5><p>View your class schedule.</p></div>
      </div>

      <div id="settings" class="section d-none">
          <div class="card p-4"><h5>Settings</h5><p>Manage your account settings.</p></div>
      </div>
  </div>
</div>

<!-- Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar collapse
    const sidebar = document.getElementById('sidebar');
    const logo = document.getElementById('logo');
    logo.addEventListener('click', () => sidebar.classList.toggle('collapsed'));

    // Section switching
    document.querySelectorAll('.sidebar a[data-section]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('.section').forEach(s => s.classList.add('d-none'));
            document.getElementById(link.getAttribute('data-section')).classList.remove('d-none');
        });
    });
</script>
</body>
</html>
