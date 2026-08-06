<?php
// api/cli_pull.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
require '../config/db.php';

$token = $_GET['token'] ?? '';
$project_param = $_GET['project'] ?? '';

if (empty($token) || empty($project_param)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token und Projekt müssen angegeben werden.']);
    exit;
}

// User über Token validieren
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized / Ungültiges Token.']);
    exit;
}

// 1. Echte Projekt-ID herausfinden (funktioniert mit Name oder ID)
$stmtProj = $pdo->prepare("SELECT id, name FROM projects WHERE id = ? OR name = ? LIMIT 1");
$stmtProj->execute([$project_param, $project_param]);
$project = $stmtProj->fetch();

if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Projekt '$project_param' nicht gefunden."]);
    exit;
}

$real_project_id = $project['id'];

// 2. Anforderungen zur echten Projekt-ID abrufen
$stmtReq = $pdo->prepare("SELECT * FROM requirements WHERE project_id = ?");
$stmtReq->execute([$real_project_id]);
$requirements = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($requirements as $req) {
    // JSON Strings sauber umwandeln, damit C++ sie richtig parsen kann
    $parents = json_decode($req['parents'] ?? '[]', true) ?: [];
    $children = json_decode($req['children'] ?? '[]', true) ?: [];
    $attributes = json_decode($req['attributes'] ?? '{}', true) ?: [];

    $items[] = [
        'uid' => $req['req_key'],
        'type' => $req['type'] ?? 'req',
        'title' => $req['title'],
        'text' => $req['description'], 
        'rationale' => $req['rationale'],
        'status' => $req['status'],
        'review_status' => $req['review_status'],
        'parents' => $parents,
        'children' => $children,
        'attributes' => $attributes
    ];
}

// 3. Als JSON zurückgeben
echo json_encode([
    'success' => true,
    'project_id' => $real_project_id,
    'project_name' => $project['name'],
    'items' => $items
]);
?>