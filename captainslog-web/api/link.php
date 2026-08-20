<?php
// api/link.php
header('Content-Type: application/json');
set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$project_id = $input['project_id'] ?? '';

// Token validieren
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Ungültiger oder abgelaufener Token. Bitte cap login ausführen.']);
    exit;
}

// Prüfen ob Nutzer Mitglied im Projekt ist
$projStmt = $pdo->prepare("
    SELECT p.name FROM projects p 
    JOIN project_members pm ON p.id = pm.project_id 
    WHERE p.id = ? AND pm.user_id = ?
");
$projStmt->execute([$project_id, $user['id']]);
$project = $projStmt->fetch();

if ($project) {
    echo json_encode([
        'success' => true, 
        'project_name' => $project['name'],
        'message' => "Erfolgreich mit Projekt verknüpft."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Projekt nicht gefunden oder kein Zugriff.']);
}
?>
