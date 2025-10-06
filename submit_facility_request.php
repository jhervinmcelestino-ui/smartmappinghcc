<?php
session_start();
require 'db.php';

$username = $_SESSION['username'] ?? '';
$org      = $_SESSION['organization'] ?? '';

$facility = trim($_POST['facility'] ?? '');
$purpose  = trim($_POST['purpose'] ?? '');
$date     = $_POST['date_requested'] ?? '';

if (!$username || !$facility || !$purpose || !$date) {
  header('Location: facilities_request.php?error=1'); exit;
}

$stmt = $pdo->prepare("INSERT INTO facility_requests (requester, organization, facility, purpose, date_requested) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$username, $org, $facility, $purpose, $date]);

header('Location: facilities_request.php?success=1');
exit;
