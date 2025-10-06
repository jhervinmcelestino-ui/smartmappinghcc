<?php
// filepath: delete_request.php
session_start();
require 'db.php';

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

if (!in_array($role, ['admin', 'officer'])) {
    header('Location: login.html');
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id || !is_numeric($id)) {
    header('Location: facilities_request.php?msg=' . urlencode('Invalid request ID.'));
    exit;
}

if ($role === 'admin') {
    $stmt = $pdo->prepare("DELETE FROM facility_requests WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'Request deleted successfully by Admin.';
} elseif ($role === 'officer') {
    $stmt = $pdo->prepare("DELETE FROM facility_requests WHERE id = ? AND requested_by = ?");
    $stmt->execute([$id, $username]);
    if ($stmt->rowCount() > 0) {
        $msg = 'Your request has been deleted.';
    } else {
        $msg = 'Error: You do not have permission to delete this request.';
    }
} else {
    $msg = 'Error: You do not have permission to delete requests.';
}

header("Location: facilities_request.php?msg=" . urlencode($msg));
exit;
?>