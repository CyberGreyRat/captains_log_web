<?php
error_reporting(0);
@ini_set('display_errors', 0);
session_start();

require_once '../config/db.php';
$project_id = $_GET['project_id'] ?? null;

if (!$project_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Keine project_id übergeben.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT sbom_data FROM project_sboms WHERE project_id = ? ORDER BY id DESC LIMIT 1"); 
    // Korrektur falls Spalte sbom_data heißt:
    $stmt = $pdo->prepare("SELECT sbom_data FROM project_sboms WHERE project_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$project_id]);
    $sbom = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sbom && !empty($sbom['sbom_data'])) {
        // Da es bereits valides JSON ist, direkt ausgeben -> Verhindert Escaping-Kollapse & 500er Fehler
        header('Content-Type: application/json; charset=utf-8');
        echo $sbom['sbom_data'];
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Keine SBOM gefunden.']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}