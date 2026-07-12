<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get TPS filter from parameter
$tpsFilter = $_GET['tps'] ?? 'all';

// Get current voters with TPS filter
$currentVoters = getCurrentVoters($pdo);

// Filter by TPS if not 'all'
if ($tpsFilter !== 'all') {
    $filteredVoters = array_filter($currentVoters, function($voter) use ($tpsFilter) {
        return $voter['tps'] == $tpsFilter;
    });
} else {
    $filteredVoters = $currentVoters;
}

// Re-index array to ensure JSON encodes correctly
$filteredVoters = array_values($filteredVoters);

echo json_encode($filteredVoters);