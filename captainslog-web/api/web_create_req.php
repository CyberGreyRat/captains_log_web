<?php
// api/web_create_req.php
session_start();
header('Content-Type: application/json');
require '../config/db.php';

// Check ob eingeloggt
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// JSON Payload vom JavaScript abgreifen
$input = json_decode(file_get_contents('php://input'), true);

// Für welches Projekt ist das? (Das müssen wir vom JS noch mitliefern lassen)
$project_id = $input['project_id'] ?? '';

$type = $input['type'] ?? 'USR';
$title = $input['title'] ?? '';
$text = $input['text'] ?? '';
$rationale = $input['rationale'] ?? '';

// Arrays als JSON-Strings für die DB aufbereiten
$attributes = isset($input['attributes']) ? json_encode($input['attributes']) : '{}';
$parents = isset($input['parents']) ? json_encode($input['parents']) : '[]';
$children = isset($input['children']) ? json_encode($input['children']) : '[]';

if (!$project_id || !$title || !$text) {
    echo json_encode(['error' => 'Pflichtfelder fehlen.']);
    exit;
}

try {
    // Generiere einen automatischen Requirement-Key (z.B. USR-001)
    // Wir zählen dafür, wie viele Reqs es für dieses Projekt und diesen Typ schon gibt
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND type = ?");
    $stmtCount->execute([$project_id, $type]);
    $count = $stmtCount->fetchColumn() + 1;
    $req_key = $type . '-' . str_pad($count, 3, '0', STR_PAD_LEFT); // Gibt z.B. SYS-004

    // Ab in die Datenbank damit!
    $stmt = $pdo->prepare("
        INSERT INTO requirements 
        (project_id, req_key, type, title, description, rationale, attributes, parents, children) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $project_id, 
        $req_key, 
        $type, 
        $title, 
        $text, 
        $rationale, 
        $attributes, 
        $parents, 
        $children
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Anforderung erfolgreich erstellt!',
        'req_key' => $req_key
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
?>