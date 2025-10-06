<?php
session_start();
require 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// DEBUGGING
if (!$username || !$password) {
  echo json_encode(['status' => 'error', 'message' => 'Missing username or password']);
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM officers WHERE username = ?");
$stmt->execute([$username]);
$officer = $stmt->fetch();

if (!$officer) {
  echo json_encode(['status' => 'error', 'message' => 'User not found']);
  exit;
}

if (!password_verify($password, $officer['password_hash'])) {
  echo json_encode(['status' => 'error', 'message' => 'Password mismatch']);
  exit;
}

$_SESSION['officer_id']   = $officer['id'];
$_SESSION['username']     = $officer['username'];
$_SESSION['organization'] = $officer['organization'];
$_SESSION['role']         = 'officer';

echo json_encode(['status' => 'success', 'redirect' => 'officer_dashboard.php']);
