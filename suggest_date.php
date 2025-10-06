<?php
require 'db.php';

$venue = $_POST['venue'] ?? '';
$start = $_POST['start'] ?? '';

header('Content-Type: application/json');

if (!$venue || !$start) {
    echo json_encode(['suggested' => []]);
    exit;
}

$suggested = [];
$startDate = new DateTime($start);
$interval = new DateInterval('P1D');

for ($i = 0; $i < 30; $i++) {
    $date = $startDate->format('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE venue = ? AND event_date = ?");
    $stmt->execute([$venue, $date]);
    $conflict = $stmt->fetchColumn();

    if ($conflict == 0) {
        $suggested[] = $date;
    }

    $startDate->add($interval);
}

echo json_encode(['suggested' => $suggested]);
