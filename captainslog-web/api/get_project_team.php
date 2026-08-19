<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $projectId = $_GET['project_id'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;

    if (!$projectId || !$userId) {
        throw new Exception('Projekt oder Sitzung fehlt.');
    }

    $access = $pdo->prepare(
        'SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_active = 1'
    );
    $access->execute([$projectId, $userId]);

    if (!$access->fetchColumn() && ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception('Kein Zugriff auf dieses Projekt.');
    }

    $team = $pdo->prepare(
        'SELECT
            pm.project_id,
            pm.user_id,
            pm.project_role,
            pm.expertise,
            pm.availability,
            pm.is_active,
            pm.joined_at,
            u.username,
            u.role AS system_role
         FROM project_members pm
         JOIN users u ON u.id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.is_active DESC,
                  CASE WHEN pm.project_role = "Projektleitung" THEN 0 ELSE 1 END,
                  u.username ASC'
    );
    $team->execute([$projectId]);

    $users = $pdo->prepare(
        'SELECT u.id, u.username, u.role
         FROM users u
         WHERE NOT EXISTS (
             SELECT 1
             FROM project_members pm
             WHERE pm.project_id = ? AND pm.user_id = u.id
         )
         ORDER BY u.username ASC'
    );
    $users->execute([$projectId]);

    $roles = $pdo->query(
        'SELECT id, role_name, description
         FROM project_roles
         WHERE is_active = 1
         ORDER BY role_name ASC'
    );

    echo json_encode([
        'success' => true,
        'team' => $team->fetchAll(PDO::FETCH_ASSOC),
        'available_users' => $users->fetchAll(PDO::FETCH_ASSOC),
        'roles' => $roles->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}
