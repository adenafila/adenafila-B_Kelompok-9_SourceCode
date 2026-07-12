<?php
// Fungsi untuk export hasil voting ke Excel dengan format HTML
function exportVotingResults($pdo) {
    // Get voting results
    $results = getVotingResults($pdo);
    
    // Start output buffering
    ob_start();
    
    // Set headers for download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Hasil_Voting_PEMIRA_2025.xls"');
    header('Cache-Control: max-age=0');
    
    // Summary data
    $totalVoters = getTotalVoters($pdo);
    $totalVotes = getTotalVotes($pdo);
    $participation = $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 2) : 0;
    
    // Output HTML content
    ?>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
            }
            th, td {
                border: 1px solid #333;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: #ff6600; /* Oranye terang - sangat mencolok */
                color: #000000; /* Teks hitam untuk kontras maksimal */
                font-weight: bold;
                font-size: 14px;
                border: 2px solid #333; /* Border lebih tebal */
            }
            .subheader {
                background-color: #ffcc00; /* Kuning terang */
                color: #000000; /* Teks hitam */
                font-weight: bold;
                font-size: 14px;
                border: 2px solid #333;
            }
            .total {
                background-color: #99ccff; /* Biru muda */
                font-weight: bold;
                color: #000000; /* Teks hitam */
                border: 2px solid #333;
            }
            .title {
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                background-color: #0066cc; /* Biru sedang */
                color: #ffffff; /* Teks putih */
                border: 2px solid #333;
                padding: 15px;
            }
            .section-title {
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                background-color: #ff6600; /* Oranye terang - sama dengan header */
                color: #000000; /* Teks hitam */
                border: 2px solid #333;
                padding: 12px;
            }
            .data-row {
                background-color: #ffffff; /* Putih bersih */
                color: #000000; /* Teks hitam */
            }
            .data-row:nth-child(even) {
                background-color: #f0f0f0; /* Abu-abu sangat terang */
            }
            .summary-row {
                background-color: #e6ffe6; /* Hijau muda */
                color: #000000; /* Teks hitam */
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td colspan="4" class="title">HASIL PEMILIHAN UMUM RAYA (PEMIRA) 2025</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: center; font-weight: bold; background-color: #f0f0f0;">Tanggal: <?php echo date('d F Y'); ?></td>
            </tr>
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            
            <!-- PRESIDEN RESULTS SECTION -->
            <tr>
                <td colspan="4" class="section-title">HASIL PEMILIHAN PRESIDEN MAHASISWA</td>
            </tr>
            <tr>
                <th>No</th>
                <th>Nama Kandidat</th>
                <th>Jumlah Suara</th>
                <th>Persentase</th>
            </tr>
            
            <?php
            $totalPresiden = array_sum(array_column($results['presiden'], 'total'));
            $no = 1;
            foreach ($results['presiden'] as $result) {
                $percentage = $totalPresiden > 0 ? round(($result['total'] / $totalPresiden) * 100, 2) : 0;
                echo "<tr class=\"data-row\">";
                echo "<td>" . $no . "</td>";
                echo "<td>" . htmlspecialchars($result['nama']) . "</td>";
                echo "<td>" . $result['total'] . "</td>";
                echo "<td>" . $percentage . "%</td>";
                echo "</tr>";
                $no++;
            }
            ?>
            
            <tr class="total">
                <td>TOTAL</td>
                <td></td>
                <td><?php echo $totalPresiden; ?></td>
                <td>100%</td>
            </tr>
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            
            <!-- DPM RESULTS SECTION -->
            <tr>
                <td colspan="5" class="section-title">HASIL PEMILIHAN DEWAN PERWAKILAN MAHASISWA</td>
            </tr>
            <tr>
                <th>No</th>
                <th>Jurusan</th>
                <th>Nama Kandidat</th>
                <th>Jumlah Suara</th>
                <th>Persentase</th>
            </tr>
            
            <?php
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
            foreach ($dpmByJurusan as $jurusan => $data) {
                $jurusanTotal = $data['total'];
                foreach ($data['candidates'] as $candidate) {
                    $percentage = $jurusanTotal > 0 ? round(($candidate['total'] / $jurusanTotal) * 100, 2) : 0;
                    echo "<tr class=\"data-row\">";
                    echo "<td>" . $no . "</td>";
                    echo "<td>" . htmlspecialchars($jurusan) . "</td>";
                    echo "<td>" . htmlspecialchars($candidate['nama']) . "</td>";
                    echo "<td>" . $candidate['total'] . "</td>";
                    echo "<td>" . $percentage . "%</td>";
                    echo "</tr>";
                    $no++;
                }
                
                // Total row for each jurusan
                echo "<tr class=\"total\">";
                echo "<td></td>";
                echo "<td>TOTAL " . htmlspecialchars($jurusan) . "</td>";
                echo "<td></td>";
                echo "<td>" . $jurusanTotal . "</td>";
                echo "<td>100%</td>";
                echo "</tr>";
            }
            ?>
            
            <tr>
                <td colspan="5">&nbsp;</td>
            </tr>
            
            <!-- SUMMARY SECTION -->
            <tr>
                <td colspan="2" class="section-title">RINGKASAN</td>
            </tr>
            <tr class="summary-row">
                <td>Total Pemilih Terdaftar</td>
                <td><?php echo $totalVoters; ?></td>
            </tr>
            <tr class="summary-row">
                <td>Total Suara Masuk</td>
                <td><?php echo $totalVotes; ?></td>
            </tr>
            <tr class="summary-row">
                <td>Tingkat Partisipasi</td>
                <td><?php echo $participation; ?>%</td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    
    // Get the buffered content and clean the buffer
    $content = ob_get_clean();
    
    // Output the content
    echo $content;
    exit;
}

// Helper functions tetap sama
function getTotalVoters($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'pemilih'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}
function getTotalVotes($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}
?>