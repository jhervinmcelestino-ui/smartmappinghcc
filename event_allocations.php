<?php
session_start();
require 'db.php';

$role = $_SESSION['role'] ?? '';
if (!$role) { header('Location: login.html'); exit; }

if ($role === 'admin')       $dashPage = 'admin_dashboard.php';
elseif ($role === 'officer') $dashPage = 'officer_dashboard.php';
else                         $dashPage = 'student_dashboard.php';

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Total count for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Get paginated data
$stmt = $pdo->prepare("
  SELECT venue, event_date, title, organization, event_type
  FROM events
  WHERE event_date >= CURDATE()
  ORDER BY venue, event_date
  LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Smart Mapping – Event Allocations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --bg-body:#f0f2f6;     --bg-navbar:#0d6efd; --bg-sidebar:#ffffff;
      --bg-card:#ffffff;      --text-main:#212529; --text-muted:#6c757d;
      --text-invert:#ffffff;  --border-color:#dee2e6;--hover-link:#e3edff;
      --hover-row:#f2f6fb;
    }
    .dark-mode{
      --bg-body:#1e2430;     --bg-navbar:#111827; --bg-sidebar:#1b2230;
      --bg-card:#2c3344;     --text-main:#f8f9fa; --text-muted:#a5b0c2;
      --text-invert:#f1f5f9; --border-color:#394151;--hover-link:#2c3a50;
      --hover-row:#2f3a4d;
    }
    body{background:var(--bg-body);color:var(--text-main);font-family:"Segoe UI",sans-serif}
    .navbar{background:var(--bg-navbar);color:var(--text-invert)}
    .navbar-brand,.dark-toggle{color:var(--text-invert)!important}
    .dark-toggle{background:transparent;border:none;font-size:20px}
    .offcanvas{background:var(--bg-sidebar);border-right:1px solid var(--border-color);width:260px}
    .offcanvas-header{border-bottom:1px solid var(--border-color)}
    .offcanvas .nav-link{color:var(--text-main);display:flex;align-items:center;gap:12px;
      padding:12px 18px;border-radius:10px;transition:.2s}
    .offcanvas .nav-link i{width:20px;text-align:center;color:#0d6efd}
    .offcanvas .nav-link:hover{background:var(--hover-link);transform:translateX(5px)}
    .offcanvas .nav-link.active{background:#dceeff;font-weight:600;border-left:4px solid #0d6efd}
    .section-title{font-size:11px;text-transform:uppercase;font-weight:bold;color:var(--text-muted);
      padding:15px 18px 5px}
    .dark-mode .offcanvas{background:#1b2230}
    .dark-mode .offcanvas .nav-link{color:#d9e2f1}
    .dark-mode .offcanvas .nav-link:hover{background:#324565}
    .dark-mode .offcanvas .nav-link.active{background:#3a4f6c;border-left:4px solid #0d6efd}
    .dark-mode .section-title{color:#97a3b8;border-top:1px solid #2f3847}
    .card{background:var(--bg-card);border-radius:16px;box-shadow:0 5px 20px rgba(0,0,0,.08)}
    .table thead th{background:var(--hover-row);color:var(--text-main)}
    .table tbody td{background:var(--bg-card);color:var(--text-main)}
    .table tbody tr:hover{background:var(--hover-row)}
    .dark-mode .table thead th{background:#324565;color:var(--text-invert)}
    .dark-mode .table tbody td{background:#2c3344;color:var(--text-invert)}
    .text-muted{color:var(--text-muted)!important}
    .dark-mode .text-muted{color:#cdd3dd!important}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar px-3 fixed-top">
  <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
  <span class="navbar-brand fw-bold">📍 Event Allocations</span>
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
    </ul>
  </div>
</div>

<!-- CONTENT -->
<div class="container py-5 mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">📋 Allocations of Scheduled Events</h4>

  </div>

  <div class="card p-4">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead>
          <tr><th>Venue</th><th>Date</th><th>Event Title</th><th>Organization</th><th>Type</th></tr>
        </thead>
        <tbody>
        <?php foreach($allocations as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['venue']) ?></td>
            <td><?= htmlspecialchars($row['event_date']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['organization']) ?></td>
            <td><?= htmlspecialchars($row['event_type']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$allocations): ?>
          <tr><td colspan="5" class="text-center text-muted">No events scheduled.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
          </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

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
