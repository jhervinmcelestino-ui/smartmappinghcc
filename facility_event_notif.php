<?php
// filepath: facility_event_notif.php
require 'db.php';

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM facility_requests WHERE request_date = ? AND status IN ('Approved','Pending')");
$stmt->execute([$today]);
$requests = $stmt->fetchAll();

foreach ($requests as $req) {
    $notifMsg = "Facility request for {$req['facility']} by {$req['requested_by']} is scheduled for today ($today).";
    $notifCheck = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE message = ?");
    $notifCheck->execute([$notifMsg]);
    
    if ($notifCheck->fetchColumn() == 0) {
        $redirectUrl = "redirect_to_request.php?id=" . $req['id'];
        $notifStmt = $pdo->prepare("INSERT INTO notifications (type, message, url) VALUES (?, ?, ?)");
        $notifStmt->execute(['facility', $notifMsg, $redirectUrl]);
    }
}
?>