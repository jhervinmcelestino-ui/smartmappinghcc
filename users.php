<?php
/* ── Session guard ─────────────────────────────────────────── */
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.html');
    exit;
}

$loggedUser = htmlspecialchars($_SESSION['username']);
$loginTime  = $_SESSION['login_time'] ?? date('F j, Y - g:i A');

/* ── Pull users from DB ────────────────────────────────────── */
require 'db.php';
$users = $pdo->query("SELECT id, username, role, organization FROM users ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Smart Mapping – Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f0f2f6;
            --bg-sidebar: #ffffff;
            --bg-navbar: #0d6efd;
            --bg-card: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --text-invert: #ffffff;
            --border-color: #dee2e6;
            --hover-link: #e3edff;
        }
        .dark-mode {
            --bg-body: #1e2430;
            --bg-sidebar: #1b2230;
            --bg-navbar: #111827;
            --bg-card: #2c3344;
            --text-main: #f8f9fa;
            --text-muted: #a5b0c2;
            --text-invert: #f1f5f9;
            --border-color: #394151;
            --hover-link: #2c3a50;
        }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            font-family: "Segoe UI", sans-serif;
            padding-top: 5rem; /* Adjust for fixed navbar */
        }
        .navbar {
            background: var(--bg-navbar);
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .navbar-brand, .dark-toggle {
            color: var(--text-invert) !important;
        }
        .offcanvas {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            width: 260px;
        }
        .offcanvas-header {
            border-bottom: 1px solid var(--border-color);
        }
        .offcanvas .nav-link {
            color: var(--text-main);
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
            background: var(--hover-link);
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
            color: var(--text-muted);
            padding: 15px 18px 5px;
        }
        .dark-mode .offcanvas {
            background: #1b2230;
            color: var(--text-invert);
        }
        .dark-mode .offcanvas .nav-link {
            color: #d9e2f1;
        }
        .dark-mode .offcanvas .nav-link:hover {
            background: #324565;
            color: #fff;
        }
        .dark-mode .offcanvas .nav-link.active {
            background: #3a4f6c;
            border-left: 4px solid #0d6efd;
        }
        .dark-mode .section-title {
            color: #97a3b8;
            border-top: 1px solid #2f3847;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,.05);
        }
        .table {
            --bs-table-bg: var(--bg-card);
            --bs-table-color: var(--text-main);
            border: none;
            vertical-align: middle;
        }
        .table thead th {
            background: var(--hover-link);
            color: var(--text-main);
            font-weight: 600;
            font-size: 15px;
            padding: 14px 16px;
        }
        .table tbody tr:hover {
            background: var(--hover-link);
        }
        .dark-mode .table thead th {
            background: #324565;
            color: var(--text-invert);
        }
        .dark-mode .table tbody tr {
            background: #2c3344;
            color: var(--text-main);
        }
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #262c38;
        }
        .dark-mode .table-striped > tbody > tr:hover {
            background: #3a4f6c;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light px-3 fixed-top shadow-sm">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <a class="navbar-brand fw-bold text-white" href="#" style="color:#fff !important;">
        📍 Smart Mapping – Admin Dashboard
    </a>
    <button class="ms-auto dark-toggle btn btn-outline-light" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0">Admin Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="section-title">Main</li>
            <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
            <li class="section-title">Manage Events</li>
            <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
            <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
            <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
            <li class="section-title">System Tools</li>
            <li><a class="nav-link active" href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
            <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
            <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
            <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <li class="section-title">Session</li>
            <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>
</div>

<div class="container my-5 py-4">

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['message_type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">👥 User Accounts</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-user-plus me-1"></i>Add User
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Organization</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= htmlspecialchars($u['organization'] ?? 'N/A') ?></td>
                            <td class="text-center" style="white-space:nowrap">
                                <a href="add_edit_user.php?mode=reset&id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary me-1"
                                   onclick="return confirm('Reset password to \"123456\"?');">
                                    <i class="fas fa-key"></i> Reset Pass
                                </a>
                                <?php if ($u['username'] !== $loggedUser): ?>
                                    <a href="delete_user.php?id=<?= $u['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this user?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-danger" disabled>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="add_edit_user.php" method="POST">
            <input type="hidden" name="mode" value="add">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" id="userRole" class="form-select" required>
                        <option value="" selected disabled>Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="officer">Officer</option>
                    </select>
                </div>
                <div class="mb-3" id="organizationField" style="display:none;">
                    <label class="form-label">Organization</label>
                    <input type="text" name="organization" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('userRole');
        const organizationField = document.getElementById('organizationField');

        roleSelect.addEventListener('change', function() {
            if (this.value === 'officer') {
                organizationField.style.display = 'block';
                organizationField.querySelector('input').setAttribute('required', 'required');
            } else {
                organizationField.style.display = 'none';
                organizationField.querySelector('input').removeAttribute('required');
            }
        });

        // Toggle dark mode
        const modeToggle = document.getElementById('modeToggle');
        if (modeToggle) {
            modeToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('mode', 'dark');
                } else {
                    localStorage.setItem('mode', 'light');
                }
            });
            if (localStorage.getItem('mode') === 'dark') {
                document.body.classList.add('dark-mode');
            }
        }
    });
</script>
</body>
</html>