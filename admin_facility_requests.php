<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'admin') {
  header('Location: login.html'); exit;
}

$requests = $pdo->query("SELECT * FROM facility_requests ORDER BY status ASC, created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Facility Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
  <h2>🛠️ Admin – Facility Requests</h2>
  <a href="admin_dashboard.php" class="btn btn-outline-secondary">Exit</a>
</div>


  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>

  <table class="table table-bordered">
    <thead><tr>
      <th>Requester</th><th>Facility</th><th>Purpose</th><th>Date</th><th>Status</th><th>Action</th>
    </tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['requester']) ?></td>
          <td><?= htmlspecialchars($r['facility']) ?></td>
          <td><?= htmlspecialchars($r['purpose']) ?></td>
          <td><?= $r['date_requested'] ?></td>
          <td><span class="badge bg-<?= 
            $r['status'] === 'Approved' ? 'success' : 
            ($r['status'] === 'Denied' ? 'danger' : 'secondary') ?>">
            <?= $r['status'] ?></span>
          </td>
          <td>
            <?php if ($r['status'] === 'Pending'): ?>
              <a href="approve_facility.php?id=<?= $r['id'] ?>&action=approve" class="btn btn-sm btn-success">Approve</a>
              <a href="approve_facility.php?id=<?= $r['id'] ?>&action=deny" class="btn btn-sm btn-danger"
                 onclick="return confirm('Are you sure you want to deny this request?');">Deny</a>
            <?php else: ?>
              <em class="text-muted">No actions</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$requests): ?>
        <tr><td colspan="6" class="text-center text-muted">No requests found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>
