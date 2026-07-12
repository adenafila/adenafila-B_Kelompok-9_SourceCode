<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../config/database.php';
require_once '../includes/functions.php';

$user = getUserData($pdo, $_SESSION['user_id']);

$voterId = $_GET['id'];

// Get voter data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'pemilih'");
$stmt->execute([$voterId]);
$voter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voter) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bilikSuara = $_POST['bilik_suara'];
    $tps = $_POST['tps'];
    
    // Check if voter already has an active session
    $stmt = $pdo->prepare("
        SELECT * FROM voting_sessions 
        WHERE id_user = ? AND status = 'sedang_diproses'
    ");
    $stmt->execute([$voterId]);
    $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSession) {
        $error = "Pemilih sudah terdaftar di bilik suara!";
    } else {
        // Create new voting session
        $stmt = $pdo->prepare("
            INSERT INTO voting_sessions (id_user, id_admin, bilik_suara, tps) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$voterId, $user['id'], $bilikSuara, $tps]);
        
        // Log activity
        logActivity($pdo, $user['id'], "Mendaftarkan pemilih {$voter['nama']} ke bilik $bilikSuara, TPS $tps");
        
        header("Location: current_voters.php?success=1");
        exit();
    }
}

$pageTitle = "Daftarkan Pemilih ke Bilik Suara";
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
            <h2 class="card-title">Daftarkan Pemilih ke Bilik Suara</h2>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="voter-info">
            <h3>Informasi Pemilih</h3>
            <p><strong>NIM:</strong> <?php echo $voter['nim']; ?></p>
            <p><strong>Nama:</strong> <?php echo $voter['nama']; ?></p>
            <p><strong>Jurusan:</strong> <?php echo getJurusanName($pdo, $voter['kode_jurusan']); ?></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Bilik Suara:</label>
                <input type="number" name="bilik_suara" min="1" required>
            </div>
            <div class="form-group">
                <label>TPS:</label>
                <input type="number" name="tps" min="1" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Daftarkan</button>
                <a href="dashboard.php" class="btn">Batal</a>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>