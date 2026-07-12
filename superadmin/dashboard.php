<?php
require_once '../includes/auth.php';
requireRole('superadmin');
require_once '../config/database.php';
require_once '../includes/functions.php';

$user = getUserData($pdo, $_SESSION['user_id']);

// Get active users (admin & superadmin)
$activeUsers = getActiveUsers($pdo);

// Get active voting sessions
$stmt = $pdo->query("
    SELECT vs.id, u.nama, vs.bilik_suara, vs.tps, vs.status
    FROM voting_sessions vs
    JOIN users u ON vs.id_user = u.id
    WHERE vs.status = 'sedang_diproses'
");
$activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total voters
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'pemilih'");
$totalVoters = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total votes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
$totalVotes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pageTitle = "Dashboard Superadmin";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Total Pemilih</h3>
            <p><?php echo $totalVoters; ?></p>
        </div>
        <div class="stat-card">
            <h3>Suara Masuk</h3>
            <p><?php echo $totalVotes; ?></p>
        </div>
        <div class="stat-card">
            <h3>Admin Aktif</h3>
            <p><?php echo count($activeUsers); ?></p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Admin & Superadmin Aktif</h2>
        </div>
        <table>
            <tr>
                <th>Nama</th>
                <th>Role</th>
                <th>Last Login</th>
            </tr>
            <?php foreach ($activeUsers as $activeUser): ?>
            <tr>
                <td><?php echo $activeUser['nama']; ?></td>
                <td><?php echo ucfirst($activeUser['role']); ?></td>
                <td><?php echo $activeUser['last_login']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pemilih Sedang di Bilik Suara</h2>
        </div>
        <table>
            <tr>
                <th>Nama</th>
                <th>Bilik Suara</th>
                <th>TPS</th>
                <th>Status</th>
            </tr>
            <?php foreach ($activeSessions as $session): ?>
            <tr>
                <td><?php echo $session['nama']; ?></td>
                <td><?php echo $session['bilik_suara']; ?></td>
                <td><?php echo $session['tps']; ?></td>
                <td><?php echo $session['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>