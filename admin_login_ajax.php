<?php
session_start();
require 'db.php';          // PDO for smartmapping_db

header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// ── basic form check ──
if ($username === '' || $password === '') {
  echo json_encode(['status' => 'error', 'message' => 'Missing username or password']);
  exit;
}

/* If admins are stored in `users` with a role column, change this query accordingly */
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
  echo json_encode(['status' => 'error', 'message' => 'User not found']);
  exit;
}

/* Column fallback: password_hash or password */
$hashField = isset($admin['password_hash']) ? 'password_hash' : 'password';

/* ── DEBUG: write plain password & hash to a file ── */
file_put_contents(
  'pwd_debug.txt',
  "USERNAME={$username}\nFORM_PW={$password}\nHASH={$admin[$hashField]}\n---\n",
  FILE_APPEND
);

if (!password_verify($password, $admin[$hashField])) {
  echo json_encode(['status' => 'error', 'message' => 'Password mismatch']);
  exit;
}

/* ── credentials are valid ── */
$_SESSION['user_id']    = $admin['id'];
$_SESSION['username']   = $admin['username'];
$_SESSION['role']       = 'admin';
$_SESSION['login_time'] = date('F j, Y - g:i A');

echo json_encode(['status' => 'success', 'redirect' => 'admin_dashboard.php']);
