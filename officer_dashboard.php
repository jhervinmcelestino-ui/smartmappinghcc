<?php
session_start();
if (!isset($_SESSION['officer_id']) || ($_SESSION['role'] ?? '') !== 'officer') {
  header('Location: officer_login.html');
  exit;
}

require 'db.php';

$username     = $_SESSION['username'];
$organization = $_SESSION['organization'];

/* ── Stat cards (your org) ── */
$totalEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organization=?");
$totalEvents->execute([$organization]);
$totalEvents = $totalEvents->fetchColumn();

$pendingEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organization=? AND status='Pending'");
$pendingEvents->execute([$organization]);
$pendingEvents = $pendingEvents->fetchColumn();

$approvedEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organization=? AND status='Approved'");
$approvedEvents->execute([$organization]);
$approvedEvents = $approvedEvents->fetchColumn();

/* ── Latest 10 events for your org ── */
$overviewStmt = $pdo->prepare("
  SELECT title, event_date, status
  FROM events
  WHERE organization=?
  ORDER BY event_date DESC
  LIMIT 10
");
$overviewStmt->execute([$organization]);
$overview = $overviewStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Latest 10 campus‑wide events NOT your org ── */
$campusStmt = $pdo->prepare("
  SELECT title, organization, event_date, status
  FROM events
  WHERE organization <> ?
  ORDER BY event_date DESC
  LIMIT 10
");
$campusStmt->execute([$organization]);
$campusEvents = $campusStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Officer Dashboard – Smart Mapping</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --bg-body: #f0f2f6;
      --bg-sidebar: #ffffff;
      --bg-navbar: #0d6efd;
      --bg-card: #ffffff;
      --text-main: #212529;
      --text-muted: #6c757d;
      --text-invert: #ffffff;
      --border-color: #dee2e6;
      --hover-link: #e3edff;
    }
    .dark-mode {
      --bg-body: #1e2430;
      --bg-sidebar: #1b2230;
      --bg-navbar: #111827;
      --bg-card: #2c3344;
      --text-main: #f8f9fa;
      --text-muted: #a5b0c2;
      --text-invert: #f1f5f9;
      --border-color: #394151;
      --hover-link: #2c3a50;
    }

    body {
      background: var(--bg-body);
      color: var(--text-main);
      font-family: "Segoe UI", sans-serif;
    }
    .navbar {
      background: var(--bg-navbar);
      color: var(--text-invert);
    }
    .navbar-brand, .dark-toggle {
      color: var(--text-invert) !important;
    }
    .dark-toggle {
      background: transparent;
      border: none;
      font-size: 20px;
    }

    .offcanvas {
      background: var(--bg-sidebar);
      border-right: 1px solid var(--border-color);
      width: 260px;
    }
    .offcanvas-header {
      border-bottom: 1px solid var(--border-color);
    }
    .offcanvas .nav-link {
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 18px;
      border-radius: 10px;
      transition: .2s;
    }
    .offcanvas .nav-link i {
      width: 20px;
      text-align: center;
      color: #0d6efd;
    }
    .offcanvas .nav-link:hover {
      background: var(--hover-link);
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
      font-weight: 700;
      color: var(--text-muted);
      padding: 15px 18px 5px;
    }
    .dark-mode .offcanvas {
      background: #1b2230;
      color: var(--text-invert);
    }
    .dark-mode .offcanvas .nav-link {
      color: #d9e2f1;
    }
    .dark-mode .offcanvas .nav-link:hover {
      background: #324565;
      color: #fff;
    }
    .dark-mode .offcanvas .nav-link.active {
      background: #3a4f6c;
    }
    .dark-mode .section-title {
      color: #97a3b8;
      border-top: 1px solid #2f3847;
    }

    .card-stat {
      background: var(--bg-card);
      border-radius: 16px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,.06);
      transition: .3s;
    }
    .card-stat:hover {
      transform: translateY(-4px);
    }
    .card-stat h6 {
      font-size: 14px;
      color: var(--text-muted);
    }
    .card-stat h2 {
      font-size: 32px;
      font-weight: 700;
    }

    .table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 14px rgba(0,0,0,.05);
    }
    .table thead th {
      background: var(--hover-link);
      color: var(--text-main);
      font-weight: 600;
      font-size: 15px;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border-color);
    }
    .table tbody td {
      background: var(--bg-card);
      color: var(--text-main);
      padding: 14px 16px;
      font-size: 14px;
      vertical-align: middle;
      border-bottom: 1px solid var(--border-color);
    }
    .table tbody tr:last-child td {
      border-bottom: none;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(0,0,0,.01);
    }
    .table-striped tbody tr:hover {
      background: var(--hover-link);
      transition: background .2s;
    }
    .dark-mode .table thead th {
      background: #324565;
      color: var(--text-invert);
      border-bottom: 1px solid #46556e;
    }
    .dark-mode .table tbody td {
      background: #2c3344;
      color: var(--text-invert);
      border-bottom: 1px solid #3a4459;
    }
    .dark-mode .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(255,255,255,.03);
    }
    .dark-mode .table-striped tbody tr:hover {
      background: rgba(255,255,255,.08);
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light px-3">
  <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
  <a class="navbar-brand fw-bold" href="#">📍 Smart Mapping – Officer Dashboard</a>
  <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<!-- Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="mb-0">Officer Menu</h5>
    <button class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="section-title">Main</li>
      <li><a class="nav-link active" href="#"><i class="fas fa-chart-line"></i>Dashboard</a></li>

      <li class="section-title">Events</li>
      <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
      <li class="section-title">Materials</li>
      <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
      <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>

      <li class="section-title">Facilities</li>
      <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Request</a></li>

      <li class="section-title">Settings</li>
      <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>

      <li class="section-title">Session</li>
      <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
  </div>
</div>

<!-- Main Content -->
<div class="container py-5">

  <!-- Greeting -->
  <div class="card p-4 mb-4" style="background: var(--bg-card); color: var(--text-main);">
    <h4 class="mb-2" style="color: var(--text-main)">Welcome, <strong><?= htmlspecialchars($username) ?></strong>!</h4>
    <p class="mb-0" style="color: var(--text-muted)">Organization: <strong><?= htmlspecialchars($organization) ?></strong></p>
  </div>

  <!-- Stat Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-4"><div class="card-stat"><h6>Pending Events</h6><h2 class="text-warning"><?= $pendingEvents ?></h2></div></div>
    <div class="col-md-4"><div class="card-stat"><h6>Approved Events</h6><h2 class="text-success"><?= $approvedEvents ?></h2></div></div>
    <div class="col-md-4"><div class="card-stat"><h6>Total Events</h6><h2 class="text-primary"><?= $totalEvents ?></h2></div></div>
  </div>

  <!-- Org Events -->
  <h5 class="mb-3"><i class="fas fa-building text-primary me-2"></i>Your Latest Events (10)</h5>
  <div class="table-responsive mb-5">
    <table class="table table-striped align-middle">
      <thead><tr><th>Event</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($overview as $ev): ?>
          <tr>
            <td><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= htmlspecialchars($ev['event_date']) ?></td>
            <td>
              <?php if($ev['status']==='Pending'): ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php elseif($ev['status']==='Approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($ev['status']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$overview): ?>
          <tr><td colspan="3" class="text-center text-muted">No events found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Campus Events -->
  <h5 class="mb-3"><i class="fas fa-globe text-primary me-2"></i>Campus‑wide Latest Events (10)</h5>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead><tr><th>Event</th><th>Organization</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($campusEvents as $ev): ?>
          <tr>
            <td><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= htmlspecialchars($ev['organization']) ?></td>
            <td><?= htmlspecialchars($ev['event_date']) ?></td>
            <td>
              <?php if($ev['status']==='Pending'): ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php elseif($ev['status']==='Approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($ev['status']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$campusEvents): ?>
          <tr><td colspan="4" class="text-center text-muted">No events found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('modeToggle').addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('modeToggle').querySelector('i');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
  });
</script>
</body>
</html>
