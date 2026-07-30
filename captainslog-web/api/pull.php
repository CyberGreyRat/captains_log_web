<?php
// api/pull.php
header('Content-Type: application/json');
require '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$project_id = $input['project_id'] ?? '';

// User checken
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Anforderungen aus der DB holen
$reqStmt = $pdo->prepare("SELECT req_key, title, description, status FROM requirements WHERE project_id = ?");
$reqStmt->execute([$project_id]);
$requirements = $reqStmt->fetchAll();

echo json_encode([
    'success' => true,
    'requirements' => $requirements
]);
?>