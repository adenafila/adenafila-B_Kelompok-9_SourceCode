<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login_panitia.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function requirePemilihLogin() {
    if (!isset($_SESSION['pemilih_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

function getUserData($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPemilihData($pdo, $pemilihId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$pemilihId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>