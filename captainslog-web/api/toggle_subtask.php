<?php
// api/toggle_subtask.php

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Nicht angemeldet.');
    }

    set_audit_context(
        $pdo,
        'web',
        basename($_SERVER['SCRIPT_NAME'])
    );

    $data = json_decode(
        file_get_contents('php://input'),
        true
    ) ?: [];

    $id = (int)($data['id'] ?? 0);
    $completed = !empty($data['completed']);

    if ($id <= 0) {
        throw new Exception('Unteraufgaben-ID fehlt.');
    }

    $statement = $pdo->prepare(
        'UPDATE project_tasks
         SET
            progress_pct = ?,
            completed_at = ?,
            completed_by = ?
         WHERE id = ?
           AND parent_id IS NOT NULL'
    );

    $statement->execute([
        $completed ? 100 : 0,
        $completed ? date('Y-m-d H:i:s') : null,
        $completed ? (int)$_SESSION['user_id'] : null,
        $id
    ]);

    if ($statement->rowCount() === 0) {
        throw new Exception('Unteraufgabe wurde nicht gefunden oder nicht geändert.');
    }

    echo json_encode([
        'success' => true,
        'completed' => $completed,
        'completed_at' => $completed
            ? date('d.m.Y H:i')
            : null,
        'completed_by' => $completed
            ? ($_SESSION['username'] ?? 'Unbekannt')
            : null
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
