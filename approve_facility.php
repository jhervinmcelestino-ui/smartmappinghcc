<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'admin') {
  header('Location: login.html'); exit;
}

$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'deny'])) {
  header('Location: admin_facility_requests.php?msg=Invalid request');
  exit;
}

$newStatus = $action === 'approve' ? 'Approved' : 'Denied';

$stmt = $pdo->prepare("UPDATE facility_requests SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $id]);

header("Location: admin_facility_requests.php?msg=Request $newStatus");
exit;
