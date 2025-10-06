<?php
// filepath: add_request.php
session_start();
require 'db.php';

$role = $_SESSION['role'] ?? '';
if ($role !== 'officer') {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facility']) && isset($_POST['request_date'])) {
    $requested_by = $_SESSION['username'];
    $status = 'Pending';
    
    $facilities = $_POST['facility'];
    $request_dates = $_POST['request_date'];
    
    if (count($facilities) !== count($request_dates)) {
        header('Location: facilities_request.php?msg=' . urlencode('Error: Mismatched number of fields submitted.'));
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO facility_requests (facility, requested_by, request_date, status) VALUES (?, ?, ?, ?)");
        
        foreach ($facilities as $i => $facility) {
            $facility = trim($facilities[$i]);
            $request_date = trim($request_dates[$i]);
            if (empty($facility) || empty($request_date)) {
                continue;
            }
            $stmt->execute([$facility, $requested_by, $request_date, $status]);
        }
        
        $notifMsg = "New facility request(s) from Officer: $requested_by.";
        $notifStmt = $pdo->prepare("INSERT INTO notifications (type, message) VALUES (?, ?)");
        $notifStmt->execute(['facility', $notifMsg]);

        $pdo->commit();
        
        header('Location: facilities_request.php?msg=' . urlencode('Facility request(s) added successfully!'));
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to add facility request: " . $e->getMessage());
        header('Location: facilities_request.php?msg=' . urlencode('Failed to add facility request(s). Please try again.'));
        exit;
    }
} else {
    header('Location: facilities_request.php?msg=' . urlencode('Invalid request method or missing data.'));
    exit;
}
?>