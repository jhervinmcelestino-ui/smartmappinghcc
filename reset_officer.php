<?php
// reset_officer.php  - creates a fresh officer with known credentials
require 'db.php';

$username     = 'officer99';
$plainPass    = 'secret99';
$organization = 'Demo Org';

$hash = password_hash($plainPass, PASSWORD_DEFAULT);

// remove any duplicate
$pdo->prepare("DELETE FROM officers WHERE username = ?")->execute([$username]);

// insert the fresh account
$pdo->prepare("INSERT INTO officers (username, password_hash, organization)
               VALUES (?,?,?)")
    ->execute([$username, $hash, $organization]);

echo "<h3>Officer created!</h3>";
echo "Username: <strong>$username</strong><br>";
echo "Password: <strong>$plainPass</strong><br>";
echo "Hash stored: $hash";
