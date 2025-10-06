<?php
require 'db.php';

$username = 'admin2';        // change to a new username
$password = 'admin123';      // known password
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

echo "✅ Admin created.<br>";
echo "Username: $username<br>";
echo "Password: $password<br>";
?>
