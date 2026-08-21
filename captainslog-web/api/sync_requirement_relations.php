<?php
// api/sync_requirement_relations.php

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

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $requirementId = (int)($data['requirement_id'] ?? 0);
    $parentIds = array_values(array_unique(array_filter(array_map(
        'intval',
        $data['parent_ids'] ?? []
    ))));
    $childIds = array_values(array_unique(array_filter(array_map(
        'intval',
        $data['child_ids'] ?? []
    ))));

    if ($requirementId <= 0) {
        throw new Exception('Anforderungs-ID fehlt.');
    }

    set_audit_context(
        $pdo,
        'web',
        basename($_SERVER['SCRIPT_NAME'])
    );

    $pdo->beginTransaction();

    $pdo->prepare(
        'DELETE FROM requirement_relations
         WHERE child_requirement_id = ?'
    )->execute([$requirementId]);

    $pdo->prepare(
        'DELETE FROM requirement_relations
         WHERE parent_requirement_id = ?'
    )->execute([$requirementId]);

    $insert = $pdo->prepare(
        'INSERT IGNORE INTO requirement_relations (
            parent_requirement_id,
            child_requirement_id,
            relation_type,
            created_by
         ) VALUES (?, ?, ?, ?)'
    );

    foreach ($parentIds as $parentId) {
        if ($parentId !== $requirementId) {
            $insert->execute([
                $parentId,
                $requirementId,
                'fulfills',
                (int)$_SESSION['user_id']
            ]);
        }
    }

    foreach ($childIds as $childId) {
        if ($childId !== $requirementId) {
            $insert->execute([
                $requirementId,
                $childId,
                'fulfills',
                (int)$_SESSION['user_id']
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
