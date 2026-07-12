<?php
session_start();
// Cek jika sudah login sebagai pemilih
if (isset($_SESSION['pemilih_id'])) {
    header("Location: pemilih/dashboard.php");
    exit();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ambil waktu voting dari session jika ada
$votingTime = null;
if (isset($_SESSION['voting_success_time'])) {
    $votingTime = $_SESSION['voting_success_time'];
    // Hapus session setelah digunakan agar tidak muncul lagi saat refresh
    unset($_SESSION['voting_success_time']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    
    // Validasi NIM
    if (empty($nim)) {
        $error = "NIM harus diisi!";
    } else {
        // Cek apakah NIM valid dan merupakan pemilih
        $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ? AND role = 'pemilih'");
        $stmt->execute([$nim]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Cek apakah sudah voting
            if ($user['has_voted']) {
                $error = "Anda sudah melakukan voting!";
            } else {
                // Cek apakah sudah diassign ke bilik suara
                $stmt = $pdo->prepare("
                    SELECT * FROM voting_sessions 
                    WHERE id_user = ? AND status = 'sedang_diproses'
                ");
                $stmt->execute([$user['id']]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($session) {
                    // Set session untuk pemilih
                    $_SESSION['pemilih_id'] = $user['id'];
                    
                    // Update last login
                    $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update->execute([$user['id']]);
                    
                    // Log aktivitas
                    logActivity($pdo, $user['id'], "Login pemilih dengan NIM");
                    
                    // Redirect ke halaman voting
                    header("Location: pemilih/voting.php");
                    exit();
                } else {
                    $error = "Anda belum terdaftar di bilik suara. Silakan hubungi panitia.";
                }
            }
        } else {
            $error = "NIM tidak terdaftar sebagai pemilih!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pemilih - PEMIRA 2025</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Tambahkan style untuk pesan sukses */
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .success-message h3 {
            margin: 0 0 10px 0;
            color: #155724;
        }
        
        .success-message p {
            margin: 10px 0;
        }
        
        .success-details {
            margin: 20px 0;
            padding: 15px;
            background-color: rgba(255,255,255,0.7);
            border-radius: 6px;
        }
        
        .success-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .countdown {
            margin-top: 20px;
            font-weight: bold;
        }
        
        .countdown span {
            color: #dc3545;
            font-weight: bold;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Style untuk alert error */
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>PEMIRA 2025</h1>
            <h2>Pemilihan Umum Raya</h2>
            <p>Login Pemilih</p>
        </div>
        
        <!-- Success Message dengan Timer -->
        <?php if (isset($_GET['voting_success']) && $votingTime): ?>
        <div id="successMessage" class="success-message">
            <div class="success-icon">✓</div>
            <h3>Voting Berhasil!</h3>
            <p>Terima kasih telah berpartisipasi dalam PEMIRA 2025. Suara Anda telah berhasil dikirim.</p>
            <div class="success-details">
                <p><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($votingTime)); ?></p>
                <p><strong>Waktu:</strong> <?php echo date('H:i:s', strtotime($votingTime)); ?></p>
            </div>
            <div class="countdown">
                <p>Halaman akan otomatis kembali dalam <span id="timer">7</span> detik</p>
            </div>
        </div>
        
        <script>
            // Timer untuk menyembunyikan pesan sukses setelah 7 detik
            let timeLeft = 7;
            const timerElement = document.getElementById('timer');
            const successMessage = document.getElementById('successMessage');
            
            const countdown = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    // Sembunyikan pesan sukses dengan animasi fade out
                    successMessage.style.opacity = '0';
                    successMessage.style.transition = 'opacity 0.5s';
                    
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                        // Tampilkan form login kembali
                        document.querySelector('.login-form').style.display = 'block';
                        document.querySelector('.login-footer').style.display = 'block';
                    }, 500);
                }
            }, 1000);
            
            // Sembunyikan form login saat pesan sukses tampil
            document.addEventListener('DOMContentLoaded', function() {
                if (document.getElementById('successMessage')) {
                    document.querySelector('.login-form').style.display = 'none';
                    document.querySelector('.login-footer').style.display = 'none';
                }
            });
        </script>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="nim">Nomor Induk Mahasiswa (NIM)</label>
                <input type="text" id="nim" name="nim" placeholder="Contoh: 202332059" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Masuk</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2025 PEMIRA. All rights reserved.</p>
        </div>
    </div>
</body>
</html>