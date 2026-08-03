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
    // Gesamtanzahl der Anforderungen & Ziele
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ?");
    $stmt1->execute([$project_id]);
    $total = $stmt1->fetchColumn();

    // Warten auf Überprüfung
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND review_status = 'Wartet auf Überprüfung'");
    $stmt2->execute([$project_id]);
    $waiting = $stmt2->fetchColumn();

    // Freigegeben
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND review_status = 'Geprüft & Freigegeben'");
    $stmt3->execute([$project_id]);
    $approved = $stmt3->fetchColumn();

    echo json_encode([
        'success' => true, 
        'kpis' => [
            'total' => $total, 
            'waiting' => $waiting, 
            'approved' => $approved
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>