<?php
session_start();
require 'db.php';

// Ensure form was submitted
if (!isset($_POST['submit_event'])) {
  header('Location: events.php');
  exit;
}

// Get form inputs
$title       = trim($_POST['title'] ?? '');
$venue       = trim($_POST['venue'] ?? '');
$event_type  = trim($_POST['event_type'] ?? '');
$event_date  = $_POST['event_date'] ?? '';
$user_id     = $_SESSION['user_id'] ?? null;
$role        = $_SESSION['role'] ?? '';

// Validate required inputs
if (!$title || !$venue || !$event_type || !$event_date || !$user_id) {
  $_SESSION['error'] = "All fields are required.";
  header('Location: events.php');
  exit;
}

// Calculate if event is within 2 days from now
$today       = new DateTime();
$eventDay    = new DateTime($event_date);
$interval    = $today->diff($eventDay)->days;
$status      = ($eventDay < $today || $interval < 2) ? 'Rejected' : 'Pending';

// Insert event
$stmt = $pdo->prepare("INSERT INTO events (title, venue, event_type, event_date, status, user_id)
                       VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$title, $venue, $event_type, $event_date, $status, $user_id]);
$notif = $pdo->prepare("INSERT INTO notifications (message, type, created_at) VALUES (?, 'event', NOW())");
$notif->execute(["New event submitted by $org_name: '$title' scheduled on $event_date."]);

$_SESSION['success'] = ($status === 'Rejected')
    ? "Event submitted but automatically rejected due to short notice (less than 2 days)."
    : "Event submitted successfully and is pending approval.";

header('Location: events.php');
exit;
