<?php
session_start();

// Cek jika sudah login sebagai panitia
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'superadmin') {
        header("Location: superadmin/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    }
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ? AND role IN ('admin', 'superadmin')");
    $stmt->execute([$nim]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Log aktivitas
        logActivity($pdo, $user['id'], "Login");
        
        // Redirect sesuai role
        switch($user['role']) {
            case 'superadmin':
                header("Location: superadmin/dashboard.php");
                break;
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
        }
        exit();
    } else {
        $error = "NIM atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Panitia - PEMIRA 2025</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>PEMIRA 2025</h1>
            <h2>Pemilihan Umum Raya</h2>
            <p>Login Panitia</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="nim">Nomor Induk (NIM)</label>
                <input type="text" id="nim" name="nim" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="button" id="togglePassword" class="toggle-password">Show</button>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const passwordInput = document.querySelector('#password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.textContent = type === 'password' ? 'Show' : 'Hide';
                });
            }
        });
    </script>
</body>
</html>