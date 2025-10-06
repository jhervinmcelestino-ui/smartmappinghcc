<?php
session_start();
require 'db.php';

/* --- always return JSON --- */
header('Content-Type: application/json');

/* --- grab POST values --- */
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Both fields are required.']);
    exit;
}

/* --- fetch user --- */
$stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    /* store session */
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['login_time'] = date('F j, Y - g:i A');

    /* choose redirect based on role */
    switch ($user['role']) {
        case 'admin':
            $redirect = 'admin_dashboard.php';
            break;
        case 'officer':
            $redirect = 'officer_dashboard.php';
            break;
        default:            /* student */
            $redirect = 'student_dashboard.php';
    }

    echo json_encode(['status' => 'success', 'redirect' => $redirect]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
}
?>
