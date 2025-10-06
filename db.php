<?php
// db.php - Shared PDO connection for Smart Mapping system

$host     = 'localhost';
$dbname   = 'smartmapping_db';  // Make sure this is where ALL tables exist: users, officers, events, etc.
$db_user  = 'root';
$db_pass  = '';  // Blank if using XAMPP default

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $db_user,
    $db_pass,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  die('Database connection failed: ' . $e->getMessage());
}
?>
