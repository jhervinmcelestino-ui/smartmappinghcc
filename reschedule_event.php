<?php
session_start();
require 'db.php';

// Check if the user is an officer before processing the request.
// This is a basic security measure.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header('Location: login.html');
    exit;
}

// Get and sanitize the POST data.
$id = filter_var($_POST['id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$venue = htmlspecialchars($_POST['venue'] ?? '');
$date = htmlspecialchars($_POST['date'] ?? '');
$time = htmlspecialchars($_POST['time'] ?? ''); // Assuming 'time' is also passed for rescheduling

// Check for all required fields.
if ($id && $venue && $date && $time) {
    try {
        // Step 1: Check for existing conflicts.
        // Look for any 'Approved' or 'Pending' events at the same venue and on the same date.
        // Exclude the event being rescheduled from the check.
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE venue = ? AND event_date = ? AND status IN ('Approved', 'Pending') AND event_id <> ?");
        $stmt->execute([$venue, $date, $id]);
        $conflictCount = $stmt->fetchColumn();

        if ($conflictCount > 0) {
            // Conflict found, redirect with an error message.
            header('Location: events.php?error=conflict');
            exit;
        }

        // Step 2: If no conflicts, proceed with the update.
        // Update the event with the new date, time, and reset its status to 'Pending' for re-approval.
        $stmt = $pdo->prepare("UPDATE events SET venue = ?, event_date = ?, event_time = ?, status = 'Pending' WHERE event_id = ?");
        $stmt->execute([$venue, $date, $time, $id]);

        // Redirect on success.
        header('Location: events.php?status=rescheduled');
        exit;
    } catch (PDOException $e) {
        // Handle database errors.
        error_log("Database error: " . $e->getMessage());
        header('Location: events.php?error=db_error');
        exit;
    }
} else {
    // Redirect if required data is missing.
    header('Location: events.php?error=missing');
    exit;
}
?>