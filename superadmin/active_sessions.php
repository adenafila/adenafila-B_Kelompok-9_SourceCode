<?php
require_once '../includes/auth.php';
requireRole('superadmin');
require_once '../config/database.php';
require_once '../includes/functions.php';
$user = getUserData($pdo, $_SESSION['user_id']);

// Get all TPS for the filter dropdown - menggunakan tabel voting_sessions
try {
    $stmt = $pdo->query("SELECT DISTINCT tps FROM voting_sessions ORDER BY tps");
    $tpsList = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Jika tidak ada data TPS dari voting_sessions, gunakan data dummy
    if (empty($tpsList)) {
        $tpsList = ['1', '2', '3', '4', '5'];
    }
} catch (PDOException $e) {
    // Jika tabel voting_sessions tidak ada atau ada error, gunakan data dummy
    $tpsList = ['1', '2', '3', '4', '5'];
}

// Get current voters
try {
    $currentVoters = getCurrentVoters($pdo);
} catch (PDOException $e) {
    // Jika ada error mendapatkan data pemilih, gunakan array kosong
    $currentVoters = [];
}

// Get selected TPS from session or set default to all
$selectedTPS = isset($_SESSION['selected_tps']) ? $_SESSION['selected_tps'] : 'all';

// Filter voters by selected TPS if not 'all'
if ($selectedTPS !== 'all') {
    $filteredVoters = array_filter($currentVoters, function($voter) use ($selectedTPS) {
        return isset($voter['tps']) && $voter['tps'] == $selectedTPS;
    });
} else {
    $filteredVoters = $currentVoters;
}

$pageTitle = "Pemilih Terkini";
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Library untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Variabel Warna - Sesuai dengan tema */
        :root {
            --primary-color: #0c4f6a;
            --primary-dark: #083344;
            --primary-light: #1a6d8a;
            --secondary-color: #b21f1f;
            --accent-color: #fdbb2d;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-color: #333333;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 24px rgba(0,0,0,0.12);
            --radius: 12px;
            --transition: all 0.3s ease;
        }
        
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
            width: 100%;
        }
        
        /* Alert styling */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: fadeInDown 0.8s ease;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success i {
            color: #28a745;
            font-size: 1.2rem;
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error i {
            color: #dc3545;
            font-size: 1.2rem;
        }
        
        /* Card styling */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(12, 79, 106, 0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .card-header {
            padding: 1.8rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .card-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 1;
            color: #0c4f6a !important;
        }
        
        .card-title i {
            color: #0c4f6a !important;
            font-size: 1.5rem;
        }
        
        .tps-filter-container {
            display: flex;
            align-items: center;
            margin-top: 0.8rem;
            margin-left:10px;
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1rem;
            gap: 10px;
        }
        
        .tps-filter-container select {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.95rem;
            min-width: 80px;
            cursor: pointer;
            outline: none;
        }
        
        .tps-filter-container select option {
            background-color: var(--primary-color);
            color: white;
        }
        
        .tps-filter-container i {
            color: var(--accent-color);
            font-size: 1.1rem;
        }
        
        .refresh-info {
            display: flex;
            align-items: center;
            margin-top: 0.8rem;
            position: relative;
            z-index: 1;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .refresh-info i {
            margin-right: 10px;
            color: var(--accent-color);
            font-size: 1.1rem;
        }
        
        .refresh-status {
            display: flex;
            align-items: center;
            margin-left: 20px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.85) !important;
        }
        
        .refresh-status i {
            color: var(--accent-color);
            margin-right: 8px;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            margin-top: 0.8rem;
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1rem;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-right: 12px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.3);
            transition: .4s;
            border-radius: 30px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        
        .countdown-container {
            display: flex;
            align-items: center;
            margin-left: 20px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.85) !important;
        }
        
        .countdown-timer {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 12px;
            margin-left: 8px;
            font-weight: 600;
            color: #ffffff !important;
            min-width: 30px;
            text-align: center;
            font-size: 1rem;
        }
        
        .card-body {
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Table styling */
        .table-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .table-wrapper {
            overflow-x: auto;
            position: relative;
            flex: 1;
            min-height: 0;
        }
        
        .table-scroll {
            height: 500px;
            overflow-y: auto;
            position: relative;
        }
        
        /* Custom scrollbar */
        .table-scroll::-webkit-scrollbar {
            width: 12px;
        }
        
        .table-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
        
        .table-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 6px;
        }
        
        .table-scroll::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(12, 79, 106, 0.1);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            font-size: 1.1rem;
        }
        
        table th {
            background-color: var(--light-bg);
            color: var(--primary-color);
            font-weight: 600;
            text-align: left;
            padding: 1.2rem 1.5rem;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }
        
        table th i {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        
        table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover {
            background-color: rgba(12, 79, 106, 0.03);
        }
        
        table tr:hover td {
            transition: background-color 0.2s ease;
        }
        
        /* Table info footer */
        .table-info {
            padding: 1rem 1.5rem;
            background-color: var(--light-bg);
            border-top: 1px solid var(--border-color);
            font-size: 1rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .record-count {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .record-count i {
            font-size: 1.2rem;
        }
        
        .tps-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .tps-info i {
            font-size: 1.2rem;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .status-badge i {
            font-size: 1rem;
        }
        
        .status-ready {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .status-voted {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3.5rem 2rem;
            color: var(--text-muted);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .empty-state i {
            font-size: 4.5rem;
            margin-bottom: 1.2rem;
            color: #dee2e6;
        }
        
        .empty-state p {
            font-size: 1.2rem;
            margin: 0;
            font-weight: 500;
        }
        
        /* Animasi */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .container {
                padding: 1.5rem 1rem;
            }
            
            .card-title {
                font-size: 1.6rem;
            }
            
            table {
                font-size: 1rem;
            }
            
            table th, table td {
                padding: 1rem 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-scroll {
                height: 400px;
            }
            
            .container {
                padding: 1rem;
            }
            
            .card-header {
                padding: 1.2rem;
            }
            
            .card-title {
                font-size: 1.3rem;
            }
            
            .tps-filter-container {
                flex-wrap: wrap;
            }
            
            .tps-filter-container select {
                min-width: 100%;
                margin-top: 5px;
            }
            
            .refresh-info {
                flex-wrap: wrap;
            }
            
            .refresh-status {
                margin-left: 0;
                margin-top: 5px;
                width: 100%;
            }
            
            .toggle-container {
                flex-wrap: wrap;
            }
            
            .countdown-container {
                margin-left: 0;
                margin-top: 5px;
                width: 100%;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            table th, table td {
                padding: 0.8rem 0.6rem;
            }
        }
        
        @media (max-width: 576px) {
            .table-scroll {
                height: 350px;
            }
            
            .card-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .refresh-info {
                align-self: flex-start;
                margin-top: 0;
            }
            
            .table-wrapper {
                margin: 0 -1rem;
                width: calc(100% + 2rem);
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
            
            .empty-state i {
                font-size: 3rem;
            }
            
            .table-info {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Pemilih berhasil didaftarkan ke bilik suara!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-clock"></i>
                    <span>Pemilih Terkini</span>
                </h2>
                
                <div class="tps-filter-container">
                    <i class="fas fa-filter"></i>
                    <select id="tpsFilter">
                        <option value="all" <?php echo $selectedTPS === 'all' ? 'selected' : ''; ?>>Semua TPS</option>
                        <?php foreach ($tpsList as $tps): ?>
                            <option value="<?php echo $tps; ?>" <?php echo $selectedTPS === $tps ? 'selected' : ''; ?>>TPS <?php echo $tps; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="refresh-info">
                    <i class="fas fa-sync-alt"></i>
                    <span>Auto-refresh data</span>
                </div>
                <div class="toggle-container">
                    <label class="toggle-switch">
                        <input type="checkbox" id="autoRefreshToggle" checked>
                        <span class="slider"></span>
                    </label>
                    <span id="toggleStatus">Aktif</span>
                    <div class="countdown-container">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Refresh dalam:</span>
                        <span class="countdown-timer" id="countdownTimer">10</span>
                    </div>
                </div>
                <div class="refresh-status">
                    <span id="refreshStatus">Terakhir update: sekarang</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container" id="currentVotersTable">
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="loading-spinner"></div>
                    </div>
                    
                    <?php if (count($filteredVoters) > 0): ?>
                        <div class="table-wrapper">
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-id-card"></i> NIM</th>
                                            <th><i class="fas fa-user"></i> Nama</th>
                                            <th><i class="fas fa-door-open"></i> Bilik</th>
                                            <th><i class="fas fa-map-marker-alt"></i> TPS</th>
                                            <th><i class="fas fa-info-circle"></i> Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="currentVotersBody">
                                        <?php foreach ($filteredVoters as $voter): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($voter['nim']); ?></td>
                                            <td><?php echo htmlspecialchars($voter['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($voter['bilik_suara']); ?></td>
                                            <td><?php echo htmlspecialchars($voter['tps']); ?></td>
                                            <td>
                                                <?php if ($voter['status'] === 'sedang_diproses'): ?>
                                                    <span class="status-badge status-ready">
                                                        <i class="fas fa-spinner fa-pulse"></i> Di Bilik
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge status-voted">
                                                        <i class="fas fa-check-circle"></i> Selesai
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-info">
                            <div class="record-count">
                                <i class="fas fa-users"></i>
                                <span id="voterCount"><?php echo count($filteredVoters); ?> pemilih aktif</span>
                            </div>
                            <div class="tps-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span id="currentTPS">
                                    <?php 
                                    if ($selectedTPS === 'all') {
                                        echo 'Semua TPS';
                                    } else {
                                        echo 'TPS ' . $selectedTPS;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-clock"></i>
                            <p>
                                <?php 
                                if ($selectedTPS === 'all') {
                                    echo 'Tidak ada pemilih aktif saat ini';
                                } else {
                                    echo 'Tidak ada pemilih aktif di TPS ' . $selectedTPS;
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Variabel global
        let refreshInterval;
        let countdownInterval;
        let countdownValue = 10; // Diubah menjadi 10 detik sesuai kebutuhan
        let isAutoRefreshEnabled = true;
        let selectedTPS = '<?php echo $selectedTPS; ?>';
        
        // Elemen DOM
        const refreshStatusElement = document.getElementById('refreshStatus');
        const currentVotersBody = document.getElementById('currentVotersBody');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const voterCountElement = document.getElementById('voterCount');
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        const toggleStatus = document.getElementById('toggleStatus');
        const countdownTimer = document.getElementById('countdownTimer');
        const tpsFilter = document.getElementById('tpsFilter');
        const currentTPSElement = document.getElementById('currentTPS');
        
        // Event listener untuk TPS filter
        tpsFilter.addEventListener('change', function() {
            selectedTPS = this.value;
            
            // Update session via AJAX
            fetch('set_tps_filter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'tps=' + encodeURIComponent(selectedTPS)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update TPS info display
                    if (selectedTPS === 'all') {
                        currentTPSElement.textContent = 'Semua TPS';
                    } else {
                        currentTPSElement.textContent = 'TPS ' + selectedTPS;
                    }
                    
                    // Refresh data with new filter
                    refreshCurrentVoters();
                } else {
                    // Tampilkan pesan error
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-error';
                    alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${data.message || 'Gagal mengubah filter TPS'}</span>`;
                    
                    // Masukkan alert di awal container
                    const container = document.querySelector('.container');
                    container.insertBefore(alertDiv, container.firstChild);
                    
                    // Hapus alert setelah 5 detik
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error setting TPS filter:', error);
                
                // Tampilkan pesan error
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-error';
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>Terjadi kesalahan saat mengubah filter TPS</span>`;
                
                // Masukkan alert di awal container
                const container = document.querySelector('.container');
                container.insertBefore(alertDiv, container.firstChild);
                
                // Hapus alert setelah 5 detik
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            });
        });
        
        // Event listener untuk toggle auto-refresh
        autoRefreshToggle.addEventListener('change', function() {
            isAutoRefreshEnabled = this.checked;
            toggleStatus.textContent = isAutoRefreshEnabled ? 'Aktif' : 'Nonaktif';
            
            if (isAutoRefreshEnabled) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        // Fungsi untuk memulai auto-refresh
        function startAutoRefresh() {
            // Reset countdown
            countdownValue = 10;
            updateCountdownDisplay();
            
            // Hapus interval yang ada
            if (refreshInterval) clearInterval(refreshInterval);
            if (countdownInterval) clearInterval(countdownInterval);
            
            // Set interval untuk countdown
            countdownInterval = setInterval(() => {
                countdownValue--;
                updateCountdownDisplay();
                
                if (countdownValue <= 0) {
                    refreshCurrentVoters();
                    countdownValue = 10; // Reset countdown
                }
            }, 1000);
            
            // Set interval untuk refresh
            refreshInterval = setInterval(refreshCurrentVoters, 10000); // 10 detik
        }
        
        // Fungsi untuk menghentikan auto-refresh
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
            
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            
            countdownTimer.textContent = '--';
        }
        
        // Fungsi untuk update tampilan countdown
        function updateCountdownDisplay() {
            countdownTimer.textContent = countdownValue;
            
            // Animasi pulse saat countdown mendekati 0
            if (countdownValue <= 5) {
                countdownTimer.style.animation = 'pulse 1s infinite';
                countdownTimer.style.color = '#ff6b6b';
            } else {
                countdownTimer.style.animation = 'none';
                countdownTimer.style.color = 'white';
            }
        }
        
        // Fungsi untuk refresh data pemilih terkini
        function refreshCurrentVoters() {
            // Tampilkan loading
            loadingOverlay.classList.add('active');
            
            // Fetch data baru menggunakan AJAX dengan parameter TPS
            fetch('get_current_voters.php?tps=' + encodeURIComponent(selectedTPS))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Kosongkan tbody
                    currentVotersBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        // Tambahkan baris baru
                        data.forEach((voter, index) => {
                            const row = document.createElement('tr');
                            row.style.opacity = '0';
                            row.style.transform = 'translateY(10px)';
                            
                            const statusBadge = voter.status === 'sedang_diproses' 
                                ? `<span class="status-badge status-ready">
                                     <i class="fas fa-spinner fa-pulse"></i> Di Bilik
                                   </span>`
                                : `<span class="status-badge status-voted">
                                     <i class="fas fa-check-circle"></i> Selesai
                                   </span>`;
                            
                            row.innerHTML = `
                                <td>${voter.nim || '-'}</td>
                                <td>${voter.nama || '-'}</td>
                                <td>${voter.bilik_suara || '-'}</td>
                                <td>${voter.tps || '-'}</td>
                                <td>${statusBadge}</td>
                            `;
                            
                            currentVotersBody.appendChild(row);
                            
                            // Animasi fadeIn untuk baris baru
                            setTimeout(() => {
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '1';
                                row.style.transform = 'translateY(0)';
                            }, 50 * index);
                        });
                        
                        // Update jumlah pemilih
                        voterCountElement.textContent = `${data.length} pemilih aktif`;
                        
                        // Pastikan tabel info visible
                        const tableInfo = document.querySelector('#currentVotersTable .table-info');
                        if (tableInfo) {
                            tableInfo.style.display = 'flex';
                        }
                        
                        // Pastikan tabel wrapper visible
                        const tableWrapper = document.querySelector('#currentVotersTable .table-wrapper');
                        if (tableWrapper) {
                            tableWrapper.style.display = 'block';
                        }
                        
                        // Hapus empty state jika ada
                        const emptyState = document.querySelector('#currentVotersTable .empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }
                    } else {
                        // Tampilkan empty state
                        const tableContainer = document.getElementById('currentVotersTable');
                        const tableWrapper = tableContainer.querySelector('.table-wrapper');
                        const tableInfo = tableContainer.querySelector('.table-info');
                        
                        // Hapus tabel dan info jika ada
                        if (tableWrapper) tableWrapper.style.display = 'none';
                        if (tableInfo) tableInfo.style.display = 'none';
                        
                        // Hapus empty state lama jika ada
                        const oldEmptyState = tableContainer.querySelector('.empty-state');
                        if (oldEmptyState) oldEmptyState.remove();
                        
                        // Tambahkan empty state baru
                        const emptyState = document.createElement('div');
                        emptyState.className = 'empty-state';
                        
                        // Pesan empty state berdasarkan filter TPS
                        let emptyMessage = 'Tidak ada pemilih aktif saat ini';
                        if (selectedTPS !== 'all') {
                            emptyMessage = 'Tidak ada pemilih aktif di TPS ' + selectedTPS;
                        }
                        
                        emptyState.innerHTML = `
                            <i class="fas fa-user-clock"></i>
                            <p>${emptyMessage}</p>
                        `;
                        
                        tableContainer.appendChild(emptyState);
                    }
                    
                    // Update status refresh
                    const now = new Date();
                    const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                                      now.getMinutes().toString().padStart(2, '0') + ':' + 
                                      now.getSeconds().toString().padStart(2, '0');
                    refreshStatusElement.innerHTML = `<i class="fas fa-check-circle"></i> Terakhir update: ${timeString}`;
                    
                    // Reset countdown
                    countdownValue = 10;
                    updateCountdownDisplay();
                    
                    // Sembunyikan loading
                    setTimeout(() => {
                        loadingOverlay.classList.remove('active');
                    }, 100);
                })
                .catch(error => {
                    console.error('Error fetching current voters:', error);
                    loadingOverlay.classList.remove('active');
                    
                    // Tampilkan pesan error
                    refreshStatusElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> Gagal memperbarui data`;
                    refreshStatusElement.style.color = '#dc3545';
                    
                    // Reset countdown
                    countdownValue = 10;
                    updateCountdownDisplay();
                });
        }
        
        // Animasi untuk baris tabel saat pertama kali dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Mulai auto-refresh
            if (isAutoRefreshEnabled) {
                startAutoRefresh();
            }
        });
    </script>
</body>
</html>