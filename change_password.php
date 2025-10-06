<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}

require 'db.php';

$userId = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $oldPassword = $_POST['old_password'] ?? '';
  $newPassword = $_POST['new_password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';
  $message = '';

  if (!$oldPassword || !$newPassword || !$confirmPassword) {
    $message = ['danger', "All fields are required."];
  } elseif ($newPassword !== $confirmPassword) {
    $message = ['danger', "New passwords do not match."];
  } else {
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user && password_verify($oldPassword, $user['password_hash'])) {
      $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
          ->execute([$newHash, $userId]);
      $message = ['success', "Password updated successfully."];
    } else {
      $message = ['danger', "Incorrect current password."];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
  <div class="card p-4 shadow-sm">
    <h4 class="mb-3">🔒 Change Password</h4>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= $message[0] ?>"><?= $message[1] ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Current Password</label>
        <input type="password" name="old_password" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required />
      </div>
      <button type="submit" class="btn btn-primary w-100">Update Password</button>
    </form>
    <a href="settings.php" class="d-block mt-3 text-center text-decoration-none">← Back to Settings</a>
  </div>
</div>
</body>
</html>
