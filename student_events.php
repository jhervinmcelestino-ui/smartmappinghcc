<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
  header('Location: login.html');
  exit;
}
require 'db.php';

$stmt = $pdo->query("SELECT title, organization, venue, event_date, status FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Events – Student</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      padding-top: 4rem;
      font-family: 'Segoe UI', sans-serif;
    }
    .dark-mode {
      background-color: #121212;
      color: #fff;
    }
    .navbar {
      background-color: #0d6efd;
      color: white;
    }
    .navbar .btn-outline-light {
      border-color: white;
      color: white;
    }
    .offcanvas {
      background: #fff;
      border-right: 1px solid #dee2e6;
      width: 260px;
    }
    .offcanvas .nav-link {
      color: #212529;
      padding: 12px 18px;
      border-radius: 10px;
    }
    .offcanvas .nav-link.active, .offcanvas .nav-link:focus {
      background: #dceeff;
      font-weight: 600;
      border-left: 4px solid #0d6efd;
    }
    .offcanvas .nav-link:hover {
      background: #e3edff;
    }
    .section-title {
      font-size: 11px;
      text-transform: uppercase;
      font-weight: bold;
      color: #6c757d;
      padding: 15px 18px 5px;
    }
  </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar px-3 fixed-top">
  <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
    <i class="fas fa-bars"></i>
  </button>
  <span class="navbar-brand fw-bold">📍 Smart Mapping – Student Events</span>
  <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<!-- Sidebar -->
<div class="offcanvas offcanvas-start" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="mb-0">Menu</h5>
    <button class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="section-title">Main</li>
      <li><a class="nav-link" href="student_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
      <li class="section-title">Student Panel</li>
      <li><a class="nav-link active" href="student_events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
      <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
      <li class="section-title">Session</li>
      <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
  </div>
</div>

<div class="container py-5">
  <h4 class="mb-4"><i class="fas fa-calendar-alt text-primary me-2"></i>All Events</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead>
        <tr>
          <th>Event</th>
          <th>Organization</th>
          <th>Venue</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= htmlspecialchars($ev['organization']) ?></td>
            <td><?= htmlspecialchars($ev['venue']) ?></td>
            <td><?= htmlspecialchars($ev['event_date']) ?></td>
            <td>
              <?php if ($ev['status'] === 'Approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php elseif ($ev['status'] === 'Pending'): ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php else: ?>
                <span class="badge bg-danger">Denied</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?>
          <tr><td colspan="5" class="text-center text-muted">No events found.</td></tr>
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
  localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
});

document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    const icon = document.getElementById('modeToggle').querySelector('i');
    icon.classList.add('fa-sun');
  }
});
</script>
</body>
</html>