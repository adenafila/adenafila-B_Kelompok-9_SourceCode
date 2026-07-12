<?php
session_start();

// Log aktivitas sebelum logout
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    logActivity($pdo, $_SESSION['user_id'], "Logout");
    $redirect = "login_panitia.php";
} elseif (isset($_SESSION['pemilih_id'])) {
    $redirect = "index.php";
} else {
    $redirect = "index.php";
}

// Hancurkan semua session
session_destroy();

// Redirect ke halaman yang sesuai
header("Location: $redirect");
exit();
?>