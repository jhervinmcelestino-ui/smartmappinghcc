<?php
session_start();
require 'db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: admin_login.html');
  exit;
}

// Handle approval/denial
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $eventId = $_POST['event_id'];
  $action  = $_POST['action'];

  if (in_array($action, ['Approved', 'Denied'])) {
    $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
    $stmt->execute([$action, $eventId]);
  }
}

// Get pending events
$pending = $pdo->query("
  SELECT id, title, organization, venue, event_date, status
  FROM events
  WHERE status = 'Pending'
  ORDER BY event_date
")->fetchAll(PDO::FETCH_ASSOC);

// Notifications (optional)
$notifStmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC");
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approve Events – Admin</title>
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
    .offcanvas .nav-link {
      color: var(--text-main);
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
      font-weight: bold;
      color: var(--text-muted);
      padding: 15px 18px 5px;
    }
    .table {
      background: var(--bg-card);
      border-radius: 10px;
      overflow: hidden;
    }
    .table thead {
      background: var(--hover-link);
    }
    .table th, .table td {
      padding: 14px;
      vertical-align: middle;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-light px-3">
  <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
  <a class="navbar-brand fw-bold" href="#">📍 Smart Mapping – Admin Dashboard</a>
  <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<!-- SIDEBAR -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="mb-0">Admin Menu</h5>
    <button class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="section-title">Main</li>
      <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
      <li class="section-title">Manage Events</li>
      <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
      <li><a class="nav-link active" href="#"><i class="fas fa-check-circle"></i>Approve Events</a></li>
      <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
      <li class="section-title">System Tools</li>
      <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
      <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
      <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
      <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
      <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
      <li class="section-title">Session</li>
      <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="container py-5">
  <h4 class="mb-4"><i class="fas fa-check-circle text-primary me-2"></i>Pending Event Approvals</h4>

  <?php if (empty($pending)): ?>
    <div class="alert alert-info">No pending events to review.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Event Name</th>
            <th>Organization</th>
            <th>Venue</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending as $event): ?>
            <tr>
              <td><?= htmlspecialchars($event['title']) ?></td>
              <td><?= htmlspecialchars($event['organization']) ?></td>
              <td><?= htmlspecialchars($event['venue']) ?></td>
              <td><?= htmlspecialchars($event['event_date']) ?></td>
              <td><span class="badge bg-warning text-dark"><?= $event['status'] ?></span></td>
              <td>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="Approved">
                  <button class="btn btn-success btn-sm">Approve</button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <input type="hidden" name="action" value="Denied">
                  <button class="btn btn-danger btn-sm">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
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
