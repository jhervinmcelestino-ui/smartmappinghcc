<?php
session_start();
require 'db.php';

// Check for admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.html');
    exit;
}

$userId = $_GET['id'] ?? null;

if (!$userId) {
    $_SESSION['message'] = 'Invalid user ID.';
    $_SESSION['message_type'] = 'danger';
    header('Location: users.php');
    exit;
}

// Prevent self-deletion
if ($userId == $_SESSION['user_id']) {
    $_SESSION['message'] = 'You cannot delete your own account.';
    $_SESSION['message_type'] = 'danger';
    header('Location: users.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = 'User deleted successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'User not found.';
        $_SESSION['message_type'] = 'danger';
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

header('Location: users.php');
exit;
?>