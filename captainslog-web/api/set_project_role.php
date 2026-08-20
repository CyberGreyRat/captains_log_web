<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';
header('Content-Type: application/json; charset=utf-8');

try {
    set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));

    require_edit_permission();
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $roleName = trim((string)($data['role_name'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($roleName === '') {
        throw new Exception('Rollenname fehlt.');
    }

    $roleName = mb_substr($roleName, 0, 100);
    $description = $description === '' ? null : mb_substr($description, 0, 255);

    $statement = $pdo->prepare(
        'INSERT INTO project_roles (role_name, description, created_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            description = COALESCE(VALUES(description), description),
            is_active = 1,
            updated_at = CURRENT_TIMESTAMP'
    );
    $statement->execute([$roleName, $description, $userId]);

    echo json_encode([
        'success' => true,
        'id' => (int)$pdo->lastInsertId(),
        'role_name' => $roleName
    ]);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}
