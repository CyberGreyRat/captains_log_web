<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_edit_permission();
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $projectId = $data['project_id'] ?? '';
    $memberUserId = (int)($data['user_id'] ?? 0);
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    if (!$projectId || !$memberUserId || !$currentUserId) {
        throw new Exception('Projekt oder Nutzer fehlt.');
    }

    if ($memberUserId === $currentUserId) {
        throw new Exception('Du kannst dich nicht selbst aus dem aktuellen Projekt entfernen.');
    }

    $access = $pdo->prepare(
        'SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_active = 1'
    );
    $access->execute([$projectId, $currentUserId]);

    if (!$access->fetchColumn() && ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception('Kein Zugriff auf dieses Projekt.');
    }

    $statement = $pdo->prepare(
        'DELETE FROM project_members WHERE project_id = ? AND user_id = ?'
    );
    $statement->execute([$projectId, $memberUserId]);

    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}
