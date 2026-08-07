<?php
// api/set_requirements.php
ini_set('display_errors', 0); 
error_reporting(E_ALL); 
session_start();
require '../config/db.php'; 
header('Content-Type: application/json');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['project_id']) || empty($data['title'])) {
        throw new Exception("Projekt-ID und Titel fehlen!");
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $type = $data['type'] ?? 'SYS';
    $title = $data['title'];
    $description = $data['description'] ?? '';
    $rationale = $data['rationale'] ?? '';
    $status = $data['status'] ?? 'open';
    $source_contact = $data['source_contact'] ?? '';
    $effort = $data['effort'] ?? '';
    $acceptance_criteria = $data['acceptance_criteria'] ?? '';
    $review_status = $data['review_status'] ?? 'Neu';
    $parents_array = (isset($data['parents']) && is_array($data['parents'])) ? $data['parents'] : [];
    $children_array = (isset($data['children']) && is_array($data['children'])) ? $data['children'] : [];
    
    $parents = json_encode($parents_array);
    $children = json_encode($children_array);

    // Wer macht die Änderung?
    $user_id = $_SESSION['user_id'] ?? 1;
    $hostname = $_SESSION['hostname'] ?? 'LocalPC';

    // JSON Attribute sicher laden
    $existing_attrs = [];
    $req_key = '';
    $old_row = null; 

    if ($id) {
        $stmtOld = $pdo->prepare("SELECT * FROM requirements WHERE id = ?");
        $stmtOld->execute([$id]);
        $old_row = $stmtOld->fetch(PDO::FETCH_ASSOC);
        
        if ($old_row) {
            $req_key = $old_row['req_key'];
            if (!empty($old_row['attributes'])) {
                $decoded = json_decode($old_row['attributes'], true);
                if (is_array($decoded)) {
                    $existing_attrs = $decoded;
                }
            }
        }
    }

    if (isset($data['attributes']) && is_array($data['attributes'])) {
        foreach ($data['attributes'] as $k => $v) {
            $existing_attrs[$k] = $v;
        }
    }
    $attributes_json = json_encode($existing_attrs);

    // ---------------------------------------------------------
    // 1. BESTEHENDES ELEMENT AKTUALISIEREN
    // ---------------------------------------------------------
    if ($id && $old_row) {
        $changes = [];
        $clean = function ($txt) { return trim($txt ?? '') ?: 'Leer'; };
        $fmt_arr = function ($arr) { return empty($arr) ? '-' : implode(', ', is_array($arr) ? $arr : []); };

        if ($old_row['title'] !== $title) $changes[] = "Titel [" . $clean($old_row['title']) . " ➔ " . $clean($title) . "]";
        if ($old_row['description'] !== $description) $changes[] = "Beschreibung geändert";
        if ($old_row['rationale'] !== $rationale) $changes[] = "Begründung geändert";
        if ($old_row['status'] !== $status) $changes[] = "Status [{$old_row['status']} ➔ {$status}]";
        if ($old_row['review_status'] !== $review_status) $changes[] = "Prüf-Status [{$old_row['review_status']} ➔ {$review_status}]";
        
        $old_parents = json_decode($old_row['parents'] ?? '[]', true) ?: [];
        $old_children = json_decode($old_row['children'] ?? '[]', true) ?: [];
        
        if ($old_parents !== $parents_array) $changes[] = "Parents [" . $fmt_arr($old_parents) . " ➔ " . $fmt_arr($parents_array) . "]";
        if ($old_children !== $children_array) $changes[] = "Children [" . $fmt_arr($old_children) . " ➔ " . $fmt_arr($children_array) . "]";

        // Wenn sich absolut nichts geändert hat, brechen wir hier ab, um die Historie nicht zu spammen!
        if (empty($changes)) {
            echo json_encode(['success' => true]);
            exit;
        }

        $action = 'Geändert:<br>  ' . implode('<br>  ', $changes);

        // a) Update in die Datenbank
        $stmt = $pdo->prepare("UPDATE requirements SET type=?, title=?, description=?, rationale=?, status=?, source_contact=?, effort=?, acceptance_criteria=?, review_status=?, parents=?, children=?, attributes=? WHERE id=? AND project_id=?");
        $stmt->execute([$type, $title, $description, $rationale, $status, $source_contact, $effort, $acceptance_criteria, $review_status, $parents, $children, $attributes_json, $id, $project_id]);

        // b) Exakt EINEN Eintrag in die Historie (mit Hostname)
        $histStmt = $pdo->prepare("INSERT INTO requirement_history (requirement_id, req_key, project_id, type, title, description, rationale, status, parents, children, modified_by, action, attributes, hostname) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $histStmt->execute([$id, $req_key, $project_id, $type, $title, $description, $rationale, $status, $parents, $children, $user_id, $action, $attributes_json, $hostname]);

    } 
    // ---------------------------------------------------------
    // 2. NEUES ELEMENT ANLEGEN
    // ---------------------------------------------------------
    else {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND type = ?");
        $countStmt->execute([$project_id, $type]);
        $count = $countStmt->fetchColumn() + 1;
        $req_key = $type . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // a) DAS FEHLTE: Das Element muss erst in die Haupt-Tabelle gespeichert werden!
        $stmt = $pdo->prepare("INSERT INTO requirements (project_id, req_key, type, title, description, rationale, status, review_status, parents, children, attributes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $req_key, $type, $title, $description, $rationale, $status, $review_status, $parents, $children, $attributes_json]);
        
        $new_id = $pdo->lastInsertId();
        $action = 'CREATE (Neu angelegt)';

        // b) Exakt EINEN Eintrag in die Historie (mit Hostname)
        $histStmt = $pdo->prepare("INSERT INTO requirement_history (requirement_id, req_key, project_id, type, title, description, rationale, status, parents, children, modified_by, action, attributes, hostname) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $histStmt->execute([$new_id, $req_key, $project_id, $type, $title, $description, $rationale, $status, $parents, $children, $user_id, $action, $attributes_json, $hostname]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}