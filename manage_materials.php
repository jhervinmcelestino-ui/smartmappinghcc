<?php
session_start();
require 'db.php';

$role = $_SESSION['role'] ?? '';
if ($role === 'officer')      $dashPage = 'officer_dashboard.php';
elseif ($role === 'admin')    $dashPage = 'admin_dashboard.php';
else                          $dashPage = 'student_dashboard.php';

$materials = $pdo->query("
  SELECT em.id, em.file_path, em.location, e.title, e.event_date
  FROM event_materials em
  JOIN events e ON em.event_id = e.id
  ORDER BY e.event_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Smart Mapping – Manage Materials</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    :root {
      --bg-body:#f4f6f9; --bg-navbar:#0d6efd; --bg-sidebar:#ffffff;
      --bg-card:#ffffff; --text-main:#212529; --text-muted:#6c757d;
      --text-invert:#ffffff; --border-color:#dee2e6; --hover-link:#eaf2ff;
    }
    .dark-mode {
      --bg-body:#1e2430; --bg-navbar:#111827; --bg-sidebar:#1b2230;
      --bg-card:#2c3344; --text-main:#f8f9fa; --text-muted:#a5b0c2;
      --text-invert:#f1f5f9; --border-color:#394151; --hover-link:#2c3a50;
    }

    body { background: var(--bg-body); color: var(--text-main); font-family: "Segoe UI", sans-serif; }
    .navbar { background: var(--bg-navbar); color: var(--text-invert); }
    .navbar-brand, .dark-toggle { color: var(--text-invert) !important; }
    .dark-toggle { background: transparent; border: none; font-size: 20px; }

    /* Sidebar design only - gaya ng events.php */
    .offcanvas {
      background: var(--bg-sidebar, #fff);
      border-right: 1px solid var(--border-color, #dee2e6);
      width: 260px;
    }
    .offcanvas-header {
      border-bottom: 1px solid var(--border-color, #dee2e6);
    }
    .offcanvas .nav-link {
      color: var(--text-main, #212529);
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 18px;
      border-radius: 10px;
      transition: 0.2s;
    }
    .offcanvas .nav-link i {
      width: 20px;
      text-align: center;
      color: #0d6efd;
    }
    .offcanvas .nav-link:hover {
      background: var(--hover-link, #e3edff);
      transform: translateX(5px);
    }
    .offcanvas .nav-link.active {
      background: #dceeff;
      font-weight: 600;
      border-left: 4px solid #0d6efd;
    }
    .section-title {
      font-size: 11px;
      text-transform: uppercase;
      font-weight: bold;
      color: var(--text-muted, #6c757d);
      padding: 15px 18px 5px;
    }
    .dark-mode .offcanvas {
      background-color: #1b2230 !important;
      color: var(--text-invert, #f1f5f9) !important;
    }
    .dark-mode .offcanvas .nav-link {
      color: #d9e2f1 !important;
    }
    .dark-mode .offcanvas .nav-link:hover {
      background-color: #324565 !important;
      color: white !important;
    }
    .dark-mode .offcanvas .nav-link.active {
      background-color: #3a4f6c !important;
      border-left: 4px solid #0d6efd !important;
      font-weight: 600;
    }
    .dark-mode .section-title {
      color: #97a3b8 !important;
      border-top: 1px solid #2f3847;
    }

    .card { 
      background: var(--bg-card); 
      border-radius: 14px; 
      box-shadow: 0 4px 20px rgba(0,0,0,.08); 
    }
    .dark-mode .card {
      background: #23272f !important;
      color: #fff !important;
    }
    .table thead th {
      background: var(--hover-link);
      color: var(--text-main);
    }
    .table tbody td {
      background: var(--bg-card);
      color: var(--text-main);
    }
    .dark-mode .table thead th {
      background: #2f3a4d;
      color: #f1f5f9;
    }
    .dark-mode .table tbody td {
      background: #23272f;
      color: #f1f5f9;
    }
    .dark-mode .fw-bold,
    .dark-mode h5,
    .dark-mode .card .fw-bold {
      color: #fff !important;
    }
    .btn-danger.btn-sm {
      padding: 4px 10px;
      font-size: 14px;
    }
    .text-muted { color: var(--text-muted) !important; }
    .dark-mode .text-muted { color: #cdd3dd !important; }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar px-3 fixed-top">
  <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
  <span class="navbar-brand fw-bold">📂 Manage Materials</span>
  <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<!-- SIDEBAR -->
<div class="offcanvas offcanvas-start" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="mb-0"><?= ucfirst($role) ?> Menu</h5>
    <button class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <?php if ($role === 'admin'): ?>
        <li class="section-title">Main</li>
        <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
        <li class="section-title">Manage Events</li>
        <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
        <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
        <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
        <li class="section-title">System Tools</li>
        <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
        <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
        <li><a class="nav-link active fw-bold" href="#"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
        <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
        <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        <li class="section-title">Session</li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
      <?php elseif ($role === 'officer'): ?>
        <li class="section-title">Main</li>
        <li><a class="nav-link" href="officer_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
        <li class="section-title">Events</li>
        <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
        <li class="section-title">Facilities</li>
        <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Request</a></li>
        <li class="section-title">Materials</li>
        <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
        <li><a class="nav-link active fw-bold" href="#"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
        <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        <li class="section-title">Session</li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
      <?php elseif ($role === 'student'): ?>
        <li class="section-title">Main</li>
        <li><a class="nav-link" href="student_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
        <li class="section-title">Student Panel</li>
        <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        <li class="section-title">Session</li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- CONTENT -->
<div class="container pt-5 mt-4">
  <div class="col-lg-9 mx-auto">
    <div class="card p-4">
      <h5 class="fw-bold mb-3">Uploaded Event Materials</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Event</th>
              <th>Location</th>
              <th>File</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($materials as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['title']) ?> (<?= $m['event_date'] ?>)</td>
              <td><?= htmlspecialchars($m['location']) ?></td>
              <td><a href="uploads/<?= htmlspecialchars($m['file_path']) ?>" target="_blank">View</a></td>
              <td>
                <form method="POST" action="delete_material.php" onsubmit="return confirm('Are you sure you want to delete this material?');">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
                  <input type="hidden" name="file" value="<?= htmlspecialchars($m['file_path']) ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$materials): ?>
            <tr><td colspan="4" class="text-center text-muted">No materials uploaded yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const tgl=document.getElementById('modeToggle'),icon=tgl.querySelector('i');
  if(localStorage.getItem('theme')==='dark'){document.body.classList.add('dark-mode');icon.classList.replace('fa-moon','fa-sun');}
  tgl.addEventListener('click',()=>{
    document.body.classList.toggle('dark-mode');
    const dark=document.body.classList.contains('dark-mode');
    localStorage.setItem('theme',dark?'dark':'light');
    icon.classList.toggle('fa-moon',!dark);
    icon.classList.toggle('fa-sun',dark);
  });
</script>
</body>
</html>
