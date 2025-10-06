<?php
session_start();

// Kunin ang role bago i‑destroy ang session
$role = $_SESSION['role'] ?? null;

// Linisin ang session
session_unset();
session_destroy();

/* ---------- tamang landing page per role ---------- */
switch ($role) {
  case 'admin':
    header('Location: admin_login.html');
    break;
  case 'officer':
    header('Location: officer_login.html');
    break;
  case 'student':
  default:
    header('Location: login.html');   // <-- ito ang talagang meron ka
    break;
}
exit;
