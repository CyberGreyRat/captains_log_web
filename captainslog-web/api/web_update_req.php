<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $id = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $rationale = $data['rationale'] ?? '';
    $status = $data['status'] ?? 'open';
    $parents = json_encode($data['parents'] ?? []);
    $children = json_encode($data['children'] ?? []);

    $hostname = gethostname() ?: 'unknown';

    // Attribute sicher als Array parsen
    $attrs = $data['attributes'] ?? [];
    if (is_string($attrs)) {
        $attrs = json_decode($attrs, true) ?: [];
    }

    // 1. Basis-Tabelle updaten
    $stmtUpd = $pdo->prepare("
        UPDATE requirements 
        SET title = ?, description = ?, rationale = ?, status = ?, parents = ?, children = ? 
        WHERE id = ?
    ");
    $stmtUpd->execute([$title, $description, $rationale, $status, $parents, $children, $id]);

    // 2. Typ herausfinden
    $reqInfo = $pdo->prepare("SELECT type, req_key, project_id FROM requirements WHERE id = ?");
    $reqInfo->execute([$id]);
    $req = $reqInfo->fetch();
    if (!$req) {
        throw new Exception("Anforderung mit ID $id nicht gefunden.");
    }
    $type = $req['type'];

    // 3. Typspezifische Tabellen updaten
    if ($type === 'STK') {
        $stmtStk = $pdo->prepare("
            INSERT INTO req_stakeholders (requirement_id, email, phone, organization, role, influence) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE email=VALUES(email), phone=VALUES(phone), organization=VALUES(organization), role=VALUES(role), influence=VALUES(influence)
        ");
        $stmtStk->execute([$id, $attrs['email'] ?? null, $attrs['phone'] ?? null, $attrs['organization'] ?? null, $attrs['role'] ?? null, $attrs['influence'] ?? null]);
    } elseif ($type === 'US') {
        $stmtUs = $pdo->prepare("
            INSERT INTO req_user_stories (requirement_id, us_role, us_action, us_benefit, acceptance_criteria, story_points) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE us_role=VALUES(us_role), us_action=VALUES(us_action), us_benefit=VALUES(us_benefit), acceptance_criteria=VALUES(acceptance_criteria), story_points=VALUES(story_points)
        ");
        $stmtUs->execute([$id, $attrs['us_role'] ?? null, $attrs['us_action'] ?? null, $attrs['us_benefit'] ?? null, $attrs['acceptance_criteria'] ?? null, $attrs['story_points'] ?? null]);
    } elseif ($type === 'UC') {
        $stmtUc = $pdo->prepare("
            INSERT INTO req_use_cases (requirement_id, primary_actor, preconditions, main_scenario, alt_scenario) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE primary_actor=VALUES(primary_actor), preconditions=VALUES(preconditions), main_scenario=VALUES(main_scenario), alt_scenario=VALUES(alt_scenario)
        ");
        $stmtUc->execute([$id, $attrs['primary_actor'] ?? null, $attrs['preconditions'] ?? null, $attrs['main_scenario'] ?? null, $attrs['alt_scenario'] ?? null]);
    }

    // 4. Historie schreiben
    $histStmt = $pdo->prepare("
        INSERT INTO requirement_history 
        (requirement_id, req_key, project_id, type, title, description, rationale, attributes, status, parents, children, modified_by, hostname, action) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'UPDATE')
    ");
    $histStmt->execute([
        $id,
        $req['req_key'],
        $req['project_id'],
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

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}