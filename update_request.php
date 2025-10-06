<?php
// filepath: update_request.php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $facility = $_POST['facility'] ?? null;
    $requested_by = $_POST['requested_by'] ?? null;
    $request_date = $_POST['request_date'] ?? null;
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE facility_requests SET facility=?, requested_by=?, request_date=?, status=? WHERE id=?");
    $stmt->execute([$facility, $requested_by, $request_date, $status, $id]);

    header('Location: facilities_request.php?msg=' . urlencode('Request updated successfully!'));
    exit;
} else {
    header('Location: facilities_request.php?msg=' . urlencode('Invalid request.'));
    exit;
}
?>