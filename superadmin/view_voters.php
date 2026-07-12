<?php
require_once '../includes/auth.php';
requireRole('superadmin');
require_once '../config/database.php';
require_once '../includes/functions.php';

$user = getUserData($pdo, $_SESSION['user_id']);

// Get all voters with their voting status
$stmt = $pdo->query("
    SELECT u.id, u.nim, u.nama, j.nama_jurusan, u.has_voted,
           vs.bilik_suara, vs.tps, vs.status as session_status
    FROM users u
    JOIN jurusan j ON u.kode_jurusan = j.kode_jurusan
    LEFT JOIN voting_sessions vs ON u.id = vs.id_user AND vs.status = 'sedang_diproses'
    WHERE u.role = 'pemilih'
    ORDER BY u.nama
");
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Daftar Pemilih";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Daftar Pemilih</h2>
        </div>
        <table>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Jurusan</th>
                <th>Status Voting</th>
                <th>Bilik Suara</th>
                <th>TPS</th>
                <th>Status Sesi</th>
            </tr>
            <?php foreach ($voters as $voter): ?>
            <tr>
                <td><?php echo $voter['nim']; ?></td>
                <td><?php echo $voter['nama']; ?></td>
                <td><?php echo $voter['nama_jurusan']; ?></td>
                <td>
                    <?php if ($voter['has_voted']): ?>
                        <span class="status-voted">Sudah Voting</span>
                    <?php else: ?>
                        <span class="status-pending">Belum Voting</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $voter['bilik_suara'] ?: '-'; ?></td>
                <td><?php echo $voter['tps'] ?: '-'; ?></td>
                <td>
                    <?php if ($voter['session_status'] === 'sedang_diproses'): ?>
                        <span class="status-ready">Sedang di Bilik</span>
                    <?php elseif ($voter['session_status'] === 'selesai'): ?>
                        <span class="status-voted">Selesai</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>