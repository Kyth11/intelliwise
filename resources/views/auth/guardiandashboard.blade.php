<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/intelliwise.png') }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IGCA - Guardian Dashboard</title>
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
      <h4>Guardian Panel</h4>
      <a href="#" data-section="dashboard"><i class="bi bi-speedometer2"></i><span> Dashboard</span></a>
      <a href="#" data-section="children"><i class="bi bi-person-lines-fill"></i><span> Children</span></a>
      <a href="#" data-section="reports"><i class="bi bi-card-checklist"></i><span> Reports</span></a>
      <a href="#" data-section="settings"><i class="bi bi-gear"></i><span> Settings</span></a>
      <a href="{{ route('login') }}"><i class="bi bi-box-arrow-right"></i><span> Logout</span></a>
  </div>

  <!-- Content -->
  <div class="content">
      <div class="topbar">
          <h3 class="mb-0">
              Welcome,
              @if(Auth::check())
                  {{ Auth::user()->name }}
              @else
                  Guardian
              @endif
              !
          </h3>
      </div>

      <!-- Sections -->
      <div id="dashboard" class="section active">
          <div class="row">
              <div class="col-md-4 mb-4">
                  <div class="card p-4 text-center">
                      <h5>Enrolled Children</h5>
                      <h2>3</h2>
                  </div>
              </div>
              <div class="col-md-4 mb-4">
                  <div class="card p-4 text-center">
                      <h5>Pending Reports</h5>
                      <h2>2</h2>
                  </div>
              </div>
              <div class="col-md-4 mb-4">
                  <div class="card p-4 text-center">
                      <h5>Messages</h5>
                      <h2>5</h2>
                  </div>
              </div>
          </div>
          <div class="card p-4">
              <h5>Announcements</h5>
              <p>No announcements yet.</p>
          </div>
          <div class="gcashpay card text-center d-flex flex-column align-items-center">
              <a href="#" class="btn btn-white mb-3 d-flex" style="background-color: #fff; color: #14006f; border: 1px solid #ced4da;">
                Pay via <img src="{{ asset('images/Gcashtext.png') }}" alt="G-Cash" style="height: 24px; max-width: 100px;" class="ms-2">
              </a>
          </div>
      </div>

      <div id="children" class="section d-none">
          <div class="card p-4"><h5>Children</h5><p>Manage your children’s records here.</p></div>
      </div>

      <div id="reports" class="section d-none">
          <div class="card p-4"><h5>Reports</h5><p>View academic reports and performance.</p></div>
      </div>

      <div id="settings" class="section d-none">
          <div class="card p-4"><h5>Settings</h5><p>Account and notification settings.</p></div>
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
