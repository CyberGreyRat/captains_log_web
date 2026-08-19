<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function cleanText($value, $maxLength = 255)
{
    $value = trim((string)($value ?? ''));
    if ($value === '') return null;
    return mb_substr($value, 0, $maxLength);
}

try {
    require_edit_permission();

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $projectId = $data['project_id'] ?? '';
    $memberUserId = (int)($data['user_id'] ?? 0);
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $projectRole = cleanText($data['project_role'] ?? null, 100);
    $expertise = cleanText($data['expertise'] ?? null, 255);
    $availability = cleanText($data['availability'] ?? null, 100);
    $isActive = array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1;

    if (!$projectId || !$memberUserId || !$currentUserId) {
        throw new Exception('Projekt und Nutzer sind Pflichtfelder.');
    }

    if (!$projectRole) {
        throw new Exception('Bitte eine Projektrolle eingeben oder auswaehlen.');
    }

    $access = $pdo->prepare(
        'SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_active = 1'
    );
    $access->execute([$projectId, $currentUserId]);

    if (!$access->fetchColumn() && ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception('Kein Zugriff auf dieses Projekt.');
    }

    $userExists = $pdo->prepare('SELECT 1 FROM users WHERE id = ?');
    $userExists->execute([$memberUserId]);
    if (!$userExists->fetchColumn()) {
        throw new Exception('Der ausgewaehlte Nutzer existiert nicht.');
    }

    $pdo->beginTransaction();

    $roleStatement = $pdo->prepare(
        'INSERT INTO project_roles (role_name, created_by)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE is_active = 1, updated_at = CURRENT_TIMESTAMP'
    );
    $roleStatement->execute([$projectRole, $currentUserId]);

    $memberStatement = $pdo->prepare(
        'INSERT INTO project_members
            (project_id, user_id, project_role, expertise, availability, is_active)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            project_role = VALUES(project_role),
            expertise = VALUES(expertise),
            availability = VALUES(availability),
            is_active = VALUES(is_active)'
    );
    $memberStatement->execute([
        $projectId,
        $memberUserId,
        $projectRole,
        $expertise,
        $availability,
        $isActive
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Projektmitglied wurde gespeichert.'
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}
