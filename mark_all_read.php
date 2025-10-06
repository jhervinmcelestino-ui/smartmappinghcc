<?php
session_start();
require 'db.php';

// Only allow admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.html');
    exit;
}

$pdo->query("UPDATE notifications SET is_read = 1");
header('Location: admin_dashboard.php');
