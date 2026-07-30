<?php
// api/push.php
header('Content-Type: application/json');
require '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$project_id = $input['project_id'] ?? '';
$updates = $input['updates'] ?? []; // Array mit geänderten Requirements
$evidences = $input['evidences'] ?? []; // Array mit Evidenzen

// User checken
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Requirements Status updaten
    $updReq = $pdo->prepare("UPDATE requirements SET status = ? WHERE req_key = ? AND project_id = ?");
    foreach ($updates as $req) {
        $updReq->execute([$req['status'], $req['req_key'], $project_id]);
    }

    // 2. Evidenzen speichern (Hardware-Tests, Konsolen-Outputs)
    $insEvd = $pdo->prepare("INSERT INTO evidences (requirement_id, project_id, console_output, uploaded_by) VALUES ((SELECT id FROM requirements WHERE req_key = ? AND project_id = ?), ?, ?, ?)");
    
    foreach ($evidences as $evd) {
        // Hinweis: Wenn Bilder als Base64 gesendet werden, speichert man diese
        // idealerweise hier auf der Festplatte und trägt nur den Pfad in die DB ein.
        // Für Konsolen-Outputs schreiben wir sie direkt in die DB:
        $insEvd->execute([
            $evd['req_key'], 
            $project_id, 
            $project_id, 
            $evd['console_output'] ?? '', 
            $user['id']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Push erfolgreich. Daten synchronisiert.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Fehler beim Push: ' . $e->getMessage()]);
}
?>