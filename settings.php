<?php
session_start();

$role = $_SESSION['role'] ?? 'guest';

// Role checking logic
$isOfficer = ($role === 'officer' && isset($_SESSION['officer_id']));
$isAdmin = ($role === 'admin' && isset($_SESSION['user_id']));
$isStudent = ($role === 'student' && isset($_SESSION['user_id']));

// If no valid role is found, redirect to the appropriate login page
if (!$isOfficer && !$isAdmin && !$isStudent) {
    if ($role === 'officer') {
        header('Location: officer_login.html');
    } else {
        header('Location: login.html');
    }
    exit;
}

// Determine user details based on the identified role
$userId = null;
$username = '';
$table = '';
$pk = '';

if ($isAdmin || $isStudent) {
    $userId = $_SESSION['user_id'];
    $username = htmlspecialchars($_SESSION['username']);
    $table = 'users';
    $pk = 'id';
} elseif ($isOfficer) {
    $userId = $_SESSION['officer_id'];
    $username = htmlspecialchars($_SESSION['username']);
    $table = 'officers';
    $pk = 'id';
}

$passwordColumn = 'password_hash';
$usernameColumn = 'username';
$loginTime = $_SESSION['login_time'] ?? date('F j, Y - g:i A');

require 'db.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $oldPass = trim($_POST['old_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    $usernameChanged = !empty($newUsername) && $newUsername !== $_SESSION['username'];
    $passwordFieldsFilled = !empty($oldPass) || !empty($newPass) || !empty($confirm);
    
    // Check if any change was attempted
    if (!$usernameChanged && !$passwordFieldsFilled) {
        $error = "No changes were submitted.";
    }

    // Handle Username Update logic
    if ($usernameChanged) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$usernameColumn} = ? AND {$pk} != ?");
        $stmt->execute([$newUsername, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $error = "That username is already taken. Please choose another one.";
        } else {
            // Proceed with username update
            $pdo->prepare("UPDATE {$table} SET {$usernameColumn} = ? WHERE {$pk} = ?")
                ->execute([$newUsername, $userId]);
            $_SESSION['username'] = $newUsername;
            $username = htmlspecialchars($newUsername);
            $success = "Username updated successfully.";
        }
    }

    // Handle Password Update logic (only if all password fields are filled)
    if ($passwordFieldsFilled) {
        if (empty($oldPass) || empty($newPass) || empty($confirm)) {
            $error = "All password fields are required to change your password.";
        } elseif ($newPass !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            // Verify old password before updating
            $stmt = $pdo->prepare("SELECT {$passwordColumn} FROM {$table} WHERE {$pk} = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && password_verify($oldPass, $user[$passwordColumn])) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE {$table} SET {$passwordColumn} = ? WHERE {$pk} = ?")
                    ->execute([$newHash, $userId]);
                $success .= ($success ? "<br>" : "") . "Password updated successfully.";
            } else {
                $error = "Incorrect current password.";
            }
        }
    }
}
$dashPage = ($isOfficer) ? 'officer_dashboard.php' : (($isAdmin) ? 'admin_dashboard.php' : 'student_dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Smart Mapping – Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --bg-body: #f4f6f9; --bg-navbar: #0d6efd; --bg-sidebar: #ffffff;
            --bg-card: #ffffff; --text-main: #212529; --text-muted: #6c757d;
            --text-invert: #ffffff; --border-color: #dee2e6; --hover-link: #eaf2ff;
        }
        .dark-mode {
            --bg-body: #1e2430; --bg-navbar: #111827; --bg-sidebar: #1b2230;
            --bg-card: #2c3344; --text-main: #f8f9fa; --text-muted: #a5b0c2;
            --text-invert: #f1f5f9; --border-color: #394151; --hover-link: #2c3a50;
        }
        body, .navbar, .offcanvas, .card, .form-control, .form-label, .alert {
            transition: background-color 0.3s, color 0.3s;
        }
        body { background: var(--bg-body); color: var(--text-main); font-family: "Segoe UI", sans-serif; }
        .navbar { background: var(--bg-navbar); color: var(--text-invert); }
        .navbar-brand, .dark-toggle { color: var(--text-invert) !important; }
        .dark-toggle { background: transparent; border: none; font-size: 20px; }
        .offcanvas {
            background: var(--bg-sidebar, #fff);
            border-right: 1px solid var(--border-color, #dee2e6);
            width: 260px;
        }
        .offcanvas-header {
            border-bottom: 1px solid var(--border-color, #dee2e6);
        }
        .offcanvas .nav-link {
            color: var(--text-main, #212529);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            border-radius: 10px;
            transition: 0.2s;
        }
        .offcanvas .nav-link i {
            width: 20px;
            text-align: center;
            color: #0d6efd;
        }
        .offcanvas .nav-link:hover {
            background: var(--hover-link, #e3edff);
            transform: translateX(5px);
        }
        .offcanvas .nav-link.active {
            background: #dceeff;
            font-weight: 600;
            border-left: 4px solid #0d6efd;
        }
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            color: var(--text-muted, #6c757d);
            padding: 15px 18px 5px;
        }
        .dark-mode .offcanvas {
            background-color: var(--bg-sidebar) !important;
            color: var(--text-invert) !important;
        }
        .dark-mode .offcanvas .nav-link {
            color: var(--text-invert) !important;
        }
        .dark-mode .offcanvas .nav-link:hover {
            background-color: #324565 !important;
            color: #fff !important;
        }
        .dark-mode .offcanvas .nav-link.active {
            background-color: #3a4f6c !important;
            border-left: 4px solid #0d6efd !important;
            font-weight: 600;
            color: #fff !important;
        }
        .dark-mode .section-title {
            color: #cdd3dd !important;
            border-top: 1px solid #2f3847;
        }
        .card { background: var(--bg-card); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.08); color: var(--text-main); }
        .form-control {
            background-color: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .form-label {
            color: var(--text-main);
        }
        .text-muted { color: var(--text-muted) !important; }
        .dark-mode .text-muted { color: #cdd3dd !important; }
        .alert {
            background-color: #eaf2ff;
            color: #212529;
            border-color: #dee2e6;
        }
        .dark-mode .alert {
            background-color: #324565 !important;
            color: #f1f5f9 !important;
            border-color: #394151 !important;
        }
    </style>
</head>
<body class="bg-body text-main">

<nav class="navbar px-3 fixed-top">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
    <span class="navbar-brand fw-bold">⚙️ Settings</span>
    <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0"><?= ($role === 'officer') ? 'Officer Menu' : (($role === 'admin') ? 'Admin Menu' : 'Student Menu') ?></h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <?php if ($role === 'admin'): ?>
                <li class="section-title">Main</li>
                <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
                <li class="section-title">Manage Events</li>
                <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
                <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
                <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
                <li class="section-title">System Tools</li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
                <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
                <li><a class="nav-link active" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
                <li class="section-title">Session</li>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            <?php elseif ($role === 'officer'): ?>
                <li class="section-title">Main</li>
                <li><a class="nav-link" href="officer_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
                <li class="section-title">Events</li>
                <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
                <li class="section-title">Facilities</li>
                <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Request</a></li>
                <li class="section-title">Materials</li>
                <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link active" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
                <li class="section-title">Session</li>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            <?php else: ?>
                <li class="section-title">Main</li>
                <li><a class="nav-link" href="student_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
                <li class="section-title">Student Panel</li>
                <li><a class="nav-link active" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
                <li class="section-title">Session</li>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="container pt-5 mt-4">
    <div class="col-lg-8 mx-auto">
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-2">Welcome, <?= $username ?>!</h5>
            <p class="text-muted mb-0">Logged in at: <em><?= $loginTime ?></em></p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="fw-bold mb-3">⚙️ Update Profile</h5>
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">New Username</label>
                    <input type="text" name="new_username" class="form-control" placeholder="Leave empty if not changing" value="<?= $username ?>">
                </div>
                <hr>
                <div class="mb-3 mt-4">
                    <label class="form-label">Current Password</label>
                    <div class="input-group">
                        <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Required for password change">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="old_password"><i class="fas fa-eye"></i></button>
                    </div>
                    <small class="text-muted">Enter your current password to change your password.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Leave empty if not changing">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Leave empty if not changing">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary px-4">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const tgl = document.getElementById('modeToggle'), icon = tgl.querySelector('i');
    
    // Check local storage on page load and apply the theme to the body
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }

    tgl.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark-mode');
        const dark = document.documentElement.classList.contains('dark-mode');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        icon.classList.toggle('fa-moon', !dark);
        icon.classList.toggle('fa-sun', dark);
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = button.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
</script>
</body>
</html>