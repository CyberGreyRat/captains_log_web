<?php
// api/delete_requirement.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) throw new Exception("Keine ID übergeben.");

    $pdo->beginTransaction();
    
    // 1. Historie restlos entfernen
    $pdo->prepare("DELETE FROM requirement_history WHERE requirement_id = ?")->execute([$data['id']]);
    
    // 2. Audit-Log bereinigen (damit keine Geister-Einträge bleiben)
    $pdo->prepare("DELETE FROM audit_log WHERE entity_type = 'requirement' AND entity_id = ?")->execute([$data['id']]);
    
    // 3. Die eigentliche Anforderung löschen
    $pdo->prepare("DELETE FROM requirements WHERE id = ?")->execute([$data['id']]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>