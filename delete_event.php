<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.html'); exit; }

require 'db.php';
$id = $_GET['id'] ?? '';
if ($id) {
  $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
}
header('Location: events.php?status=deleted');
