<?php
require_once '../includes/auth.php';
requirePemilihLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$user = getPemilihData($pdo, $_SESSION['pemilih_id']);

// Check if user has already voted
if ($user['has_voted']) {
    header("Location: dashboard.php?already_voted=1");
    exit();
}

// Check if user has active voting session
$stmt = $pdo->prepare("
    SELECT * FROM voting_sessions 
    WHERE id_user = ? AND status = 'sedang_diproses'
");
$stmt->execute([$user['id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    header("Location: dashboard.php?no_session=1");
    exit();
}

// Get presiden candidates
$stmt = $pdo->query("SELECT * FROM kandidat WHERE tipe = 'presiden'");
$presidenCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get DPM candidates based on jurusan
$stmt = $pdo->prepare("SELECT * FROM kandidat WHERE tipe = 'dpm' AND kode_jurusan = ?");
$stmt->execute([$user['kode_jurusan']]);
$dpmCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPresiden = $_POST['presiden'];
    $idDpm = $_POST['dpm'];
    
    // Di bagian proses POST voting
    try {
        $pdo->beginTransaction();
        
        // Insert vote
        $stmt = $pdo->prepare("
            INSERT INTO votes (id_user, id_presiden, id_dpm) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $idPresiden, $idDpm]);
        
        // Update session status
        $stmt = $pdo->prepare("
            UPDATE voting_sessions 
            SET status = 'selesai' 
            WHERE id = ?
        ");
        $stmt->execute([$session['id']]);
        
        // Update user has_voted status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET has_voted = 1 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Log activity
        logActivity($pdo, $user['id'], "Melakukan voting");
        
        $pdo->commit();
        
        // Simpan waktu voting di session
        $_SESSION['voting_success_time'] = date('Y-m-d H:i:s');

        // Destroy session and redirect to index with success message
        session_destroy();

        // Redirect ke halaman index dengan parameter success
        header("Location: ../index.php?voting_success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal menyimpan suara: " . $e->getMessage();
    }
}

$pageTitle = "Voting";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <h1>Pemilihan Umum Raya 2025</h1>
    <h2>Selamat datang, <?php echo $user['nama']; ?></h2>
    <p>NIM: <?php echo $user['nim']; ?></p>
    <p>Jurusan: <?php echo getJurusanName($pdo, $user['kode_jurusan']); ?></p>
    <p>Bilik Suara: <?php echo $session['bilik_suara']; ?> | TPS: <?php echo $session['tps']; ?></p>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" id="votingForm">
    <div class="voting-section">
        <h3>Pilih Pasangan Calon Presiden Mahasiswa</h3>
        <div class="candidates-grid">
            <?php foreach ($presidenCandidates as $index => $candidate): ?>
            <div class="candidate-card <?php echo ($candidate['id'] == 0) ? 'kotak-kosong' : ''; ?>" onclick="selectCandidate('presiden_<?php echo $candidate['id']; ?>')">
                <input type="radio" name="presiden" value="<?php echo $candidate['id']; ?>" id="presiden_<?php echo $candidate['id']; ?>" required>
                <label for="presiden_<?php echo $candidate['id']; ?>">
                    <?php if ($candidate['foto'] && $candidate['id'] != 0): ?>
                        <img src="../<?php echo $candidate['foto']; ?>" alt="<?php echo $candidate['nama']; ?>">
                    <?php else: ?>
                        <div class="red-box-placeholder"></div> 
                    <?php endif; ?>
                    <h4><?php echo $candidate['nama']; ?></h4>
                    <p><?php echo nl2br($candidate['visi_misi']); ?></p>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="voting-section">
        <h3>Pilih Calon Dewan Perwakilan Mahasiswa</h3>
        <div class="candidates-grid">
            <?php foreach ($dpmCandidates as $index => $candidate): ?>
            <div class="candidate-card" onclick="selectCandidate('dpm_<?php echo $candidate['id']; ?>')">
                <input type="radio" name="dpm" value="<?php echo $candidate['id']; ?>" id="dpm_<?php echo $candidate['id']; ?>" required>
                <label for="dpm_<?php echo $candidate['id']; ?>">
                    <?php if ($candidate['foto']): ?>
                        <img src="../<?php echo $candidate['foto']; ?>" alt="<?php echo $candidate['nama']; ?>">
                    <?php endif; ?>
                    <h4><?php echo $candidate['nama']; ?></h4>
                    <p><?php echo nl2br($candidate['visi_misi']); ?></p>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary" id="submitBtn">Kirim Suara</button>
    </div>
</form>
    
    <!-- Modal Konfirmasi -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Voting</h3>
            <p>Apakah Anda yakin dengan pilihan Anda? Setelah mengirim suara, Anda tidak dapat mengubahnya lagi.</p>
            <div class="modal-actions">
                <button id="cancelBtn" class="btn">Batal</button>
                <button id="confirmBtn" class="btn btn-primary">Ya, Kirim Suara</button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p>Mengirim suara Anda...</p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('votingForm');
        const submitBtn = document.getElementById('submitBtn');
        const modal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        // Show confirmation modal when form is submitted
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        });
        
        // Hide modal when cancel is clicked
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Submit form when confirm is clicked
        confirmBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            loadingOverlay.style.display = 'flex';
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim...';
            
            // Submit the form
            form.submit();
        });
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Fungsi untuk memilih kandidat saat card diklik
    function selectCandidate(id) {
        document.getElementById(id).checked = true;
    }
</script>
</body>
</html>