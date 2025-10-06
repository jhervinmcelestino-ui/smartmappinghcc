<?php
session_start();
require 'db.php';

// Check if user is logged in and has a valid role
$role = $_SESSION['role'] ?? '';

// Unified authentication logic
if ($role === 'officer') {
    // If the role is 'officer', check for the officer-specific session variable.
    if (!isset($_SESSION['officer_id'])) {
        header('Location: officer_login.html');
        exit;
    }
} elseif ($role === 'admin') {
    // If the role is 'admin', check for the admin-specific session variable.
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
} else {
    // If no valid role is found, redirect to the general login page.
    header('Location: login.html');
    exit;
}

/**
 * Helper function to parse 12-hour time to 24-hour format (H:i:s) for database
 */
function parse_time($time_str) {
    if (empty($time_str)) {
        return null;
    }
    // PHP's date() and strtotime() can handle most time formats, including H:i from HTML type="time"
    return date("H:i:s", strtotime($time_str));
}


// PHP logic for handling event rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_event']) && $role === 'officer') {
    $eventId = $_POST['event_id'];
    
    // Read separate date and time strings
    $newDate = $_POST['new_date'];
    $newStartTimeRaw = $_POST['new_start_time'];
    $newEndTimeRaw = $_POST['new_end_time'];

    // Convert to 24-hour format for the database
    $newStartTime = parse_time($newStartTimeRaw);
    $newEndTime = parse_time($newEndTimeRaw);
    
    if (empty($eventId) || empty($newDate) || empty($newStartTime) || empty($newEndTime)) {
        $_SESSION['error_message'] = 'Please fill in all required date and time fields.';
        header('Location: events.php');
        exit;
    }
    
    if (strtotime($newStartTime) >= strtotime($newEndTime)) {
        $_SESSION['error_message'] = 'End time must be after start time.';
        header('Location: events.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT venue FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        $venue = $event['venue'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date = ? AND venue = ? AND status IN ('Approved', 'Pending') AND id <> ? AND (? < end_time AND ? > event_time)");
        $stmt->execute([$newDate, $venue, $eventId, $newEndTime, $newStartTime]); // Corrected time conflict check logic
        $conflictCount = $stmt->fetchColumn();

        if ($conflictCount > 0) {
            $_SESSION['error_message'] = 'Conflict found! The new date and venue are already booked and conflict with another event\'s time.';
            header('Location: events.php');
            exit;
        }

        try {
            $sql = "UPDATE events SET event_date = ?, event_time = ?, end_time = ?, status = 'Pending', rejection_reason = NULL WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newDate, $newStartTime, $newEndTime, $eventId]);

            header("Location: events.php?success=rescheduled");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
            header('Location: events.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Event not found.';
        header('Location: events.php');
        exit;
    }
}

// PHP logic for AJAX request to check for date and time conflicts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_conflict'])) {
    header('Content-Type: application/json');

    $eventDate = $_POST['event_date'] ?? '';
    $venue = $_POST['venue'] ?? '';
    
    // The time inputs are now H:i (24-hour format)
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $excludeEventId = $_POST['exclude_event_id'] ?? null;
    
    if (empty($eventDate) || empty($startTime) || empty($endTime)) {
        echo json_encode(['conflict' => false, 'suggested_dates' => []]);
        exit;
    }

    $query = "SELECT * FROM events WHERE event_date = ? AND venue = ? AND status IN ('Approved', 'Pending') AND (? < end_time AND ? > event_time)";
    $params = [$eventDate, $venue, $endTime, $startTime]; // Corrected time conflict check logic

    if ($excludeEventId) {
        $query .= " AND id <> ?";
        $params[] = $excludeEventId;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $conflicts = $stmt->fetchAll();

    $response = ['conflict' => !empty($conflicts), 'suggested_dates' => []];

    if (!empty($conflicts)) {
        $suggestedDates = [];
        $currentDate = new DateTime($eventDate);
        $count = 0;

        while ($count < 3) {
            $currentDate->modify('+1 day');
            $nextDate = $currentDate->format('Y-m-d');

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date = ? AND venue = ? AND status IN ('Approved', 'Pending')");
            $stmt->execute([$nextDate, $venue]);
            $isConflict = $stmt->fetchColumn();

            // Also check for time conflicts on the new date (using the original times)
            if ($isConflict == 0) {
                 // Double check no time conflict exists on this potential next date with the same times
                 $stmt_time_check = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date = ? AND venue = ? AND status IN ('Approved', 'Pending') AND (? < end_time AND ? > event_time)");
                 $stmt_time_check->execute([$nextDate, $venue, $endTime, $startTime]);
                 $timeConflictCount = $stmt_time_check->fetchColumn();
                 
                 if ($timeConflictCount == 0) {
                    $suggestedDates[] = $nextDate;
                    $count++;
                 }
            }
        }
        $response['suggested_dates'] = $suggestedDates;
    }

    echo json_encode($response);
    exit;
}

// PHP logic for handling form submission from officers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_event']) && $role === 'officer') {
    $title = htmlspecialchars($_POST['title']);
    $organization = htmlspecialchars($_POST['organization']);
    // Check if the "other" venue input is set and not empty, otherwise use the selected dropdown value
    $venue = htmlspecialchars($_POST['venue'] === 'Other' ? $_POST['other_venue'] : $_POST['venue']); 
    $eventType = htmlspecialchars($_POST['event_type']);

    // Read separate date and time strings
    $eventDate = $_POST['event_date'];
    $eventStartTimeRaw = $_POST['event_start_time'];
    $eventEndTimeRaw = $_POST['event_end_time'];

    // Convert to 24-hour format for the database
    $eventStartTime = parse_time($eventStartTimeRaw); // 24-hour time
    $eventEndTime = parse_time($eventEndTimeRaw); // 24-hour time
    
    $estimatedAttendees = htmlspecialchars($_POST['estimated_attendees']); 
    $deadlineDays = 7;
    $today = new DateTime(date('Y-m-d'));
    $eventDateTime = new DateTime($eventDate);
    $interval = $today->diff($eventDateTime);

    if (strtotime($eventStartTime) >= strtotime($eventEndTime)) {
        $_SESSION['add_event_error'] = 'End time must be after start time.';
        header('Location: events.php#addModal');
        exit;
    }

    if (!$interval->invert && $interval->days < $deadlineDays) {
        $sql = "INSERT INTO events (title, organization, venue, event_date, event_time, end_time, event_type, estimated_attendees, status, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Rejected', ?)";
        $stmt = $pdo->prepare($sql);
        $rejectionReason = "Automatically rejected: The request was submitted less than {$deadlineDays} days before the event date.";
        $stmt->execute([$title, $organization, $venue, $eventDate, $eventStartTime, $eventEndTime, $eventType, $estimatedAttendees, $rejectionReason]);
        $_SESSION['add_event_error'] = 'Event request automatically rejected. You must submit your request at least ' . $deadlineDays . ' days in advance.';
        header('Location: events.php#addModal');
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date = ? AND venue = ? AND status IN ('Approved', 'Pending') AND (? < end_time AND ? > event_time)");
    $stmt->execute([$eventDate, $venue, $eventEndTime, $eventStartTime]); // Corrected time conflict check logic
    $conflictCount = $stmt->fetchColumn();

    if ($conflictCount > 0) {
        $_SESSION['add_event_error'] = 'Conflict found! The selected date and venue are already booked and conflict with another event\'s time.';
        header('Location: events.php#addModal');
        exit;
    }

    if (empty($title) || empty($organization) || empty($venue) || empty($eventDate) || empty($eventStartTime) || empty($eventEndTime) || empty($estimatedAttendees)) {
        $_SESSION['add_event_error'] = 'Please fill in all required fields.';
        header('Location: events.php#addModal');
        exit;
    }

    try {
        $sql = "INSERT INTO events (title, organization, venue, event_date, event_time, end_time, event_type, estimated_attendees, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $organization, $venue, $eventDate, $eventStartTime, $eventEndTime, $eventType, $estimatedAttendees]); 
        header("Location: events.php?success=1");
        exit;
    } catch (PDOException $e) {
        $_SESSION['add_event_error'] = "Database error: " . $e->getMessage();
        header('Location: events.php#addModal');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events – Smart Mapping</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            padding-top: 5rem;
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
        .pagination {
            justify-content: end;
        }
        .form-control, .form-select {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .dark-mode .form-control, .dark-mode .form-select {
            background: #2c3344;
            border-color: #444;
            color: #fff;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light px-3 fixed-top shadow-sm">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <a class="navbar-brand fw-bold text-white" href="#" style="color:#fff !important;">
        📍 Smart Mapping –
        <?= ($role === 'officer') ? 'Officer Dashboard' : 'Admin Dashboard' ?>
    </a>
    <button class="ms-auto dark-toggle btn btn-outline-light" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0"><?= ($role === 'officer') ? 'Officer Menu' : 'Admin Menu' ?></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="section-title">Main</li>
            <?php if ($role === 'admin'): ?>
                <li><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
            <?php else: ?>
                <li><a class="nav-link" href="officer_dashboard.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
            <?php endif; ?>
            <li class="section-title">Manage Events</li>
            <li><a class="nav-link active" href="events.php"><i class="fas fa-calendar-alt"></i>Events</a></li>
            <?php if ($role === 'admin'): ?>
                <li><a class="nav-link" href="admin_events.php"><i class="fas fa-check-circle"></i>Approve Events</a></li>
                <li><a class="nav-link" href="event_allocations.php"><i class="fas fa-list-alt"></i>Event Allocations</a></li>
            <?php endif; ?>
            <li class="section-title">System Tools</li>
            <?php if ($role === 'admin'): ?>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="manage_materials.php"><i class="fas fa-folder-open"></i>Manage Materials</a></li>
                <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Requests</a></li>
            <?php else: ?>
                <li><a class="nav-link" href="upload_material.php"><i class="fas fa-upload"></i>Upload Material</a></li>
                <li><a class="nav-link" href="facilities_request.php"><i class="fas fa-building"></i>Facility Request</a></li>
            <?php endif; ?>
            <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <li class="section-title">Session</li>
            <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === '1'): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Event request submitted successfully! It is now Pending approval.
            </div>
        <?php elseif ($_GET['success'] === 'rescheduled'): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Event successfully rescheduled. It is now Pending re-approval.
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Event Management</h4>
        <div>
            <?php if ($role === 'officer'): ?>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            <?php endif; ?>
            <a href="generate_event_report.php" target="_blank" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Generate PDF Report
            </a>
        </div>
    </div>
    
    <form method="get" class="mb-3 row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-1">Status</label>
            <select name="status_filter" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Pending" <?= ($_GET['status_filter'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= ($_GET['status_filter'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($_GET['status_filter'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="Cancelled" <?= ($_GET['status_filter'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
        </div>

        <div class="col-md-3">
            <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Estimated Attendees</th>
                    <th>Venue</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Type</th>
                    <th>Status</th>
                    <?php if ($role === 'officer'): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $statusFilter = $_GET['status_filter'] ?? '';
                $startDate = $_GET['start_date'] ?? '';
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $perPage = 10;
                $offset = ($page - 1) * $perPage;

                $query = "SELECT COUNT(*) FROM events WHERE 1";
                $params = [];
                if ($role === 'officer') {
                    $query .= " AND organization = ?";
                    $params[] = $_SESSION['organization'];
                }
                if ($statusFilter) {
                    $query .= " AND status = ?";
                    $params[] = $statusFilter;
                }
                if ($startDate) {
                    $query .= " AND event_date >= ?";
                    $params[] = $startDate;
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $totalRows = $stmt->fetchColumn();
                $totalPages = ceil($totalRows / $perPage);

                $query = "SELECT * FROM events WHERE 1";
                $params = [];
                 if ($role === 'officer') {
                    $query .= " AND organization = ?";
                    $params[] = $_SESSION['organization'];
                }
                if ($statusFilter) {
                    $query .= " AND status = ?";
                    $params[] = $statusFilter;
                }
                if ($startDate) {
                    $query .= " AND event_date >= ?";
                    $params[] = $startDate;
                }

                $query .= " ORDER BY event_date DESC LIMIT $perPage OFFSET $offset";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);

                foreach ($stmt as $row):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['organization']) ?></td>
                        <td><?= htmlspecialchars($row['estimated_attendees'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['venue']) ?></td>
                        <td><?= htmlspecialchars($row['event_date']) ?></td>
                        <td><?= htmlspecialchars($row['event_time'] ? date('h:i A', strtotime($row['event_time'])) : 'N/A') ?></td>
                        <td>
                            <?php 
                            $endTime = $row['end_time'] ?? null; 
                            echo htmlspecialchars($endTime ? date('h:i A', strtotime($endTime)) : 'N/A');
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['event_type']) ?></td>
                        <td>
                            <?php
                            $status = $row['status'];
                            $badgeClass = match ($status) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                'Pending'  => 'warning',
                                'Cancelled' => 'secondary',
                                default    => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            <?php if ($status === 'Rejected' && !empty($row['rejection_reason'])): ?>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($row['rejection_reason']) ?>"></i>
                            <?php endif; ?>
                        </td>
                        <?php if ($role === 'officer'): ?>
                        <td>
                            <?php if ($status === 'Rejected' || $status === 'Cancelled'): ?>
                            <button class="btn btn-sm btn-info text-white reschedule-btn" data-bs-toggle="modal" data-bs-target="#rescheduleModal" data-id="<?= $row['id'] ?>" data-title="<?= htmlspecialchars($row['title']) ?>" data-org="<?= htmlspecialchars($row['organization']) ?>" data-venue="<?= htmlspecialchars($row['venue']) ?>">
                                <i class="fas fa-sync-alt"></i> Reschedule
                            </button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($totalRows === 0): ?>
                    <tr><td colspan="10" class="text-center text-muted">No events found.</td></tr> <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <nav>
        <ul class="pagination mt-3">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php if ($role === 'officer'): ?>
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($_SESSION['add_event_error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['add_event_error']) ?>
                    </div>
                    <?php unset($_SESSION['add_event_error']); ?>
                <?php endif; ?>

                <div class="mb-2">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Organization</label>
                    <input type="text" name="organization" class="form-control" value="<?= htmlspecialchars($_SESSION['organization'] ?? '') ?>" required>
                </div>
                
                <div class="mb-2">
                    <label for="add_venue_select">Venue</label>
                    <select name="venue" id="add_venue_select" class="form-select venue-select" required>
                        <option value="" disabled selected>Select a venue...</option>
                        <option value="Gymnasium">Gymnasium</option>
                        <option value="SHS AVR">SHS AVR</option>
                        <option value="College AVR">College AVR</option>
                        <option value="Board Room">Board Room</option>
                        <option value="JHS Quadrangle">JHS Quadrangle</option>
                        <option value="SHS Quadrangle">SHS Quadrangle</option>
                        <option value="GS Quadrangle">GS Quadrangle</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="add_other_venue_input" class="form-control mt-2 d-none" name="other_venue">
                </div>
                
                <div class="mb-2">
                    <label>Event Type</label>
                    <select name="event_type" class="form-control" required>
                        <option value="Seminar">Seminar</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Training">Training</option>
                        <option value="Conference">Conference</option>
                        <option value="Sports Event">Sports Event</option>
                        <option value="Cultural Show">Cultural Show</option>
                        <option value="Team Building">Team Building</option>
                    </select>
                </div>
                
                <div class="mb-2">
                    <label>Event Date</label>
                    <input type="date" id="add_event_date" name="event_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row">
                    <div class="col-6 mb-2">
                        <label>Start Time</label>
                        <input type="time" id="add_start_time" name="event_start_time" class="form-control" required step="300">
                    </div>
                    <div class="col-6 mb-2">
                        <label>End Time</label>
                        <input type="time" id="add_end_time" name="event_end_time" class="form-control" required step="300">
                    </div>
                </div>
                
                <div class="mb-2">
                    <label>Estimated Attendees</label>
                    <input type="number" name="estimated_attendees" class="form-control" min="1" required>
                </div>
                
                <div id="add-conflict-warning" class="alert alert-danger mt-3 d-none">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Conflict Found!</strong>
                    <p class="mb-0 mt-2">The selected date and venue are already booked.</p>
                    <p class="mb-0">Suggested available dates for this venue:</p>
                    <ul class="mb-0" id="add-suggested-dates-list"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="submit_event" class="btn btn-success">Save Event</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="event_id" id="reschedule_event_id">
            <input type="hidden" id="reschedule_event_venue_hidden">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Title</label>
                    <input type="text" id="reschedule_title" class="form-control" readonly>
                </div>
                <div class="mb-2">
                    <label>Organization</label>
                    <input type="text" id="reschedule_organization" class="form-control" readonly>
                </div>
                <div class="mb-2">
                    <label>Venue</label>
                    <input type="text" id="reschedule_venue" class="form-control" readonly>
                </div>
                <div class="mb-2">
                    <label>New Event Date</label>
                    <input type="date" id="reschedule_new_date" name="new_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row">
                    <div class="col-6 mb-2">
                        <label>New Start Time</label>
                        <input type="time" id="reschedule_start_time" name="new_start_time" class="form-control" required step="300">
                    </div>
                    <div class="col-6 mb-2">
                        <label>New End Time</label>
                        <input type="time" id="reschedule_end_time" name="new_end_time" class="form-control" required step="300">
                    </div>
                </div>
                
                <div id="reschedule-conflict-warning" class="alert alert-danger mt-3 d-none">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Conflict Found!</strong>
                    <p class="mb-0 mt-2">The selected new date and venue are already booked and conflict with another event's time.</p>
                    <p class="mb-0">Suggested available dates for this venue:</p>
                    <ul class="mb-0" id="reschedule-suggested-dates-list"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="reschedule_event" class="btn btn-success">Reschedule</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Toggle dark mode
        const modeToggle = document.getElementById('modeToggle');
        if (modeToggle) {
            modeToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                // Save user's preference to localStorage
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('mode', 'dark');
                } else {
                    localStorage.setItem('mode', 'light');
                }
            });
            // Load user's preference from localStorage
            if (localStorage.getItem('mode') === 'dark') {
                document.body.classList.add('dark-mode');
            }
        }
    
        // Show/hide other venue input
        const addVenueSelect = document.getElementById('add_venue_select');
        const addOtherVenueInput = document.getElementById('add_other_venue_input');
        if (addVenueSelect) {
            addVenueSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    addOtherVenueInput.classList.remove('d-none');
                    addOtherVenueInput.setAttribute('required', 'required');
                } else {
                    addOtherVenueInput.classList.add('d-none');
                    addOtherVenueInput.removeAttribute('required');
                }
            });
        }
        
        // Populate reschedule modal with event data
        const rescheduleModal = document.getElementById('rescheduleModal');
        if (rescheduleModal) {
            rescheduleModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const eventId = button.getAttribute('data-id');
                const eventTitle = button.getAttribute('data-title');
                const eventOrg = button.getAttribute('data-org');
                const eventVenue = button.getAttribute('data-venue');

                const modalTitle = rescheduleModal.querySelector('.modal-title');
                const eventIdInput = document.getElementById('reschedule_event_id');
                const titleInput = document.getElementById('reschedule_title');
                const orgInput = document.getElementById('reschedule_organization');
                const venueInput = document.getElementById('reschedule_venue');
                const venueHiddenInput = document.getElementById('reschedule_event_venue_hidden');

                modalTitle.textContent = `Reschedule: ${eventTitle}`;
                eventIdInput.value = eventId;
                titleInput.value = eventTitle;
                orgInput.value = eventOrg;
                venueInput.value = eventVenue;
                venueHiddenInput.value = eventVenue;
                
                // Reset inputs
                document.getElementById('reschedule_new_date').value = '';
                document.getElementById('reschedule_start_time').value = '';
                document.getElementById('reschedule_end_time').value = '';
                document.getElementById('reschedule-conflict-warning').classList.add('d-none');
            });
        }

        // AJAX conflict checking for Add Event form
        const addEventDateInput = document.getElementById('add_event_date');
        const addStartTimeInput = document.getElementById('add_start_time');
        const addEndTimeInput = document.getElementById('add_end_time');
        const addConflictWarning = document.getElementById('add-conflict-warning');
        const addSuggestedDatesList = document.getElementById('add-suggested-dates-list');

        [addEventDateInput, addStartTimeInput, addEndTimeInput, addVenueSelect].forEach(element => {
            if (element) {
                element.addEventListener('change', checkAddConflict);
            }
        });

        function checkAddConflict() {
            const eventDate = addEventDateInput.value;
            const startTime = addStartTimeInput.value;
            const endTime = addEndTimeInput.value;
            const venue = addVenueSelect.value;
            
            // Basic validation
            if (startTime && endTime && (startTime >= endTime)) {
                addConflictWarning.classList.remove('d-none');
                addConflictWarning.querySelector('p:nth-child(2)').textContent = 'End time must be after start time.';
                addSuggestedDatesList.innerHTML = '';
                return;
            }

            if (eventDate && startTime && endTime && venue && venue !== 'Other') {
                const formData = new FormData();
                formData.append('check_conflict', '1');
                formData.append('event_date', eventDate);
                formData.append('start_time', startTime);
                formData.append('end_time', endTime);
                formData.append('venue', venue);

                fetch('events.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.conflict) {
                        addConflictWarning.classList.remove('d-none');
                        addConflictWarning.querySelector('p:nth-child(2)').textContent = 'The selected date and venue are already booked.';
                        addSuggestedDatesList.innerHTML = '';
                        data.suggested_dates.forEach(sDate => {
                            const li = document.createElement('li');
                            li.textContent = sDate;
                            addSuggestedDatesList.appendChild(li);
                        });
                    } else {
                        addConflictWarning.classList.add('d-none');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                addConflictWarning.classList.add('d-none');
            }
        }

        // AJAX conflict checking for Reschedule form
        const rescheduleEventIdInput = document.getElementById('reschedule_event_id');
        const rescheduleNewDateInput = document.getElementById('reschedule_new_date');
        const rescheduleStartInput = document.getElementById('reschedule_start_time');
        const rescheduleEndInput = document.getElementById('reschedule_end_time');
        const rescheduleVenueHidden = document.getElementById('reschedule_event_venue_hidden');
        const rescheduleConflictWarning = document.getElementById('reschedule-conflict-warning');
        const rescheduleSuggestedDatesList = document.getElementById('reschedule-suggested-dates-list');
        
        [rescheduleNewDateInput, rescheduleStartInput, rescheduleEndInput].forEach(element => {
            if (element) {
                element.addEventListener('change', checkRescheduleConflict);
            }
        });

        function checkRescheduleConflict() {
            const eventId = rescheduleEventIdInput.value;
            const newDate = rescheduleNewDateInput.value;
            const startTime = rescheduleStartInput.value;
            const endTime = rescheduleEndInput.value;
            const venue = rescheduleVenueHidden.value;
            
            // Basic validation
            if (startTime && endTime && (startTime >= endTime)) {
                rescheduleConflictWarning.classList.remove('d-none');
                rescheduleConflictWarning.querySelector('p:nth-child(2)').textContent = 'End time must be after start time.';
                rescheduleSuggestedDatesList.innerHTML = '';
                return;
            }

            if (eventId && newDate && startTime && endTime && venue) {
                const formData = new FormData();
                formData.append('check_conflict', '1');
                formData.append('event_date', newDate);
                formData.append('start_time', startTime);
                formData.append('end_time', endTime);
                formData.append('venue', venue);
                formData.append('exclude_event_id', eventId);

                fetch('events.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.conflict) {
                        rescheduleConflictWarning.classList.remove('d-none');
                        rescheduleConflictWarning.querySelector('p:nth-child(2)').textContent = 'The selected new date and venue are already booked and conflict with another event\'s time.';
                        rescheduleSuggestedDatesList.innerHTML = '';
                        data.suggested_dates.forEach(sDate => {
                            const li = document.createElement('li');
                            li.textContent = sDate;
                            rescheduleSuggestedDatesList.appendChild(li);
                        });
                    } else {
                        rescheduleConflictWarning.classList.add('d-none');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                rescheduleConflictWarning.classList.add('d-none');
            }
        }

        // Show addModal if redirected from a failed submission
        const urlHash = window.location.hash;
        if (urlHash === '#addModal') {
            const addModal = new bootstrap.Modal(document.getElementById('addModal'));
            addModal.show();
        }
    });
</script>
</body>
</html>