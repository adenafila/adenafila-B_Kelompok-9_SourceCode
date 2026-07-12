<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../config/database.php';
require_once '../includes/functions.php';
 $user = getUserData($pdo, $_SESSION['user_id']);
// Debugging - Tampilkan jumlah pemilih yang tersedia
 $stmt = $pdo->query("
    SELECT u.id, u.nama, u.nim, j.nama_jurusan
    FROM users u
    JOIN jurusan j ON u.kode_jurusan = j.kode_jurusan
    WHERE u.role = 'pemilih'
    AND u.has_voted = 0
    AND u.id NOT IN (SELECT id_user FROM voting_sessions WHERE status = 'sedang_diproses')
");
 $availableVoters = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Debugging - Tampilkan jumlah pemilih yang sedang aktif
 $currentVoters = getCurrentVoters($pdo);
 $pageTitle = "Dashboard Admin";
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
            --table-height: 400px; /* Tinggi tabel yang lebih wajar */
            --table-min-width: 700px;
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
        
        /* Dashboard Container */
        .dashboard-wrapper {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
            width: 100%;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease;
        }
        
        .dashboard-header h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .dashboard-header p {
            font-size: 1.1rem;
            color: var(--text-muted);
        }
        
        /* Grid layout untuk cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(650px, 1fr));
            gap: 2rem;
            animation: fadeIn 1s ease;
            /* Hapus tinggi fixed agar bisa menyesuaikan konten */
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
            min-height: 600px; /* Beri tinggi minimum yang cukup */
        }
        
        .card:nth-child(1) { animation-delay: 0.2s; }
        .card:nth-child(2) { animation-delay: 0.4s; }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .card-header {
            padding: 1.5rem;
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
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
            color: #ffffff !important; /* Memastikan warna putih dengan !important */
        }
        
        .card-title i {
            color: #ffffff !important; /* Memastikan ikon juga berwarna putih */
        }
        
        .refresh-info {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            position: relative;
            z-index: 1;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9) !important; /* Memastikan warna teks */
        }
        
        .refresh-info i {
            margin-right: 8px;
            color: var(--accent-color);
        }
        
        .refresh-status {
            display: flex;
            align-items: center;
            margin-left: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85) !important; /* Memastikan warna teks status */
        }
        
        .refresh-status i {
            color: var(--accent-color);
            margin-right: 5px;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.9) !important; /* Memastikan warna teks toggle */
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
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
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .countdown-container {
            display: flex;
            align-items: center;
            margin-left: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85) !important; /* Memastikan warna teks countdown */
        }
        
        .countdown-timer {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 5px;
            font-weight: 600;
            color: #ffffff !important; /* Memastikan timer berwarna putih */
            min-width: 24px;
            text-align: center;
        }
        
        .card-body {
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Ini penting untuk flexbox */
        }
        
        /* Table styling */
        .table-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Ini penting untuk flexbox */
        }
        
        .table-wrapper {
            overflow-x: auto;
            position: relative;
            flex: 1;
            min-height: 0; /* Ini penting untuk flexbox */
        }
        
        .table-scroll {
            height: var(--table-height); /* Gunakan variabel tinggi tabel */
            overflow-y: auto;
            position: relative;
        }
        
        /* Custom scrollbar */
        .table-scroll::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
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
            width: 40px;
            height: 40px;
            border: 4px solid rgba(12, 79, 106, 0.1);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        table {
            width: 100%;
            min-width: var(--table-min-width);
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        table th {
            background-color: var(--light-bg);
            color: var(--primary-color);
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }
        
        table td {
            padding: 1rem;
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
            padding: 0.75rem 1rem;
            background-color: var(--light-bg);
            border-top: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .record-count {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Button styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(12, 79, 106, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(12, 79, 106, 0.3);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
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
            padding: 3rem 1.5rem;
            color: var(--text-muted);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Debugging styles */
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.85rem;
            color: #495057;
        }
        
        /* Ikon sorting */
        .sort-icon {
            margin-left: 8px;
            cursor: pointer;
            opacity: 0.5;
            transition: all 0.2s ease;
        }
        
        .sort-icon:hover {
            opacity: 1;
        }
        
        .sort-icon.active {
            opacity: 1;
            color: var(--accent-color);
        }
        
        .sort-icon.asc {
            transform: rotate(180deg);
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
        @media (max-width: 1400px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            }
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            :root {
                --table-min-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            :root {
                --table-height: 350px; /* Tinggi tabel lebih kecil di mobile */
                --table-min-width: 100%;
            }
            
            .dashboard-wrapper {
                padding: 1.5rem 1rem;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .card-header {
                padding: 1.2rem;
            }
            
            .card-title {
                font-size: 1.3rem;
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
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            :root {
                --table-height: 300px; /* Tinggi tabel lebih kecil di mobile kecil */
                --table-min-width: 100%;
            }
            
            .dashboard-header {
                margin-bottom: 1.5rem;
            }
            
            .dashboard-header h1 {
                font-size: 1.8rem;
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
    
    <div class="dashboard-wrapper">
        <div class="dashboard-grid">
            <!-- Card Pemilih Tersedia -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i>
                        <span>Daftar Pemilih Tersedia</span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <?php if (count($availableVoters) > 0): ?>
                            <div class="table-wrapper">
                                <div class="table-scroll">
                                    <table id="availableVotersTable">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <i class="fas fa-id-card"></i> NIM 
                                                    <i class="fas fa-sort sort-icon" data-column="nim"></i>
                                                </th>
                                                <th>
                                                    <i class="fas fa-user"></i> Nama 
                                                    <i class="fas fa-sort sort-icon" data-column="nama"></i>
                                                </th>
                                                <th>
                                                    <i class="fas fa-graduation-cap"></i> Jurusan 
                                                    <i class="fas fa-sort sort-icon" data-column="nama_jurusan"></i>
                                                </th>
                                                <th><i class="fas fa-cog"></i> Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="availableVotersBody">
                                            <?php foreach ($availableVoters as $voter): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($voter['nim']); ?></td>
                                                <td><?php echo htmlspecialchars($voter['nama']); ?></td>
                                                <td><?php echo htmlspecialchars($voter['nama_jurusan']); ?></td>
                                                <td>
                                                    <a href="register_voter.php?id=<?php echo htmlspecialchars($voter['id']); ?>" class="btn">
                                                        <i class="fas fa-user-plus"></i> Daftarkan
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="table-info">
                                <div class="record-count">
                                    <i class="fas fa-list"></i>
                                    <span id="availableVoterCount"><?php echo count($availableVoters); ?> pemilih tersedia</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>Tidak ada pemilih tersedia saat ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Card Pemilih Terkini -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-clock"></i>
                        <span>Pemilih Terkini</span>
                    </h2>
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
                        <i class="fas fa-check-circle"></i>
                        <span id="refreshStatus">Terakhir update: sekarang</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container" id="currentVotersTable">
                        <div class="loading-overlay" id="loadingOverlay">
                            <div class="loading-spinner"></div>
                        </div>
                        
                        <?php if (count($currentVoters) > 0): ?>
                            <div class="table-wrapper">
                                <div class="table-scroll">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-id-card"></i> NIM</th>
                                                <th><i class="fas fa-user"></i> Nama</th>
                                                <th><i class="fas fa-door-open"></i> Bilik <i class="fas fa-sort sort-icon" data-column="bilik_suara"></i></th>
                                                <th><i class="fas fa-map-marker-alt"></i> TPS <i class="fas fa-sort sort-icon" data-column="tps"></i></th>
                                                <th><i class="fas fa-info-circle"></i> Status <i class="fas fa-sort sort-icon" data-column="status"></i></th>
                                                <th><i class="fas fa-clock"></i> Waktu <i class="fas fa-sort sort-icon active" data-column="waktu_masuk"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody id="currentVotersBody">
                                            <?php foreach ($currentVoters as $voter): ?>
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
                                                <td>
                                                    <?php 
                                                    $waktuMasuk = new DateTime($voter['waktu_masuk']);
                                                    echo $waktuMasuk->format('H:i:s');
                                                    ?>
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
                                    <span id="voterCount"><?php echo count($currentVoters); ?> pemilih aktif</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-clock"></i>
                                <p>Tidak ada pemilih aktif saat ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Variabel global
        let refreshInterval;
        let countdownInterval;
        let countdownValue = 10;
        let isAutoRefreshEnabled = true;
        let sortColumn = 'waktu_masuk';
        let sortDirection = 'desc'; // 'asc' atau 'desc'
        
        // Variabel untuk sorting tabel pemilih tersedia
        let availableSortColumn = 'nim';
        let availableSortDirection = 'asc';
        
        // Elemen DOM
        const refreshStatusElement = document.getElementById('refreshStatus');
        const currentVotersBody = document.getElementById('currentVotersBody');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const voterCountElement = document.getElementById('voterCount');
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        const toggleStatus = document.getElementById('toggleStatus');
        const countdownTimer = document.getElementById('countdownTimer');
        const availableVotersBody = document.getElementById('availableVotersBody');
        const availableVoterCount = document.getElementById('availableVoterCount');
        
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
        
        // Event listener untuk tombol sort di tabel pemilih terkini
        document.querySelectorAll('#currentVotersTable .sort-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                
                // Jika kolom yang sama diklik, toggle arah
                if (sortColumn === column) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = column;
                    sortDirection = 'asc'; // Default ascending untuk kolom baru
                }
                
                // Update tampilan ikon sort
                updateSortIcons('#currentVotersTable');
                
                // Refresh data dengan parameter sorting
                refreshCurrentVotersWithSort();
            });
        });
        
        // Event listener untuk tombol sort di tabel pemilih tersedia
        document.querySelectorAll('#availableVotersTable .sort-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                
                // Jika kolom yang sama diklik, toggle arah
                if (availableSortColumn === column) {
                    availableSortDirection = availableSortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    availableSortColumn = column;
                    availableSortDirection = 'asc'; // Default ascending untuk kolom baru
                }
                
                // Update tampilan ikon sort
                updateSortIcons('#availableVotersTable');
                
                // Sort data di sisi klien
                sortAvailableVoters();
            });
        });
        
        // Fungsi untuk update tampilan ikon sort
        function updateSortIcons(tableSelector) {
            document.querySelectorAll(`${tableSelector} .sort-icon`).forEach(icon => {
                const column = icon.getAttribute('data-column');
                
                // Reset semua ikon
                icon.className = 'fas fa-sort sort-icon';
                
                // Set ikon untuk kolom yang aktif
                const currentSortColumn = tableSelector === '#currentVotersTable' ? sortColumn : availableSortColumn;
                const currentSortDirection = tableSelector === '#currentVotersTable' ? sortDirection : availableSortDirection;
                
                if (column === currentSortColumn) {
                    icon.className = currentSortDirection === 'asc' 
                        ? 'fas fa-sort-down sort-icon active asc' 
                        : 'fas fa-sort-down sort-icon active';
                }
            });
        }
        
        // Fungsi untuk sorting tabel pemilih tersedia di sisi klien
        function sortAvailableVoters() {
            // Ambil semua baris dari tbody
            const rows = Array.from(availableVotersBody.querySelectorAll('tr'));
            
            // Sort baris berdasarkan kolom dan arah
            rows.sort((a, b) => {
                let aValue, bValue;
                
                // Ambil nilai berdasarkan kolom
                switch(availableSortColumn) {
                    case 'nim':
                        aValue = a.cells[0].textContent.trim();
                        bValue = b.cells[0].textContent.trim();
                        break;
                    case 'nama':
                        aValue = a.cells[1].textContent.trim();
                        bValue = b.cells[1].textContent.trim();
                        break;
                    case 'nama_jurusan':
                        aValue = a.cells[2].textContent.trim();
                        bValue = b.cells[2].textContent.trim();
                        break;
                    default:
                        return 0;
                }
                
                // Bandingkan nilai
                if (availableSortColumn === 'nim') {
                    // Untuk NIM, bandingkan sebagai string atau angka tergantung format
                    const aNum = parseInt(aValue);
                    const bNum = parseInt(bValue);
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return availableSortDirection === 'asc' ? aNum - bNum : bNum - aNum;
                    }
                }
                
                // Untuk teks, gunakan localeCompare
                if (availableSortDirection === 'asc') {
                    return aValue.localeCompare(bValue, 'id-ID');
                } else {
                    return bValue.localeCompare(aValue, 'id-ID');
                }
            });
            
            // Kosongkan tbody
            availableVotersBody.innerHTML = '';
            
            // Tambahkan baris yang sudah diurutkan
            rows.forEach((row, index) => {
                // Tambahkan animasi
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                availableVotersBody.appendChild(row);
                
                // Animasi fadeIn untuk baris baru
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 50 * index);
            });
        }
        
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
                    refreshCurrentVotersWithSort();
                    countdownValue = 10; // Reset countdown
                }
            }, 1000);
            
            // Set interval untuk refresh
            refreshInterval = setInterval(refreshCurrentVotersWithSort, 10000);
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
            if (countdownValue <= 3) {
                countdownTimer.style.animation = 'pulse 1s infinite';
                countdownTimer.style.color = '#ff6b6b';
            } else {
                countdownTimer.style.animation = 'none';
                countdownTimer.style.color = 'white';
            }
        }
        
        // Fungsi untuk refresh data pemilih terkini dengan sorting
        function refreshCurrentVotersWithSort() {
            // Tampilkan loading
            loadingOverlay.classList.add('active');
            
            // Fetch data baru menggunakan AJAX dengan parameter sorting
            fetch(`get_current_voters.php?sort=${sortColumn}&direction=${sortDirection}`)
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
                            
                            // Format waktu
                            const waktuMasuk = new Date(voter.waktu_masuk);
                            const formattedTime = waktuMasuk.toLocaleTimeString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            });
                            
                            const statusBadge = voter.status === 'sedang_diproses' 
                                ? `<span class="status-badge status-ready">
                                     <i class="fas fa-spinner fa-pulse"></i> Di Bilik
                                   </span>`
                                : `<span class="status-badge status-voted">
                                     <i class="fas fa-check-circle"></i> Selesai
                                   </span>`;
                            
                            row.innerHTML = `
                                <td>${voter.nim}</td>
                                <td>${voter.nama}</td>
                                <td>${voter.bilik_suara}</td>
                                <td>${voter.tps}</td>
                                <td>${statusBadge}</td>
                                <td>${formattedTime}</td>
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
                        emptyState.innerHTML = `
                            <i class="fas fa-user-clock"></i>
                            <p>Tidak ada pemilih aktif saat ini</p>
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
                    }, 300);
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
            // Animasi untuk tabel pemilih tersedia
            const availableRows = document.querySelectorAll('#availableVotersBody tr');
            availableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Animasi untuk tabel pemilih terkini
            const currentRows = document.querySelectorAll('#currentVotersBody tr');
            currentRows.forEach((row, index) => {
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