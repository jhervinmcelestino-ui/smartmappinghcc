  <?php
  session_start();
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.html');
    exit;
  }
  require 'db.php';

  // Auto-reject events 1 day before the event_date
$today = new DateTime();
$cutoff = $today->modify('+1 day')->format('Y-m-d');

$autoRejectStmt = $pdo->prepare("UPDATE events SET status = 'Rejected' WHERE status = 'Pending' AND event_date <= ?");
$autoRejectStmt->execute([$cutoff]);

  $totalEvents   = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
  $pendingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status='Pending'")->fetchColumn();
  $approvedEvents= $pdo->query("SELECT COUNT(*) FROM events WHERE status='Approved'")->fetchColumn();
  $orgCount      = $pdo->query("SELECT COUNT(DISTINCT organization) FROM events")->fetchColumn();
  $notifStmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC");
  $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
  $notifCount = count($notifications);
  

  $overview = $pdo->query("
    SELECT title, organization, event_date, status
    FROM events
    ORDER BY event_date DESC
    LIMIT 10
  ")->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Smart Mapping – Admin Dashboard</title>
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

      .dark-mode .offcanvas {
        background-color: #1b2230;
        color: var(--text-invert);
      }

      .dark-mode .offcanvas .nav-link {
        color: #d9e2f1;
      }

      .dark-mode .offcanvas .nav-link:hover {
        background-color: #324565;
        color: white;
      }

      .dark-mode .offcanvas .nav-link.active {
        background-color: #3a4f6c;
        border-left: 4px solid #0d6efd;
        font-weight: 600;
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
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transition: 0.3s;
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

      /* TABLE DESIGN */
      .table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0,0,0,0.05);
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
        background-color: rgba(0, 0, 0, 0.01);
      }

      .table-striped tbody tr:hover {
        background-color: var(--hover-link);
        transition: background 0.2s ease-in-out;
      }

      .dark-mode .table thead th {
        background-color: #324565;
        color: var(--text-invert);
        border-bottom: 1px solid #46556e;
      }

      .dark-mode .table tbody td {
        background-color: #2c3344;
        color: var(--text-invert);
        border-bottom: 1px solid #3a4459;
      }

      .dark-mode .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255, 255, 255, 0.03);
      }

      .dark-mode .table-striped tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.08);
      }
    </style>
  </head>
  <body>

  <nav class="navbar navbar-light px-3">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
    <a class="navbar-brand fw-bold" href="#">📍 Smart Mapping – Admin Dashboard</a>
    <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
  </nav>

  <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
    <div class="offcanvas-header">
      <h5 class="mb-0">Admin Menu</h5>
      <button class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
      <ul class="nav flex-column">
        <li class="section-title">Main</li>
        <li><a class="nav-link active" href="#"><i class="fas fa-chart-line"></i>Dashboard</a></li>
        <li class="section-title">Manage Events</li>
        <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
        <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
        <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
        <li class="section-title">System Tools</li>
        <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
        <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
        <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
        <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
        <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        <li class="section-title">Session</li>
        <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
      <!-- Notifications Dropdown -->
  <div class="dropdown ms-auto me-3">
    <ul class="dropdown-menu">
  <?php foreach ($notifications as $note): ?>
    <li><a class="dropdown-item" href="#"><?php echo $note['facility']; ?></a></li>
  <?php endforeach; ?>

  <!-- IDAGDAG ITO SA ILALIM -->
  <li><hr class="dropdown-divider"></li>
  <li>
    <form action="mark_all_read.php" method="POST">
      <button type="submit" class="dropdown-item text-primary">Mark all as read</button>
    </form>
  </li>
</ul>

    <a class="btn btn-light dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
      🔔
      <?php if ($notifCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
          <?= $notifCount ?>
        </span>
      <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
      <?php if ($notifCount === 0): ?>
        <li><span class="dropdown-item text-muted">No new notifications</span></li>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
          <li>
            <span class="dropdown-item small">
              <?= htmlspecialchars($notif['message']) ?><br>
              <small class="text-muted"><?= $notif['created_at'] ?></small>
            </span>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>
  
      </ul>
    </div>
  </div>

  <div class="container py-5">

    <div class="row g-4 mb-5">
      <div class="col-md-3"><div class="card-stat"><h6>Pending Approvals</h6><h2 class="text-warning"><?= $pendingEvents ?></h2></div></div>
      <div class="col-md-3"><div class="card-stat"><h6>Approved Events</h6><h2 class="text-success"><?= $approvedEvents ?></h2></div></div>
      <div class="col-md-3"><div class="card-stat"><h6>Total Events</h6><h2 class="text-primary"><?= $totalEvents ?></h2></div></div>
      <div class="col-md-3"><div class="card-stat"><h6>Registered Orgs</h6><h2 class="text-info"><?= $orgCount ?></h2></div></div>
    </div>

    <h5 class="mb-3"><i class="fas fa-map-marker-alt text-primary me-2"></i>Event Overview (Latest 10)</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead><tr><th>Event Name</th><th>Date</th><th>Organization</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($overview as $ev): ?>
            <tr>
              <td><?= htmlspecialchars($ev['title']) ?></td>
              <td><?= htmlspecialchars($ev['event_date']) ?></td>
              <td><?= htmlspecialchars($ev['organization']) ?></td>
              <td>
                <?php if ($ev['status'] === 'Pending'): ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php elseif ($ev['status'] === 'Approved'): ?>
                  <span class="badge bg-success">Approved</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($ev['status']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$overview): ?>
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
