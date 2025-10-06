<?php
/*  add_edit_event.php
 *  Handles both ADD and EDIT actions.
 *  Auto‑rejects an event if its date is less than 3 days away (or in the past).
 *-------------------------------------------------------------*/
session_start();
require '.../includes/db.php';

$mode  = $_POST['mode'] ?? '';            // 'add' | 'edit'
$title = trim($_POST['title'] ?? '');
$org   = trim($_POST['organization'] ?? '');
$venue = trim($_POST['venue'] ?? '');
$date  = $_POST['date'] ?? '';
$id    = $_POST['id']   ?? null;

/* ── Basic form validation ───────────────────────────────── */
if (!$title || !$org || !$venue || !$date) {
  header('Location: events.php?error=empty');
  exit;
}

/* ── Auto‑reject if event is < 3 days away ───────────────── */
try {
  $today     = new DateTime();          // today 00:00
  $eventDate = new DateTime($date);
  $diff      = (int)$today->diff($eventDate)->format('%r%a');  // signed days

  if ($diff < 3) {                      // includes past dates
    header('Location: events.php?error=deadline');
    exit;
  }
} catch (Exception $e) {
  header('Location: events.php?error=date');
  exit;
}

/* ── Same‑day conflict check (exclude itself when editing) ─ */
if ($mode === 'edit') {
  
  $stmt = $pdo->prepare("SELECT id FROM events WHERE event_date = ? AND id <> ?");
  $stmt->execute([$date, $id]);
} else {
  $stmt = $pdo->prepare("SELECT id FROM events WHERE event_date = ?");
  $stmt->execute([$date]);
}
if ($stmt->fetch()) {
  header('Location: events.php?error=conflict');
  exit;
}

/* ── Insert or update in DB ──────────────────────────────── */
if ($mode === 'add') {
  $stmt = $pdo->prepare("
    INSERT INTO events (title, organization, venue, event_date)
    VALUES (?,?,?,?)
  ");
  $stmt->execute([$title, $org, $venue, $date]);
  header('Location: events.php?status=added');
  exit;
}

if ($mode === 'edit') {
  $stmt = $pdo->prepare("
    UPDATE events
    SET title = ?, organization = ?, venue = ?, event_date = ?
    WHERE id = ?
  ");
  $stmt->execute([$title, $org, $venue, $date, $id]);
  header('Location: events.php?status=updated');
  exit;
}

/* ── Fallback ───────────────────────────────────────────── */
header('Location: events.php');
exit;
