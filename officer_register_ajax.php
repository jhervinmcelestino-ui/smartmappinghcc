<?php
require 'db.php';
header('Content-Type: application/json');

$username     = trim($_POST['username'] ?? '');
$password     = $_POST['password'] ?? '';
$organization = trim($_POST['organization'] ?? '');

if (!$username || !$password || !$organization) {
  echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
  exit;
}

// Check if username exists
$stmt = $pdo->prepare("SELECT id FROM officers WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
  echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
  exit;
}

// Hash password and insert
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO officers (username, password_hash, organization) VALUES (?, ?, ?)");
$stmt->execute([$username, $hash, $organization]);

echo json_encode(['status' => 'success']);
