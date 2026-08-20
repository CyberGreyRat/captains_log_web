<?php
// api/verify_criterion.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php'; header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$req_id = $data['req_id'] ?? null;
$idx = $data['criterion_idx'] ?? null;
$note = $data['note'] ?? '';

$user_id = $_SESSION['user_id'] ?? 1;
$user = $_SESSION['username'] ?? 'admin';
$hostname = $_SESSION['hostname'] ?? 'LocalPC';

if (!$req_id || $idx === null) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Daten']);
    exit;
}

try {
    set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));

    $pdo->beginTransaction();

    // 1. Aktuelle Kriterien laden
    $stmt = $pdo->prepare("SELECT * FROM requirements WHERE id = ?");
    $stmt->execute([$req_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!isset($req)) throw new Exception("Element nicht gefunden");

    $attrs = json_decode($req['attributes'] ?: '{}', true);
    if (!isset($attrs['criteria_states'])) $attrs['criteria_states'] = [];

    // 2. Kriterium abhaken
    $attrs['criteria_states'][$idx] = [
        'checked' => true,
        'by' => $user,
        'date' => date('d.m.Y H:i'),
        'note' => $note
    ];

    // 3. Prüfen: Sind ALLE Kriterien abgehakt?
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

    $update_stmt = $pdo->prepare("UPDATE requirements SET attributes = ? WHERE id = ?");
    $update_stmt->execute([json_encode($attrs), $req_id]);

    $auto_approved = false;
    if ($valid_count > 0 && $valid_count === $checked_count) {
        // Status auf Freigegeben setzen
        $status_stmt = $pdo->prepare("UPDATE requirements SET review_status = 'Geprüft & Freigegeben' WHERE id = ?");
        $status_stmt->execute([$req_id]);
        $auto_approved = true;

        // WICHTIG: Historien-Eintrag für die Performance-Analyse schreiben!
        $histStmt = $pdo->prepare("INSERT INTO requirement_history (requirement_id, req_key, project_id, type, title, description, rationale, status, parents, children, modified_by, action, attributes, hostname) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $histStmt->execute([
            $req['id'], $req['req_key'], $req['project_id'], $req['type'], 
            $req['title'], $req['description'], $req['rationale'], 'Geprüft & Freigegeben', 
            $req['parents'], $req['children'], $user_id, 'Automatisch freigegeben durch vollständige Kriterien-Prüfung', 
            json_encode($attrs), $hostname
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'auto_approved' => $auto_approved]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
