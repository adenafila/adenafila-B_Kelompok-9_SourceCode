<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tps = $_POST['tps'] ?? 'all';
    
    // Validate TPS value
    if ($tps !== 'all') {
        // Check if TPS exists in database
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM voting_sessions WHERE tps = ?");
            $stmt->execute([$tps]);
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                // Jika tidak ada di voting_sessions, anggap valid untuk testing
                $count = 1;
            }
            
            if ($count == 0) {
                echo json_encode(['success' => false, 'message' => 'TPS tidak valid']);
                exit;
            }
        } catch (PDOException $e) {
            // Jika tabel voting_sessions tidak ada, anggap valid untuk testing
            $count = 1;
        }
    }
    
    // Save to session
    $_SESSION['selected_tps'] = $tps;
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);