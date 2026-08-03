<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$req_id = $data['req_id'] ?? null;
$idx = $data['criterion_idx'] ?? null;
$note = $data['note'] ?? '';
$user = $_SESSION['username'] ?? 'admin'; // Oder wie dein User heißt

if (!$req_id || $idx === null) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Daten']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Aktuelle Kriterien laden
    $stmt = $pdo->prepare("SELECT acceptance_criteria, attributes FROM requirements WHERE id = ?");
    $stmt->execute([$req_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) throw new Exception("Element nicht gefunden");

    $attrs = json_decode($req['attributes'] ?: '{}', true);
    if (!isset($attrs['criteria_states'])) $attrs['criteria_states'] = [];

    // Kriterium abhaken
    $attrs['criteria_states'][$idx] = [
        'checked' => true,
        'by' => $user,
        'date' => date('d.m.Y H:i'),
        'note' => $note
    ];

    // PRÜFUNG: Sind jetzt ALLE Kriterien abgehakt?
    $raw_lines = explode("\n", $req['acceptance_criteria']);
    $valid_count = 0;
    $checked_count = 0;

    foreach ($raw_lines as $i => $line) {
        if (trim(preg_replace('/^-\s*/', '', $line)) !== '') {
            $valid_count++;
            if (isset($attrs['criteria_states'][$i]['checked']) && $attrs['criteria_states'][$i]['checked']) {
                $checked_count++;
            }
        }
    }

    // JSON updaten
    $update_stmt = $pdo->prepare("UPDATE requirements SET attributes = ? WHERE id = ?");
    $update_stmt->execute([json_encode($attrs), $req_id]);

    // AUTO-FREIGABE, wenn alle validen Kriterien geprüft sind
    $auto_approved = false;
    if ($valid_count > 0 && $valid_count === $checked_count) {
        $status_stmt = $pdo->prepare("UPDATE requirements SET review_status = 'Geprüft & Freigegeben' WHERE id = ?");
        $status_stmt->execute([$req_id]);
        $auto_approved = true;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'auto_approved' => $auto_approved]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>