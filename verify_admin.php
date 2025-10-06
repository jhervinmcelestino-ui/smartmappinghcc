<?php
require 'db.php';          // ← uses smartmapping_db

$username = 'admin1';      // put the admin username you expect
$plain    = 'admin123';    // put the password you type in the form

$stmt = $pdo->prepare("SELECT username, password_hash FROM admins WHERE username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo "❌  USER NOT FOUND in admins table.";
  exit;
}

echo "<pre>";
echo "Username : {$row['username']}\n";
echo "Hash     : {$row['password_hash']}\n";
echo "password_verify result: ";
var_dump(password_verify($plain, $row['password_hash']));
echo "</pre>";
