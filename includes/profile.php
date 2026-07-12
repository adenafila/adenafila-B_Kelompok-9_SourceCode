<?php
require_once 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_panitia.php");
    exit();
}

require_once 'config/database.php';
require_once 'functions.php';

$user = getUserData($pdo, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 6) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);
                
                // Log activity
                logActivity($pdo, $user['id'], "Mengubah password");
                
                $success = "Password berhasil diperbarui!";
            } else {
                $error = "Password baru minimal 6 karakter!";
            }
        } else {
            $error = "Password baru dan konfirmasi tidak cocok!";
        }
    } else {
        $error = "Password saat ini salah!";
    }
}

// Determine redirect based on role
$redirect = $user['role'] === 'superadmin' ? '../superadmin/dashboard.php' : '../admin/dashboard.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profil - PEMIRA 2025</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Edit Profil</h1>
            <p><?php echo $user['nama']; ?> (<?php echo ucfirst($user['role']); ?>)</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-card">
            <h2>Ubah Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Password Saat Ini:</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>Password Baru:</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                    <a href="<?php echo $redirect; ?>" class="btn">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>