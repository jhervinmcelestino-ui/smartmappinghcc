<?php
/*  print_events.php
 *  Printable HTML report of events
 *  Officers see only their org’s events; others see all
 *---------------------------------------------------------*/
session_start();
require 'db.php';

$role      = $_SESSION['role'] ?? '';
$isOfficer = $role === 'officer';
$org       = $_SESSION['organization'] ?? '';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['officer_id'])) {
  header('Location: login.html');
  exit;
}

if ($isOfficer && $org) {
  $stmt = $pdo->prepare("SELECT * FROM events WHERE organization = ? ORDER BY event_date");
  $stmt->execute([$org]);
} else {
  $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date");
}
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Event Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body class="p-4">

  <!-- Header + action buttons -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📄 Event Report</h2>
    <div class="no-print">
      <button class="btn btn-secondary me-2" onclick="window.location.href='events.php'">
        ⬅️ Exit
      </button>
      <button class="btn btn-primary" onclick="window.print()">
        🖨️ Print
      </button>
    </div>
  </div>

  <!-- Events table -->
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Title</th>
        <th>Organization</th>
        <th>Venue</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['title']) ?></td>
          <td><?= htmlspecialchars($e['organization']) ?></td>
          <td><?= htmlspecialchars($e['venue']) ?></td>
          <td><?= htmlspecialchars($e['event_date']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$events): ?>
        <tr><td colspan="4" class="text-center text-muted">No events found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>
