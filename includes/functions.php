<?php
function logActivity($pdo, $userId, $activity) {
    $stmt = $pdo->prepare("INSERT INTO logs (id_user, aktivitas) VALUES (?, ?)");
    $stmt->execute([$userId, $activity]);
}

function getJurusanName($pdo, $kodeJurusan) {
    $stmt = $pdo->prepare("SELECT nama_jurusan FROM jurusan WHERE kode_jurusan = ?");
    $stmt->execute([$kodeJurusan]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['nama_jurusan'];
}

function extractJurusanCode($nim) {
    return substr($nim, 4, 2);
}

function getVotingResults($pdo) {
    // Presiden results
    $stmt = $pdo->query("
        SELECT k.nama, COUNT(v.id_presiden) as total
        FROM kandidat k
        LEFT JOIN votes v ON k.id = v.id_presiden
        WHERE k.tipe = 'presiden'
        GROUP BY k.id
    ");
    $presidenResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DPM results per jurusan
    $stmt = $pdo->query("
        SELECT j.nama_jurusan, k.nama, COUNT(v.id_dpm) as total
        FROM kandidat k
        JOIN jurusan j ON k.kode_jurusan = j.kode_jurusan
        LEFT JOIN votes v ON k.id = v.id_dpm
        WHERE k.tipe = 'dpm'
        GROUP BY j.kode_jurusan, k.id
        ORDER BY j.nama_jurusan, k.nama
    ");
    $dpmResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'presiden' => $presidenResults,
        'dpm' => $dpmResults
    ];
}

function getCurrentVoters($pdo) {
    $stmt = $pdo->query("
        SELECT vs.id, u.nama, u.nim, vs.bilik_suara, vs.tps, vs.status
        FROM voting_sessions vs
        JOIN users u ON vs.id_user = u.id
        ORDER BY vs.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveUsers($pdo) {
    $stmt = $pdo->query("
        SELECT u.nama, u.role, u.last_login 
        FROM users u 
        WHERE u.role IN ('admin', 'superadmin') 
        AND u.last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>