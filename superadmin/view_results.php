<?php
require_once '../includes/auth.php';
requireRole('superadmin');
require_once '../config/database.php';
require_once '../includes/functions.php';
$user = getUserData($pdo, $_SESSION['user_id']);
// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once '../includes/excel_export.php';
    exportVotingResults($pdo);
}
// Get voting results
$results = getVotingResults($pdo);
// Get summary data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'pemilih'");
$totalVoters = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
$totalVotes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$participation = $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 2) : 0;
$pageTitle = "Hasil Pemilihan";
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for results page */
        .results-container {
            display: grid;
            gap: 2rem;
        }
        
        .results-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .results-card h3 {
            color: #0c4f6a;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #0c4f6a;
        }
        
        .export-btn {
            background-color: #0c4f6a;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-bottom: 2rem;
        }
        
        .export-btn:hover {
            background-color: #093d50;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-align: center;
            border-top: 4px solid #0c4f6a;
        }
        
        .summary-card h3 {
            margin-top: 0;
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .summary-card p {
            margin-bottom: 0;
            font-size: 2rem;
            font-weight: bold;
            color: #0c4f6a;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        table th {
            background-color: #ff6600; /* Oranye terang */
            color: #000000; /* Hitam untuk kontras maksimal */
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase; /* Huruf kapital semua */
            letter-spacing: 1px; /* Jarak antar huruf */
        }
        
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .total-row {
            background-color: #e8f4f8 !important;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #0c4f6a;
            border-bottom: 2px solid #0c4f6a;
        }
        
        .section-header {
            background-color: #fdbb2d;
            color: #000000; /* Hitam untuk kontras */
            font-weight: bold;
            text-align: center;
            text-transform: uppercase; /* Huruf kapital semua */
            letter-spacing: 1px; /* Jarak antar huruf */
        }
        
        .section-header th {
            background-color: #fdbb2d;
            color: #000000; /* Hitam untuk kontras */
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Hasil Pemilihan</h1>
        
        <div class="card-actions">
            <a href="?export=excel" class="export-btn">Export ke Excel</a>
        </div>
        
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Pemilih</h3>
                <p><?php echo $totalVoters; ?></p>
            </div>
            <div class="summary-card">
                <h3>Suara Masuk</h3>
                <p><?php echo $totalVotes; ?></p>
            </div>
            <div class="summary-card">
                <h3>Partisipasi</h3>
                <p><?php echo $participation; ?>%</p>
            </div>
        </div>
        
        <div class="results-container">
            <div class="results-card">
                <h3>Hasil Pemilihan Presiden Mahasiswa</h3>
                <table>
                    <tr>
                        <th>No</th>
                        <th>Nama Kandidat</th>
                        <th>Jumlah Suara</th>
                        <th>Persentase</th>
                    </tr>
                    <?php 
                    $totalPresiden = array_sum(array_column($results['presiden'], 'total'));
                    foreach ($results['presiden'] as $result): 
                        $percentage = $totalPresiden > 0 ? round(($result['total'] / $totalPresiden) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo array_search($result, $results['presiden']) + 1; ?></td>
                        <td><?php echo $result['nama']; ?></td>
                        <td><?php echo $result['total']; ?></td>
                        <td><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td><?php echo $totalPresiden; ?></td>
                        <td>100%</td>
                    </tr>
                </table>
            </div>
            
            <div class="results-card">
                <h3>Hasil Pemilihan Dewan Perwakilan Mahasiswa</h3>
                <table>
                    <tr>
                        <th>No</th>
                        <th>Jurusan</th>
                        <th>Nama Kandidat</th>
                        <th>Jumlah Suara</th>
                        <th>Persentase</th>
                    </tr>
                    <?php 
                    // Group by jurusan
                    $dpmByJurusan = [];
                    foreach ($results['dpm'] as $result) {
                        if (!isset($dpmByJurusan[$result['nama_jurusan']])) {
                            $dpmByJurusan[$result['nama_jurusan']] = [
                                'total' => 0,
                                'candidates' => []
                            ];
                        }
                        $dpmByJurusan[$result['nama_jurusan']]['candidates'][] = $result;
                        $dpmByJurusan[$result['nama_jurusan']]['total'] += $result['total'];
                    }
                    
                    $no = 1;
                    foreach ($dpmByJurusan as $jurusan => $data): 
                        $jurusanTotal = $data['total'];
                    ?>
                        <?php foreach ($data['candidates'] as $candidate): 
                            $percentage = $jurusanTotal > 0 ? round(($candidate['total'] / $jurusanTotal) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $no; ?></td>
                            <td><?php echo $jurusan; ?></td>
                            <td><?php echo $candidate['nama']; ?></td>
                            <td><?php echo $candidate['total']; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                        <?php 
                        $no++;
                        endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2">TOTAL <?php echo $jurusan; ?></td>
                            <td><?php echo $jurusanTotal; ?></td>
                            <td>100%</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>