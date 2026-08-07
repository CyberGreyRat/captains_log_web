<?php
// api/delete_requirement.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        throw new Exception("Keine ID übergeben.");
    }

    // 1. Zuerst die Historie zu diesem Element sauber entfernen (verhindert Foreign-Key Konflikte)
    $stmtHist = $pdo->prepare("DELETE FROM requirement_history WHERE requirement_id = ?");
    $stmtHist->execute([$id]);

    // 2. Dann das eigentliche Element löschen
    $stmtReq = $pdo->prepare("DELETE FROM requirements WHERE id = ?");
    $stmtReq->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}