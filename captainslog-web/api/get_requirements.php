<?php
// api/get_requirements.php

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Nicht angemeldet.');
    }

    $projectId = trim((string)($_GET['project_id'] ?? ''));

    if ($projectId === '') {
        throw new Exception('Projekt-ID fehlt.');
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM requirements
         WHERE project_id = ?
         ORDER BY serial_number, id'
    );

    $statement->execute([$projectId]);
    $requirements = $statement->fetchAll(PDO::FETCH_ASSOC);

    $relationStatement = $pdo->prepare(
        'SELECT
            parent_requirement_id,
            child_requirement_id
         FROM requirement_relations relation_row
         JOIN requirements parent_req
           ON parent_req.id = relation_row.parent_requirement_id
         WHERE parent_req.project_id = ?'
    );

    $relationStatement->execute([$projectId]);
    $relations = $relationStatement->fetchAll(PDO::FETCH_ASSOC);

    $byId = [];

    foreach ($requirements as $index => &$requirement) {
        $requirement['id'] = (int)$requirement['id'];
        $requirement['serial_number'] = (int)$requirement['serial_number'];
        $requirement['display_number'] = str_pad(
            (string)$requirement['serial_number'],
            3,
            '0',
            STR_PAD_LEFT
        );
        $requirement['display_label'] = $requirement['req_key'];
        $requirement['source_page'] = $requirement['source_page'] !== null
            ? (int)$requirement['source_page']
            : null;
        $requirement['attributes'] = json_decode(
            $requirement['attributes'] ?? '{}',
            true
        ) ?: [];
        $requirement['parent_ids'] = [];
        $requirement['child_ids'] = [];
        $requirement['parents'] = [];
        $requirement['children'] = [];
        $byId[$requirement['id']] = $index;
    }
    unset($requirement);

    foreach ($relations as $relation) {
        $parentId = (int)$relation['parent_requirement_id'];
        $childId = (int)$relation['child_requirement_id'];

        if (!isset($byId[$parentId], $byId[$childId])) {
            continue;
        }

        $requirements[$byId[$childId]]['parent_ids'][] = $parentId;
        $requirements[$byId[$childId]]['parents'][] =
            $requirements[$byId[$parentId]]['req_key'];

        $requirements[$byId[$parentId]]['child_ids'][] = $childId;
        $requirements[$byId[$parentId]]['children'][] =
            $requirements[$byId[$childId]]['req_key'];
    }

    echo json_encode(
        [
            'success' => true,
            'items' => $requirements,
            'requirements' => $requirements
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $error) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
