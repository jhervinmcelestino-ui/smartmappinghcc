<?php
session_start();
require 'db.php';

// Check for admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.html');
    exit;
}

$mode = $_POST['mode'] ?? $_GET['mode'] ?? '';

switch ($mode) {
    case 'add':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'officer';
        $organization = trim($_POST['organization'] ?? null);

        if (empty($username) || empty($password)) {
            $_SESSION['message'] = 'Username and password are required.';
            $_SESSION['message_type'] = 'danger';
            header('Location: users.php');
            exit;
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['message'] = 'Username already exists.';
            $_SESSION['message_type'] = 'danger';
            header('Location: users.php');
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (username, password, role, organization) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashedPassword, $role, $organization]);

            $_SESSION['message'] = 'User added successfully!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error adding user: " . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
        break;

    case 'reset':
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            $_SESSION['message'] = 'Invalid user ID.';
            $_SESSION['message_type'] = 'danger';
            header('Location: users.php');
            exit;
        }

        $newPassword = '123456';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            $_SESSION['message'] = "Password for user ID {$userId} has been reset to '123456'.";
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error resetting password: " . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
        break;

    default:
        $_SESSION['message'] = 'Invalid action.';
        $_SESSION['message_type'] = 'danger';
        break;
}

header('Location: users.php');
exit;
?>