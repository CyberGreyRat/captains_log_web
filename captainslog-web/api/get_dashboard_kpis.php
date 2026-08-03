<?php // api/get_dashboard_kpis.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, req_key, title, review_status, type FROM requirements WHERE project_id = ? ORDER BY req_key ASC");
    $stmt->execute([$project_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $waiting = array_values(array_filter($all, function($r) { return $r['review_status'] === 'Wartet auf Überprüfung'; }));
    $approved = array_values(array_filter($all, function($r) { return $r['review_status'] === 'Geprüft & Freigegeben'; }));
    
    // Neu: Filter für Risk und Sec
    $risks = array_values(array_filter($all, function($r) { return $r['type'] === 'RISK'; }));
    $sec = array_values(array_filter($all, function($r) { return $r['type'] === 'SEC'; }));

    echo json_encode([
        'success' => true, 
        'kpis' => [
            'total' => ['count' => count($all), 'items' => $all], 
            'waiting' => ['count' => count($waiting), 'items' => $waiting], 
            'approved' => ['count' => count($approved), 'items' => $approved],
            'risks' => ['count' => count($risks), 'items' => $risks],
            'sec' => ['count' => count($sec), 'items' => $sec]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>