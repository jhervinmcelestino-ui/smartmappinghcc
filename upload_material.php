<?php
session_start();
require 'db.php';

/* ── Determine role & dash link ── */
$role = $_SESSION['role'] ?? '';
$dashPage = ($role === 'officer') ? 'officer_dashboard.php' : (($role === 'admin') ? 'admin_dashboard.php' : 'student_dashboard.php');

// Security check: Only officers and admins can upload materials
// Use the correct session variable for officers
if ($role === 'officer') {
    if (!isset($_SESSION['officer_id'])) {
        header('Location: officer_login.html');
        exit;
    }
} elseif ($role === 'admin') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
} else {
    // If not admin or officer, redirect to a generic login
    header('Location: login.html');
    exit;
}

/* ── Event list for dropdown ── */
$events = $pdo->query("SELECT id,title FROM events ORDER BY event_date DESC")->fetchAll();

/* ── Handle upload ── */
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'] ?? null;
    $location = $_POST['location'] ?? null;
    $file = $_FILES['material'] ?? null;
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if ($file['error'] === UPLOAD_ERR_OK && $eventId && $location) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG or PDF allowed.";
        } else {
            $name = uniqid() . '.' . $ext;
            $target = __DIR__ . '/uploads/' . $name;
            
            // Check if 'uploads' directory exists, if not, create it
            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $stmt = $pdo->prepare("INSERT INTO event_materials (event_id, file_path, location) VALUES (?, ?, ?)");
                $stmt->execute([$eventId, $name, $location]);
                
                $success = "Uploaded successfully! The material is now linked to the event.";
                // Instead of a JS alert and redirect, let the PHP code handle it
                // We'll show the message on the same page.
            } else {
                $error = "Upload failed.";
            }
        }
    } else {
        $error = "No file selected or missing event/location.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Smart Mapping – Upload Material</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --bg-body:#f4f6f9; --bg-navbar:#0d6efd; --bg-sidebar:#ffffff;
            --bg-card:#ffffff; --text-main:#212529; --text-muted:#6c757d;
            --text-invert:#ffffff; --border-color:#dee2e6; --hover-link:#eaf2ff;
        }
        .dark-mode {
            --bg-body:#1e2430; --bg-navbar:#111827; --bg-sidebar:#1b2230;
            --bg-card:#23272f; --text-main:#f8f9fa; --text-muted:#a5b0c2;
            --text-invert:#f1f5f9; --border-color:#394151; --hover-link:#2c3a50;
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
            background-color: #1b2230 !important;
            color: #f1f5f9 !important;
        }
        .dark-mode .offcanvas .nav-link {
            color: #d9e2f1 !important;
        }
        .dark-mode .offcanvas .nav-link:hover {
            background-color: #324565 !important;
            color: white !important;
        }
        .dark-mode .offcanvas .nav-link.active {
            background-color: #3a4f6c !important;
            border-left: 4px solid #0d6efd !important;
            font-weight: 600;
        }
        .dark-mode .section-title {
            color: #97a3b8 !important;
            border-top: 1px solid #2f3847;
        }

        .card { background: var(--bg-card); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .dark-mode .card {
            background: var(--bg-card, #23272f) !important;
            color: var(--text-main, #f8f9fa) !important;
        }
        .form-label { font-weight: 600; }
        .text-muted { color: var(--text-muted) !important; }
        .dark-mode .text-muted { color: #bcc8d6 !important; }
        .dark-mode .form-control, .dark-mode .form-select {
            background-color: #23272f !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .dark-mode .form-label,
        .dark-mode .fw-bold,
        .dark-mode h5 {
            color: #fff !important;
        }
        .dark-mode .alert {
            background-color: #23272f !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .dark-mode .btn-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: #fff !important;
        }
        .dark-mode .btn-link {
            color: #8ecaff !important;
        }
    </style>
</head>
<body>

<nav class="navbar px-3 fixed-top">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
    <span class="navbar-brand fw-bold">📎 Upload Material</span>
    <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0"><?= ucfirst($role) ?> Menu</h5>
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
                <li><a class="nav-link active" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
                <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
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
                <li><a class="nav-link active" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
                <li class="section-title">Session</li>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            <?php elseif ($role === 'student'): ?>
                <li class="section-title">Main</li>
                <li><a class="nav-link" href="student_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
                <li class="section-title">Student Panel</li>
                <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
                <li class="section-title">Session</li>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="container pt-5 mt-4">
    <div class="col-lg-7 mx-auto">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="mb-3 fw-bold">Upload Event Material</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select Event</label>
                    <select name="event_id" class="form-select" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Campus Location (e.g. Library, Gym)</label>
                    <input type="text" name="location" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Poster or Material</label>
                    <input type="file" name="material" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required />
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <a href="manage_materials.php" class="btn btn-link">→ Manage Materials</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const tgl=document.getElementById('modeToggle'),icon=tgl.querySelector('i');
    if(localStorage.getItem('theme')==='dark'){document.body.classList.add('dark-mode');icon.classList.replace('fa-moon','fa-sun');}
    tgl.addEventListener('click',()=>{
        document.body.classList.toggle('dark-mode');
        const dark=document.body.classList.contains('dark-mode');
        localStorage.setItem('theme',dark?'dark':'light');
        icon.classList.toggle('fa-moon',!dark);
        icon.classList.toggle('fa-sun',dark);
    });
</script>
</body>
</html>