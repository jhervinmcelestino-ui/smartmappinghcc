<?php
require 'db.php';

if (isset($_GET['username'])) {
  $username = trim($_GET['username']);

  $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
  $stmt->execute([$username]);

  if ($stmt->fetch()) {
    echo 'taken';
  } else {
    echo 'available';
  }
}
