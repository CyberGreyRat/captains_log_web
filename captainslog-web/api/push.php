<?php
// api/push.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$project_id = $input['project_id'] ?? '';
$updates = $input['updates'] ?? []; 
$evidences = $input['evidences'] ?? []; 

// User über Token validieren
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $user['id'];
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $user['username'] ?? 'API-Nutzer';
$action_logs = [];

try {
    set_audit_context($pdo, 'api', basename($_SERVER['SCRIPT_NAME']));

    $pdo->beginTransaction();

    $clean = function($txt) {
        $txt = trim($txt ?? '');
        return $txt ?: 'Leer';
    };

    $fmt_arr = function($arr) {
        $arr = is_array($arr) ? $arr : [];
        return empty($arr) ? '-' : implode(', ', $arr);
    };

    $stmtOld = $pdo->prepare("SELECT * FROM requirements WHERE req_key = ? AND project_id = ?");
    
    $stmtUpd = $pdo->prepare("
        UPDATE requirements 
        SET title = ?, description = ?, rationale = ?, status = ?, review_status = ?, parents = ?, children = ?, attributes = ? 
        WHERE req_key = ? AND project_id = ?
    ");

    $stmtIns = $pdo->prepare("
        INSERT INTO requirements 
        (project_id, req_key, type, title, description, rationale, status, review_status, parents, children, attributes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $histStmt = $pdo->prepare("
        INSERT INTO requirement_history 
        (requirement_id, req_key, project_id, type, title, description, rationale, status, parents, children, modified_by, action, attributes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($updates as $req) {
        $req_key = $req['req_key'] ?? $req['uid'] ?? null;
        if (!$req_key) continue;

        // 1. Zuerst schauen, ob es den Datensatz schon gibt
        $stmtOld->execute([$req_key, $project_id]);
        $old_row = $stmtOld->fetch(PDO::FETCH_ASSOC);

        // 2. Werte setzen (mit Fallback auf die alten DB-Werte, falls die CLI nichts mitschickt)
        $title = $req['title'] ?? ($old_row['title'] ?? '');
        $description = $req['description'] ?? $req['text'] ?? ($old_row['description'] ?? '');
        $rationale = $req['rationale'] ?? ($old_row['rationale'] ?? '');
        $status = $req['status'] ?? ($old_row['status'] ?? 'Entwurf');
        $review_status = $req['review_status'] ?? ($old_row['review_status'] ?? 'Neu');
        $type = $req['type'] ?? ($old_row['type'] ?? 'req');

        $parents_array = isset($req['parents']) && is_array($req['parents']) ? $req['parents'] : [];
        $children_array = isset($req['children']) && is_array($req['children']) ? $req['children'] : [];
        
        $parents_json = json_encode($parents_array);
        $children_json = json_encode($children_array);

        $incoming_attrs = isset($req['attributes']) && is_array($req['attributes']) ? $req['attributes'] : [];
        $attributes_json = json_encode($incoming_attrs);

        $stmtOld->execute([$req_key, $project_id]);
        $old_row = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$old_row) {
            // NEU ANLEGEN
            $stmtIns->execute([
                $project_id, $req_key, $type, $title, $description, 
                $rationale, $status, $review_status, $parents_json, $children_json, $attributes_json
            ]);
            
            $new_id = $pdo->lastInsertId();

            $histStmt->execute([
                $new_id, $req_key, $project_id, $type, $title, $description, 
                $rationale, $status, $parents_json, $children_json, $user_id, 'Erstellt via CLI Push', $attributes_json
            ]);

            $action_logs[] = "[NEU ERSTELLT] " . $req_key . " (" . $type . ")";
        } else {
            // AKTUALISIEREN
            $id = $old_row['id'];
            $type = $old_row['type'];

            $existing_attrs = [];
            if (!empty($old_row['attributes'])) {
                $decoded = json_decode($old_row['attributes'], true);
                if (is_array($decoded)) $existing_attrs = $decoded;
            }
            foreach ($incoming_attrs as $k => $v) {
                $existing_attrs[$k] = $v;
            }
            $attributes_json = json_encode($existing_attrs);

           $changes = [];
            if ($old_row['title'] !== $title) $changes[] = "Titel: '{$old_row['title']}' ➔ '{$title}'";
            if ($old_row['description'] !== $description) $changes[] = "Beschreibung geändert";
            if ($old_row['rationale'] !== $rationale) $changes[] = "Begründung geändert";
            if ($old_row['status'] !== $status) $changes[] = "Status [{$old_row['status']} ➔ {$status}]";
            if ($old_row['review_status'] !== $review_status) $changes[] = "Prüf-Status [{$old_row['review_status']} ➔ {$review_status}]";
            
            $old_parents = json_decode($old_row['parents'] ?? '[]', true) ?: [];
            $old_children = json_decode($old_row['children'] ?? '[]', true) ?: [];

            if ($old_parents !== $parents_array) $changes[] = "Parents geändert";
            if ($old_children !== $children_array) $changes[] = "Children geändert";

            // --- NEU: Wenn es keine Änderungen gab, direkt überspringen! ---
            if (empty($changes)) {
                continue; 
            }
            
            // Ab hier wissen wir sicher: Es GIBT Änderungen. Das else entfällt.
            $action = 'Geändert (CLI Push):<br>• ' . implode('<br>• ', $changes);
            $action_logs[] = "[AKTUALISIERT] " . $req_key . " -> " . implode(' | ', $changes);

            $stmtUpd->execute([
                $title, $description, $rationale, $status, $review_status, 
                $parents_json, $children_json, $attributes_json, $req_key, $project_id
            ]);

            $histStmt->execute([
                $id, $req_key, $project_id, $type, $title, $description, 
                $rationale, $status, $parents_json, $children_json, $user_id, $action, $attributes_json
            ]);
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Push erfolgreich synchronisiert.',
        'details' => $action_logs
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fehler beim Push: ' . $e->getMessage()]);
}
?>
