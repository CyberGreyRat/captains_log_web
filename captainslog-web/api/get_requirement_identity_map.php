<?php
// api/get_requirement_identity_map.php

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
        'SELECT
            id,
            display_number,
            req_key,
            external_key,
            type,
            title
         FROM requirements
         WHERE project_id = ?
         ORDER BY display_number, id'
    );

    $statement->execute([$projectId]);
    $items = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['display_number'] = (int)$item['display_number'];
        $item['display_id'] = str_pad(
            (string)$item['display_number'],
            3,
            '0',
            STR_PAD_LEFT
        );
        $item['display_label'] =
            $item['display_id'] . ' - ' . $item['type'];
    }
    unset($item);

    echo json_encode(
        [
            'success' => true,
            'items' => $items
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $error) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
