<?php
// filepath: redirect_to_request.php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$requestId = $_GET['id'] ?? null;

if ($requestId && is_numeric($requestId)) {
    header('Location: facilities_request.php?show_edit=' . $requestId);
    exit;
} else {
    header('Location: facilities_request.php?msg=' . urlencode('Invalid request ID for editing.'));
    exit;
}
?>