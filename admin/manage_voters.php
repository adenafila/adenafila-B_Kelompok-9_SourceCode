<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../config/database.php';
require_once '../includes/functions.php';
 $user = getUserData($pdo, $_SESSION['user_id']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $nim = $_POST['nim'];
        $nama = $_POST['nama'];
        $kodeJurusan = $_POST['kode_jurusan'];
        
        // Extract jurusan code from NIM if not provided
        if (empty($kodeJurusan) && strlen($nim) >= 6) {
            $kodeJurusan = substr($nim, 4, 2);
        }
        
        // Check if NIM already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE nim = ?");
        $stmt->execute([$nim]);
        if ($stmt->fetch()) {
            $error = "NIM sudah terdaftar!";
        } else {
            // Insert new voter with default password
            $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (nim, nama, password, role, kode_jurusan) 
                VALUES (?, ?, ?, 'pemilih', ?)
            ");
            $stmt->execute([$nim, $nama, $defaultPassword, $kodeJurusan]);
            
            // Log activity
            logActivity($pdo, $user['id'], "Menambah pemilih: $nama ($nim)");
            
            header("Location: manage_voters.php?success=added");
            exit();
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $nim = $_POST['nim'];
        $nama = $_POST['nama'];
        $kodeJurusan = $_POST['kode_jurusan'];
        
        // Update voter
        $stmt = $pdo->prepare("
            UPDATE users 
            SET nim = ?, nama = ?, kode_jurusan = ? 
            WHERE id = ? AND role = 'pemilih'
        ");
        $stmt->execute([$nim, $nama, $kodeJurusan, $id]);
        
        // Log activity
        logActivity($pdo, $user['id'], "Mengedit pemilih ID $id");
        
        header("Location: manage_voters.php?success=updated");
        exit();
    } elseif ($action === 'delete' || $action === 'force_delete') {
        $id = $_POST['id'];
        $forceMode = ($action === 'force_delete');
        
        // Debug: Log the deletion attempt
        error_log("Attempting to " . ($forceMode ? "force " : "") . "delete voter with ID: $id");
        
        // Get voter info before deletion
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'pemilih'");
        $stmt->execute([$id]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$voter) {
            error_log("Voter not found with ID: $id");
            header("Location: manage_voters.php?error=voter_not_found&id=$id");
            exit();
        }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if voter has voted using multiple possible column names
            $hasVoted = false;
            $deletedVotes = false;
            
            // Try different possible table and column combinations
            $voteCheckQueries = [
                // Table: votes, columns: user_id, nim, voter_id
                ["SELECT * FROM votes WHERE user_id = ?", "DELETE FROM votes WHERE user_id = ?"],
                ["SELECT * FROM votes WHERE nim = ?", "DELETE FROM votes WHERE nim = ?"],
                ["SELECT * FROM votes WHERE voter_id = ?", "DELETE FROM votes WHERE voter_id = ?"],
                // Table: vote, columns: user_id, nim, voter_id
                ["SELECT * FROM vote WHERE user_id = ?", "DELETE FROM vote WHERE user_id = ?"],
                ["SELECT * FROM vote WHERE nim = ?", "DELETE FROM vote WHERE nim = ?"],
                ["SELECT * FROM vote WHERE voter_id = ?", "DELETE FROM vote WHERE voter_id = ?"],
            ];
            
            foreach ($voteCheckQueries as $queries) {
                try {
                    $checkQuery = $queries[0];
                    $deleteQuery = $queries[1];
                    
                    // Check if vote exists
                    $stmt = $pdo->prepare($checkQuery);
                    $stmt->execute([$id]);
                    if ($stmt->fetch()) {
                        // Delete the vote
                        $stmt = $pdo->prepare($deleteQuery);
                        $stmt->execute([$id]);
                        $hasVoted = true;
                        $deletedVotes = true;
                        error_log("Deleted vote using query: $deleteQuery with ID: $id");
                        break;
                    }
                } catch (PDOException $e) {
                    // Table or column doesn't exist, continue to next
                    error_log("Query failed: " . $e->getMessage());
                    continue;
                }
            }
            
            // Try with voter NIM
            if (!$hasVoted && isset($voter['nim'])) {
                $nimVoteQueries = [
                    ["SELECT * FROM votes WHERE nim = ?", "DELETE FROM votes WHERE nim = ?"],
                    ["SELECT * FROM vote WHERE nim = ?", "DELETE FROM vote WHERE nim = ?"],
                ];
                
                foreach ($nimVoteQueries as $queries) {
                    try {
                        $checkQuery = $queries[0];
                        $deleteQuery = $queries[1];
                        
                        // Check if vote exists
                        $stmt = $pdo->prepare($checkQuery);
                        $stmt->execute([$voter['nim']]);
                        if ($stmt->fetch()) {
                            // Delete the vote
                            $stmt = $pdo->prepare($deleteQuery);
                            $stmt->execute([$voter['nim']]);
                            $hasVoted = true;
                            $deletedVotes = true;
                            error_log("Deleted vote using NIM: {$voter['nim']}");
                            break;
                        }
                    } catch (PDOException $e) {
                        // Table or column doesn't exist, continue to next
                        error_log("NIM query failed: " . $e->getMessage());
                        continue;
                    }
                }
            }
            
            // Try to delete the voter with different approaches
            $voterDeleted = false;
            
            // Approach 1: Direct delete
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'pemilih'");
                $result = $stmt->execute([$id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $voterDeleted = true;
                    error_log("Successfully deleted voter with ID: $id");
                }
            } catch (PDOException $e) {
                error_log("Direct delete failed: " . $e->getMessage());
            }
            
            // Approach 2: If direct delete failed and force mode is on, try to identify constraints
            if (!$voterDeleted && $forceMode) {
                try {
                    // Get all tables that might have foreign key constraints
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $constraintInfo = [];
                    
                    foreach ($tables as $table) {
                        if ($table !== 'users') {
                            try {
                                // Check if table has foreign key reference to users
                                $stmt = $pdo->prepare("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'users' AND TABLE_NAME = ?");
                                $stmt->execute([$table]);
                                $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($constraints)) {
                                    $constraintInfo[] = [
                                        'table' => $table,
                                        'constraints' => $constraints
                                    ];
                                }
                            } catch (PDOException $e) {
                                continue;
                            }
                        }
                    }
                    
                    if (!empty($constraintInfo)) {
                        // Log constraint info
                        error_log("Found constraints in tables: " . implode(", ", array_column($constraintInfo, 'table')));
                        
                        // Try to delete records from constraint tables first
                        foreach ($constraintInfo as $info) {
                            $table = $info['table'];
                            foreach ($info['constraints'] as $constraint) {
                                $columnName = $constraint['COLUMN_NAME'];
                                
                                try {
                                    $deleteQuery = "DELETE FROM $table WHERE $columnName = ?";
                                    $stmt = $pdo->prepare($deleteQuery);
                                    $stmt->execute([$id]);
                                    error_log("Deleted from $table using $columnName = $id");
                                } catch (PDOException $e) {
                                    error_log("Failed to delete from $table: " . $e->getMessage());
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Constraint check failed: " . $e->getMessage());
                }
                
                // Approach 3: Try to disable foreign key checks temporarily
                try {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    error_log("Disabled foreign key checks");
                    
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'pemilih'");
                    $result = $stmt->execute([$id]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $voterDeleted = true;
                        error_log("Deleted voter with disabled foreign key checks");
                    }
                    
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    error_log("Re-enabled foreign key checks");
                } catch (PDOException $e) {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    error_log("Delete with disabled FK checks failed: " . $e->getMessage());
                }
            }
            
            if ($voterDeleted) {
                $pdo->commit();
                
                // Log activity
                $message = "Menghapus pemilih: {$voter['nama']} ({$voter['nim']})";
                if ($deletedVotes) {
                    $message .= " dan semua data voting terkait";
                }
                if ($forceMode) {
                    $message .= " (mode paksa)";
                }
                logActivity($pdo, $user['id'], $message);
                
                header("Location: manage_voters.php?success=deleted");
                exit();
            } else {
                $pdo->rollBack();
                header("Location: manage_voters.php?error=delete_failed&voter_id=" . $id);
                exit();
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Exception during deletion: " . $e->getMessage());
            header("Location: manage_voters.php?error=exception&msg=" . urlencode($e->getMessage()) . "&voter_id=" . $id);
            exit();
        }
    }
}

// Get search and filter parameters
 $search = isset($_GET['search']) ? trim($_GET['search']) : '';
 $filterJurusan = isset($_GET['jurusan']) ? trim($_GET['jurusan']) : '';

// Build query with search and filter
 $query = "
    SELECT u.id, u.nim, u.nama, j.nama_jurusan, u.has_voted
    FROM users u
    JOIN jurusan j ON u.kode_jurusan = j.kode_jurusan
    WHERE u.role = 'pemilih'
";

 $params = [];

// Add search conditions if search term is provided
if (!empty($search)) {
    $query .= " AND (u.nim LIKE ? OR u.nama LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add filter condition if jurusan is selected
if (!empty($filterJurusan)) {
    $query .= " AND u.kode_jurusan = ?";
    $params[] = $filterJurusan;
}

 $query .= " ORDER BY u.nama";

// Prepare and execute the query
 $stmt = $pdo->prepare($query);
 $stmt->execute($params);
 $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all jurusan for filter dropdown
 $stmt = $pdo->query("SELECT * FROM jurusan ORDER BY nama_jurusan");
 $jurusanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = "Manajemen Pemilih";
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
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .status-voted {
            background-color: #28a745;
            color: black;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: black;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .btn-delete {
            background-color: #dc3545;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .btn-force {
            background-color: #6f42c1;
            color: white;
        }
        
        .btn-force:hover {
            background-color: #5a32a3;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
        }
        
        .modal-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #ff6600;
            color: #000000;
            font-weight: bold;
        }
        
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .force-delete-form {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
        }
        
        .database-debug {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .database-debug h3 {
            margin-top: 0;
            color: #495057;
        }
        
        .database-debug pre {
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        .search-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .search-container {
            flex: 2;
        }
        
        .filter-container {
            flex: 1;
        }
        
        .search-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .clear-filters {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .clear-filters:hover {
            background-color: #5a6268;
        }
        
        .results-info {
            margin-bottom: 15px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'added'): ?>
            <div class="alert alert-success">Pemilih berhasil ditambahkan!</div>
        <?php elseif ($_GET['success'] === 'updated'): ?>
            <div class="alert alert-success">Data pemilih berhasil diperbarui!</div>
        <?php elseif ($_GET['success'] === 'deleted'): ?>
            <div class="alert alert-success">Pemilih berhasil dihapus beserta semua data terkait!</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <?php if ($_GET['error'] === 'delete_failed'): ?>
            <div class="alert alert-error">
                <strong>Gagal menghapus pemilih!</strong>
                <?php if (isset($_GET['voter_id'])): ?>
                    <div class="force-delete-form">
                        <h4>Opsi Lanjutan:</h4>
                        <p>Jika Anda yakin ingin menghapus pemilih ini beserta semua data terkait, Anda bisa mencoba hapus paksa:</p>
                        <form method="POST" onsubmit="return confirm('HAPUS PAKSA: Ini akan menghapus pemilih dan semua data terkait secara paksa. LANJUTKAN?')">
                            <input type="hidden" name="action" value="force_delete">
                            <input type="hidden" name="id" value="<?php echo $_GET['voter_id']; ?>">
                            <button type="submit" class="btn btn-force">Hapus Paksa</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($_GET['error'] === 'voter_not_found'): ?>
            <div class="alert alert-error">Pemilih tidak ditemukan!</div>
        <?php elseif ($_GET['error'] === 'constraints'): ?>
            <div class="alert alert-warning">
                <strong>Constraint Database Terdeteksi!</strong><br>
                Tidak dapat menghapus pemilih karena ada data terkait di tabel: 
                <?php echo htmlspecialchars(urldecode($_GET['tables'])); ?>
                <div class="force-delete-form">
                    <h4>Opsi Lanjutan:</h4>
                    <p>Anda bisa mencoba hapus paksa untuk menghapus semua constraint:</p>
                    <form method="POST" onsubmit="return confirm('HAPUS PAKSA: Ini akan menghapus semua constraint database. LANJUTKAN?')">
                        <input type="hidden" name="action" value="force_delete">
                        <input type="hidden" name="id" value="<?php echo $_GET['voter_id']; ?>">
                        <button type="submit" class="btn btn-force">Hapus Paksa</button>
                    </form>
                </div>
            </div>
        <?php elseif ($_GET['error'] === 'exception'): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tambah Pemilih Baru</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label>NIM:</label>
                    <input type="text" name="nim" placeholder="Contoh: 202332059" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap:</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-group">
                    <label>Jurusan:</label>
                    <select name="kode_jurusan">
                        <option value="">-- Otomatis dari NIM --</option>
                        <?php foreach ($jurusanList as $jurusan): ?>
                            <option value="<?php echo $jurusan['kode_jurusan']; ?>">
                                <?php echo $jurusan['nama_jurusan']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Tambah Pemilih</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Daftar Pemilih</h2>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filter-container">
            <div class="search-container">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Cari berdasarkan NIM atau nama..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if (!empty($filterJurusan)): ?>
                        <input type="hidden" name="jurusan" value="<?php echo htmlspecialchars($filterJurusan); ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="filter-container">
                <form method="GET" action="" id="filterForm">
                    <select name="jurusan" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- Semua Jurusan --</option>
                        <?php foreach ($jurusanList as $jurusan): ?>
                            <option value="<?php echo $jurusan['kode_jurusan']; ?>" <?php echo ($filterJurusan === $jurusan['kode_jurusan']) ? 'selected' : ''; ?>>
                                <?php echo $jurusan['nama_jurusan']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if (!empty($search) || !empty($filterJurusan)): ?>
            <div class="results-info">
                <?php 
                    $filterText = [];
                    if (!empty($search)) {
                        $filterText[] = "pencarian: \"$search\"";
                    }
                    if (!empty($filterJurusan)) {
                        $jurusanName = '';
                        foreach ($jurusanList as $jurusan) {
                            if ($jurusan['kode_jurusan'] === $filterJurusan) {
                                $jurusanName = $jurusan['nama_jurusan'];
                                break;
                            }
                        }
                        $filterText[] = "jurusan: $jurusanName";
                    }
                    
                    echo "Menampilkan " . count($voters) . " hasil dengan filter " . implode(', ', $filterText);
                ?>
                <a href="manage_voters.php" class="clear-filters">Hapus Filter</a>
            </div>
        <?php endif; ?>
        
        <table>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Jurusan</th>
                <th>Status Voting</th>
                <th>Aksi</th>
            </tr>
            <?php if (empty($voters)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Tidak ada data pemilih yang ditemukan.</td>
                </tr>
            <?php else: ?>
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
                    <td>
                        <button onclick="editVoter(<?php echo $voter['id']; ?>, '<?php echo $voter['nim']; ?>', '<?php echo $voter['nama']; ?>', '<?php echo substr($voter['nim'], 4, 2); ?>')" class="btn">Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('<?php echo $voter['nama']; ?>', <?php echo $voter['has_voted'] ? 'true' : 'false'; ?>)">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $voter['id']; ?>">
                            <button type="submit" class="btn btn-delete">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Pemilih</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>NIM:</label>
                    <input type="text" name="nim" id="editNim" required>
                </div>
                <div class="form-group">
                    <label>Nama:</label>
                    <input type="text" name="nama" id="editNama" required>
                </div>
                <div class="form-group">
                    <label>Jurusan:</label>
                    <select name="kode_jurusan" id="editJurusan">
                        <?php foreach ($jurusanList as $jurusan): ?>
                            <option value="<?php echo $jurusan['kode_jurusan']; ?>">
                                <?php echo $jurusan['nama_jurusan']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeEditModal()" class="btn">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editVoter(id, nim, nama, jurusan) {
            document.getElementById('editId').value = id;
            document.getElementById('editNim').value = nim;
            document.getElementById('editNama').value = nama;
            document.getElementById('editJurusan').value = jurusan;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function confirmDelete(name, hasVoted) {
            var message = "Apakah Anda yakin ingin menghapus pemilih " + name + "?";
            if (hasVoted) {
                message += "\n\nPERINGATAN: Pemilih ini sudah melakukan voting. Semua data voting yang terkait juga akan dihapus.";
            }
            return confirm(message);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Auto-submit search form on Enter key
        document.querySelector('.search-container input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>