<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $hashed = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");

  try {
    $stmt->execute([$username, $hashed]);
    header("Location: login.html?registered=1");
    exit;
  } catch (PDOException $e) {
    header("Location: register.html");
    exit;
  }
}
