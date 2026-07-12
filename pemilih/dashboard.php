<?php
require_once '../includes/auth.php';
requirePemilihLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$user = getPemilihData($pdo, $_SESSION['pemilih_id']);

// Check if user has voted
if ($user['has_voted']) {
    $voted = true;
} else {
    $voted = false;
}

// Check if user has active voting session
$stmt = $pdo->prepare("
    SELECT * FROM voting_sessions 
    WHERE id_user = ? AND status = 'sedang_diproses'
");
$stmt->execute([$user['id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

$hasActiveSession = !empty($session);

$pageTitle = "Dashboard Pemilih";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <h2>Selamat datang, <?php echo $user['nama']; ?></h2>
    <p>NIM: <?php echo $user['nim']; ?></p>
    <p>Jurusan: <?php echo getJurusanName($pdo, $user['kode_jurusan']); ?></p>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Terima kasih! Suara Anda telah berhasil disimpan.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['already_voted'])): ?>
        <div class="alert alert-warning">
            Anda sudah melakukan voting. Setiap pemilih hanya dapat voting sekali.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['no_session'])): ?>
        <div class="alert alert-warning">
            Anda belum terdaftar di bilik suara. Silakan hubungi panitia di meja registrasi.
        </div>
    <?php endif; ?>
    
    <?php if ($voted): ?>
        <div class="status-card">
            <h3>Status Voting</h3>
            <div class="status-voted">
                <span class="icon">✓</span>
                <p>Anda telah melakukan voting</p>
            </div>
        </div>
    <?php else: ?>
        <?php if ($hasActiveSession): ?>
            <div class="status-card">
                <h3>Status Voting</h3>
                <div class="status-ready">
                    <span class="icon">→</span>
                    <p>Anda siap untuk voting</p>
                </div>
                <p>Bilik Suara: <?php echo $session['bilik_suara']; ?> | TPS: <?php echo $session['tps']; ?></p>
                <a href="voting.php" class="btn btn-primary">Mulai Voting</a>
            </div>
        <?php else: ?>
            <div class="status-card">
                <h3>Status Voting</h3>
                <div class="status-pending">
                    <span class="icon">⏱</span>
                    <p>Menunggu pendaftaran di bilik suara</p>
                </div>
                <p>Silakan hubungi panitia di meja registrasi</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>