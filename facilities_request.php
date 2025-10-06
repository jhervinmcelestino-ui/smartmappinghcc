<?php
session_start();
require 'db.php';

$role = $_SESSION['role'] ?? '';
$isOfficer = $role === 'officer';
$isAdmin = $role === 'admin';

if (!$isOfficer && !$isAdmin) {
    header('Location: login.html');
    exit;
}

if ($isOfficer) {
    $officerName = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT * FROM facility_requests WHERE requested_by = ? ORDER BY request_date DESC");
    $stmt->execute([$officerName]);
    $requests = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM facility_requests ORDER BY request_date DESC");
    $requests = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Facilities Request</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --bg-body: #f0f2f6; --bg-sidebar: #ffffff; --bg-navbar: #0d6efd; --bg-card: #ffffff; --text-main: #212529; --text-muted: #6c757d; --text-invert: #ffffff; --border-color: #dee2e6; --hover-link: #e3edff; }
        body { background: var(--bg-body); color: var(--text-main); font-family: "Segoe UI", sans-serif; padding-top: 56px; }
        .navbar { background: #0d6efd !important; color: var(--text-invert); }
        .navbar-brand, .dark-toggle { color: var(--text-invert) !important; }
        .dark-toggle { background: transparent; border: none; font-size: 20px; }
        .offcanvas { background: var(--bg-sidebar); border-right: 1px solid var(--border-color); width: 260px; }
        .offcanvas-header { border-bottom: 1px solid var(--border-color); }
        .offcanvas .nav-link { color: var(--text-main); display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-radius: 10px; transition: 0.2s; }
        .offcanvas .nav-link i { width: 20px; text-align: center; color: #0d6efd; }
        .offcanvas .nav-link:hover { background: var(--hover-link); transform: translateX(5px); }
        .offcanvas .nav-link.active { background: #dceeff; font-weight: 600; border-left: 4px solid #0d6efd; }
        .section-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: var(--text-muted); padding: 15px 18px 5px; }
        .dark-mode { background-color: #121212 !important; color: #fff !important; }
        .dark-mode .navbar { background-color: #181b20 !important; color: #fff !important; }
        .dark-mode .offcanvas, .dark-mode .offcanvas-body { background-color: #1b2230 !important; color: #f1f5f9 !important; }
        .dark-mode .offcanvas .nav-link { color: #d9e2f1 !important; }
        .dark-mode .offcanvas .nav-link:hover { background-color: #324565 !important; color: white !important; }
        .dark-mode .offcanvas .nav-link.active { background-color: #3a4f6c !important; border-left: 4px solid #0d6efd !important; font-weight: 600; }
        .dark-mode .section-title { color: #97a3b8 !important; border-top: 1px solid #2f3847; }
        .dark-mode .card { background: #23272f !important; color: #fff !important; }
        .dark-mode .table, .dark-mode .table-striped, .dark-mode .table thead, .dark-mode .table tbody, .dark-mode .table th, .dark-mode .table td { background-color: #23272f !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .table thead th, .dark-mode .table-primary th { background-color: #1a1d22 !important; color: #fff !important; border-color: #444 !important; }
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) { background-color: #23272f !important; }
        .dark-mode .table-striped > tbody > tr:nth-of-type(even) { background-color: #181b20 !important; }
        .dark-mode .btn, .dark-mode .btn-outline-secondary, .dark-mode .btn-primary, .dark-mode .btn-danger { color: #fff !important; border-color: #444 !important; background-color: #23272f !important; }
        .dark-mode .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; }
        .dark-mode .btn-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; }
        .dark-mode .btn-outline-primary { color: #8ecaff !important; border-color: #3a7bd5 !important; background-color: #23272f !important; }
        .dark-mode .btn-outline-primary:hover, .dark-mode .btn-outline-primary:focus { background-color: #0d6efd !important; color: #fff !important; border-color: #0d6efd !important; }
        .dark-mode .btn-outline-danger { color: #ff8a8a !important; border-color: #e74c3c !important; background-color: #23272f !important; }
        .dark-mode .btn-outline-danger:hover, .dark-mode .btn-outline-danger:focus { background-color: #dc3545 !important; color: #fff !important; border-color: #dc3545 !important; }
        .dark-mode .btn-success { background-color: #198754 !important; border-color: #198754 !important; color: #fff !important; }
        .dark-mode .btn-success:hover, .dark-mode .btn-success:focus { background-color: #157347 !important; border-color: #157347 !important; color: #fff !important; }
        .dark-mode .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; color: #fff !important; }
        .dark-mode .btn-primary:hover, .dark-mode .btn-primary:focus { background-color: #0b5ed7 !important; border-color: #0b5ed7 !important; color: #fff !important; }
        .dark-mode .btn:active, .dark-mode .btn:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25) !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-light px-3 fixed-top" style="background: #0d6efd; color: #fff;">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand fw-bold text-white" style="color:#fff !important;">
        🏢 Facilities Request
    </span>
    <button class="ms-auto dark-toggle btn btn-outline-light" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0"><?= $isAdmin ? 'Admin Menu' : 'Officer Menu' ?></h5>
        <button class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="section-title">Main</li>
            <?php if ($isAdmin): ?>
                <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
            <?php else: ?>
                <li><a class="nav-link" href="officer_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
            <?php endif; ?>
            <li class="section-title">Manage Events</li>
            <li><a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
            <?php if ($isAdmin): ?>
                <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
                <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
            <?php endif; ?>
            <li class="section-title">System Tools</li>
            <?php if ($isAdmin): ?>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link active" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
            <?php else: ?>
                <li><a class="nav-link active" href="facilities_request.php"><i class="fas fa-building"></i>Facility Request</a></li>
            <?php endif; ?>
            <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <li class="section-title">Session</li>
            <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📋 List of Facility Requests</h4>
        <?php if ($isOfficer): ?>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Facility Request
        </button>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Facility</th>
                            <th>Requested By</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['facility']) ?></td>
                                <td><?= htmlspecialchars($req['requested_by']) ?></td>
                                <td><?= htmlspecialchars($req['request_date']) ?></td>
                                <td>
                                    <?php if ($req['status'] === 'Approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($req['status'] === 'Denied'): ?>
                                        <span class="badge bg-danger">Denied</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isAdmin): ?>
                                        <button class="btn btn-sm btn-outline-primary me-1 editBtn"
                                                data-id="<?= $req['id'] ?>"
                                                data-facility="<?= htmlspecialchars($req['facility'], ENT_QUOTES) ?>"
                                                data-requested_by="<?= htmlspecialchars($req['requested_by'], ENT_QUOTES) ?>"
                                                data-date="<?= $req['request_date'] ?>"
                                                data-status="<?= $req['status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="delete_request.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this request?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php elseif ($isOfficer && $req['requested_by'] == $_SESSION['username']): ?>
                                        <a href="delete_request.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this request?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$requests): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No requests found.<br>
                                    <?php if ($isOfficer): ?>
                                    <button class="btn btn-success btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#addModal">
                                        <i class="fas fa-plus me-1"></i> Add Facility Request
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($isOfficer): ?>
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="add_request.php" method="POST" class="modal-content" id="multiRequestForm">
            <div class="modal-header">
                <h5 class="modal-title">Add Facility Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="requestFields">
                    <div class="request-group mb-3 border-bottom pb-3">
                        <div class="mb-2"><label class="form-label">Facility</label>
                            <input type="text" class="form-control" name="facility[]" required>
                        </div>
                        <div class="mb-2"><label class="form-label">Request Date</label>
                            <input type="date" class="form-control" name="request_date[]" required>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm removeBtn mt-2 d-none"><i class="fas fa-minus"></i> Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addMoreBtn" style="margin-bottom:10px;">
                    <i class="fas fa-plus"></i> Add Another Request
                </button>
                <input type="hidden" name="requested_by" value="<?= htmlspecialchars($_SESSION['username']) ?>">
                <input type="hidden" name="status" value="Pending">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Submit All</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('addMoreBtn').addEventListener('click', function() {
    const group = document.createElement('div');
    group.className = 'request-group mb-3 border-bottom pb-3';
    group.innerHTML = `
        <div class="mb-2"><label class="form-label">Facility</label>
            <input type="text" class="form-control" name="facility[]" required>
        </div>
        <div class="mb-2"><label class="form-label">Request Date</label>
            <input type="date" class="form-control" name="request_date[]" required>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm removeBtn mt-2"><i class="fas fa-minus"></i> Remove</button>
    `;
    document.getElementById('requestFields').appendChild(group);
    group.querySelector('.removeBtn').addEventListener('click', function() {
        group.remove();
    });
});
</script>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="update_request.php" method="POST" class="modal-content">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Facility Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Facility</label>
                    <input type="text" class="form-control" name="facility" id="edit_facility" required>
                </div>
                <div class="mb-3"><label class="form-label">Requested By</label>
                    <input type="text" class="form-control" name="requested_by" id="edit_requested_by" readonly required>
                </div>
                <div class="mb-3"><label class="form-label">Request Date</label>
                    <input type="date" class="form-control" name="request_date" id="edit_date" required>
                </div>
                <div class="mb-3"><label class="form-label">Status</label>
                    <select class="form-select" name="status" id="edit_status">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Denied">Denied</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($isAdmin): ?>
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_facility').value = btn.dataset.facility;
        document.getElementById('edit_requested_by').value = btn.dataset.requested_by;
        document.getElementById('edit_date').value = btn.dataset.date;
        document.getElementById('edit_status').value = btn.dataset.status;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('modeToggle').querySelector('i');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
    document.getElementById('modeToggle').addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const icon = document.getElementById('modeToggle').querySelector('i');
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
        if(document.body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

    // New logic to handle redirection from notifications
    const urlParams = new URLSearchParams(window.location.search);
    const requestId = urlParams.get('show_edit');

    if (requestId) {
        const editButton = document.querySelector(`.editBtn[data-id="${requestId}"]`);
        if (editButton) {
            editButton.click();
        }
    }
});
</script>
</body>
</html>