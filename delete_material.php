<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $file = $_POST['file'] ?? '';

    if ($id && $file) {
        $filePath = __DIR__ . "/uploads/" . basename($file);
        if (file_exists($filePath)) {
            unlink($filePath); // delete the file
        }

        $stmt = $pdo->prepare("DELETE FROM event_materials WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: manage_materials.php?deleted=1");
        exit;
    }
}
header("Location: manage_materials.php?error=1");
exit;
