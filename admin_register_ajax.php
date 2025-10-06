<?php
require 'db.php';
header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
  echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
  exit;
}

// Check for duplicates
$stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
  echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
  exit;
}

// Hash the password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
try {
  $stmt->execute([$username, $hash]);
  echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
}
