<?php
session_start();
require 'db.php';

/* ----- Only officer and admin can access ----- */
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['officer', 'admin'])) {
  header('Location: events.php?error=unauthorized');
  exit;
}

/* ----- collect and validate data ----- */
$title = trim($_POST['title'] ?? '');
$org   = trim($_POST['organization'] ?? '');
$venue = trim($_POST['venue'] ?? '');
$date  = $_POST['event_date'] ?? ''; // ✅ corrected name from 'date' to 'event_date'

if (!$title || !$org || !$venue || !$date) {
  header('Location: events.php?error=empty');
  exit;
}

/* ----- check if event is at least 3 days from now ----- */
if (strtotime($date) < strtotime('+3 days')) {
  header('Location: events.php?error=deadline');
  exit;
}

/* ----- conflict: same date already exists ----- */
$stmt = $pdo->prepare("SELECT id FROM events WHERE event_date = ?");
$stmt->execute([$date]);
if ($stmt->fetch()) {
  header('Location: events.php?error=conflict');
  exit;
}

/* ----- insert new event ----- */
$stmt = $pdo->prepare(
  "INSERT INTO events (title, organization, venue, event_date)
   VALUES (?, ?, ?, ?)"
);
$stmt->execute([$title, $org, $venue, $date]);

header('Location: events.php?status=added');
exit;
