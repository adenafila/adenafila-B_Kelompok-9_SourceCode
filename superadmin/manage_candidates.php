<?php
require_once '../includes/auth.php';
requireRole('superadmin');
require_once '../config/database.php';
require_once '../includes/functions.php';
$user = getUserData($pdo, $_SESSION['user_id']);

// Handle success and error messages
$successMessage = '';
$errorMessage = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $candidateId = $_GET['id'];
    
    // Get candidate data for logging and deleting photo
    $stmt = $pdo->prepare("SELECT * FROM kandidat WHERE id = ?");
    $stmt->execute([$candidateId]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($candidate) {
        // Delete candidate photo if exists
        if ($candidate['foto'] && file_exists('../' . $candidate['foto'])) {
            unlink('../' . $candidate['foto']);
        }
        
        // Delete candidate
        $stmt = $pdo->prepare("DELETE FROM kandidat WHERE id = ?");
        $stmt->execute([$candidateId]);
        
        // Log activity
        logActivity($pdo, $user['id'], "Menghapus kandidat: {$candidate['nama']}");
        
        $successMessage = "Kandidat berhasil dihapus!";
    } else {
        $errorMessage = "Kandidat tidak ditemukan!";
    }
}

// Handle edit form submission
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $candidateId = $_POST['id'];
    $nama = $_POST['nama'];
    $tipe = $_POST['tipe'];
    $kodeJurusan = $_POST['kode_jurusan'] ?: null;
    $visiMisi = $_POST['visi_misi'];
    
    // Get current candidate data
    $stmt = $pdo->prepare("SELECT * FROM kandidat WHERE id = ?");
    $stmt->execute([$candidateId]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($candidate) {
        // Handle file upload
        $foto = $candidate['foto']; // Keep existing photo by default
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/candidates/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['foto']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                // Delete old photo if exists
                if ($candidate['foto'] && file_exists('../' . $candidate['foto'])) {
                    unlink('../' . $candidate['foto']);
                }
                $foto = 'assets/images/candidates/' . $fileName;
            }
        }
        
        // Update candidate
        $stmt = $pdo->prepare("
            UPDATE kandidat 
            SET nama = ?, tipe = ?, kode_jurusan = ?, foto = ?, visi_misi = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nama, $tipe, $kodeJurusan, $foto, $visiMisi, $candidateId]);
        
        // Log activity
        logActivity($pdo, $user['id'], "Mengedit kandidat: $nama");
        
        $successMessage = "Kandidat berhasil diperbarui!";
    } else {
        $errorMessage = "Kandidat tidak ditemukan!";
    }
}

// Handle add form submission
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama = $_POST['nama'];
    $tipe = $_POST['tipe'];
    $kodeJurusan = $_POST['kode_jurusan'] ?: null;
    $visiMisi = $_POST['visi_misi'];
    
    // Handle file upload
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/candidates/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
            $foto = 'assets/images/candidates/' . $fileName;
        }
    }
    
    // Insert candidate
    $stmt = $pdo->prepare("
        INSERT INTO kandidat (nama, tipe, kode_jurusan, foto, visi_misi) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nama, $tipe, $kodeJurusan, $foto, $visiMisi]);
    
    // Log activity
    logActivity($pdo, $user['id'], "Menambah kandidat: $nama");
    
    $successMessage = "Kandidat berhasil ditambahkan!";
}

// Get all candidates
$stmt = $pdo->query("
    SELECT k.*, j.nama_jurusan 
    FROM kandidat k 
    LEFT JOIN jurusan j ON k.kode_jurusan = j.kode_jurusan 
    ORDER BY k.tipe, j.nama_jurusan, k.nama
");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all jurusan for DPM candidates
$stmt = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan");
$jurusanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get candidate data for edit form
$editMode = false;
$editCandidate = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $candidateId = $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT k.*, j.nama_jurusan 
        FROM kandidat k 
        LEFT JOIN jurusan j ON k.kode_jurusan = j.kode_jurusan 
        WHERE k.id = ?
    ");
    $stmt->execute([$candidateId]);
    $editCandidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($editCandidate) {
        $editMode = true;
    }
}

$pageTitle = "Kelola Kandidat";
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 5px;
            background-color: #0c4f6a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #093d50;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        table th {
            background-color: #ff6600;
            color: #000000;
            font-weight: bold;
        }
        
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?php echo $editMode ? 'Edit Kandidat' : 'Tambah Kandidat'; ?></h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editMode): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $editCandidate['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nama Kandidat:</label>
                <input type="text" name="nama" value="<?php echo $editMode ? htmlspecialchars($editCandidate['nama']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Tipe Kandidat:</label>
                <select name="tipe" required>
                    <option value="presiden" <?php echo ($editMode && $editCandidate['tipe'] === 'presiden') ? 'selected' : ''; ?>>Presiden Mahasiswa</option>
                    <option value="dpm" <?php echo ($editMode && $editCandidate['tipe'] === 'dpm') ? 'selected' : ''; ?>>Dewan Perwakilan Mahasiswa</option>
                </select>
            </div>
            <div class="form-group" id="jurusan-group" style="<?php echo ($editMode && $editCandidate['tipe'] === 'dpm') ? 'display: block;' : 'display: none;'; ?>">
                <label>Jurusan (untuk DPM):</label>
                <select name="kode_jurusan">
                    <option value="">-- Pilih Jurusan --</option>
                    <?php foreach ($jurusanList as $jurusan): ?>
                        <option value="<?php echo $jurusan['kode_jurusan']; ?>" <?php echo ($editMode && $editCandidate['kode_jurusan'] === $jurusan['kode_jurusan']) ? 'selected' : ''; ?>>
                            <?php echo $jurusan['nama_jurusan']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Foto:</label>
                <?php if ($editMode && $editCandidate['foto']): ?>
                    <div>
                        <img src="../<?php echo $editCandidate['foto']; ?>" alt="<?php echo $editCandidate['nama']; ?>" width="100">
                        <p>Foto saat ini</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="foto" accept="image/*">
                <small><?php echo $editMode ? 'Kosongkan jika tidak ingin mengubah foto' : ''; ?></small>
            </div>
            <div class="form-group">
                <label>Visi & Misi:</label>
                <textarea name="visi_misi" rows="4" required><?php echo $editMode ? htmlspecialchars($editCandidate['visi_misi']) : ''; ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo $editMode ? 'Update Kandidat' : 'Tambah Kandidat'; ?></button>
                <?php if ($editMode): ?>
                    <a href="manage_candidates.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Daftar Kandidat</h2>
        </div>
        <table>
            <tr>
                <th>Nama</th>
                <th>Tipe</th>
                <th>Jurusan</th>
                <th>Foto</th>
                <th>Visi & Misi</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($candidates as $candidate): ?>
            <tr>
                <td><?php echo $candidate['nama']; ?></td>
                <td><?php echo $candidate['tipe'] === 'presiden' ? 'Presiden Mahasiswa' : 'DPM'; ?></td>
                <td><?php echo $candidate['nama_jurusan'] ?: '-'; ?></td>
                <td>
                    <?php if ($candidate['foto']): ?>
                        <img src="../<?php echo $candidate['foto']; ?>" alt="<?php echo $candidate['nama']; ?>" width="50">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo nl2br(substr($candidate['visi_misi'], 0, 100)) . (strlen($candidate['visi_misi']) > 100 ? '...' : ''); ?></td>
                <td>
                    <a href="manage_candidates.php?action=edit&id=<?php echo $candidate['id']; ?>" class="btn">Edit</a>
                    <a href="manage_candidates.php?action=delete&id=<?php echo $candidate['id']; ?>" class="btn btn-delete" onclick="return confirm('Apakah Anda yakin?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
        // Show/hide jurusan field based on tipe
        document.querySelector('select[name="tipe"]').addEventListener('change', function() {
            const jurusanGroup = document.getElementById('jurusan-group');
            if (this.value === 'dpm') {
                jurusanGroup.style.display = 'block';
            } else {
                jurusanGroup.style.display = 'none';
            }
        });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>