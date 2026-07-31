<?php
// 1. Verhindert die Ausgabe von HTML-Fehlern (<br> etc.)
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require '../config/db.php';

header('Content-Type: application/json');

try {
    // 2. Zwingt die Datenbank, Fehler als Exception zu werfen (damit sie im catch landen!)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $project_id = $data['project_id'] ?? null;
    $type = $data['type'] ?? 'USR';
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $rationale = $data['rationale'] ?? '';
    $status = $data['status'] ?? 'open';
    $parents = json_encode($data['parents'] ?? []);
    $children = json_encode($data['children'] ?? []);

    $hostname = gethostname() ?: 'unknown';

    // Sicherstellen, dass $attrs ein Array ist (egal ob JS einen String oder ein Objekt schickt)
    $attrs = $data['attributes'] ?? [];
    if (is_string($attrs)) {
        $attrs = json_decode($attrs, true) ?: [];
    }

    // 1. Prefix für den Req-Key generieren
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND type = ?");
    $stmt->execute([$project_id, $type]);
    $count = $stmt->fetchColumn() + 1;
    $req_key = $type . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

    // 2. Insert in die Basis-Tabelle
    $stmtIns = $pdo->prepare("
        INSERT INTO requirements (project_id, req_key, type, title, description, rationale, status, parents, children) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([$project_id, $req_key, $type, $title, $description, $rationale, $status, $parents, $children]);
    $newId = $pdo->lastInsertId();

    // 3. Insert in die typspezifischen Tabellen
    if ($type === 'STK') {
        $stmtStk = $pdo->prepare("INSERT INTO req_stakeholders (requirement_id, email, phone, organization, role, influence) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtStk->execute([$newId, $attrs['email'] ?? null, $attrs['phone'] ?? null, $attrs['organization'] ?? null, $attrs['role'] ?? null, $attrs['influence'] ?? null]);
    } elseif ($type === 'US') {
        $stmtUs = $pdo->prepare("INSERT INTO req_user_stories (requirement_id, us_role, us_action, us_benefit, acceptance_criteria, story_points) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUs->execute([$newId, $attrs['us_role'] ?? null, $attrs['us_action'] ?? null, $attrs['us_benefit'] ?? null, $attrs['acceptance_criteria'] ?? null, $attrs['story_points'] ?? null]);
    } elseif ($type === 'UC') {
        $stmtUc = $pdo->prepare("INSERT INTO req_use_cases (requirement_id, primary_actor, preconditions, main_scenario, alt_scenario) VALUES (?, ?, ?, ?, ?)");
        $stmtUc->execute([$newId, $attrs['primary_actor'] ?? null, $attrs['preconditions'] ?? null, $attrs['main_scenario'] ?? null, $attrs['alt_scenario'] ?? null]);
    }

    // 4. Historie schreiben (Jetzt wieder GANZ WICHTIG mit parents, children und hostname!)
    $histStmt = $pdo->prepare("
        INSERT INTO requirement_history 
        (requirement_id, req_key, project_id, type, title, description, rationale, attributes, status, parents, children, modified_by, hostname, action) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'CREATE')
    ");
    $histStmt->execute([
        $newId,
        $req_key,
        $project_id,
        $type,
        $title,
        $description,
        $rationale,
        json_encode($attrs),
        $status,
        $parents,
        $children,
        $_SESSION['user_id'],
        $hostname
    ]);

    echo json_encode(['success' => true, 'id' => $newId, 'req_key' => $req_key]);

} catch (Exception $e) {
    // Falls ein Datenbankfehler auftritt, senden wir ihn als sauberes JSON an den Browser
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}