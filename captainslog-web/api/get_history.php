<?php
// api/get_history.php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function clean_string(mixed $value, int $maxLength = 255): string
{
    return mb_substr(trim((string) $value), 0, $maxLength);
}

try {
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $role = (string) ($_SESSION['role'] ?? 'viewer');
    $projectId = clean_string($_GET['project_id'] ?? '', 50);

    if ($userId <= 0) {
        json_response(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    }

    if ($projectId === '') {
        json_response(['success' => false, 'error' => 'Projekt-ID fehlt.'], 400);
    }

    if ($role !== 'admin') {
        $access = $pdo->prepare(
            'SELECT 1
             FROM project_members
             WHERE project_id = :project_id
               AND user_id = :user_id
               AND is_active = 1
             LIMIT 1'
        );
        $access->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId,
        ]);

        if (!$access->fetchColumn()) {
            json_response(['success' => false, 'error' => 'Kein Zugriff auf dieses Projekt.'], 403);
        }
    }

    $search = clean_string($_GET['search'] ?? '', 200);
    $entityType = clean_string($_GET['entity_type'] ?? '', 50);
    $action = strtoupper(clean_string($_GET['action'] ?? '', 20));
    $actorUserId = max(0, (int) ($_GET['actor_user_id'] ?? 0));
    $sourceType = clean_string($_GET['source_type'] ?? '', 50);
    $dateFrom = clean_string($_GET['date_from'] ?? '', 10);
    $dateTo = clean_string($_GET['date_to'] ?? '', 10);
    $batchId = clean_string($_GET['batch_id'] ?? '', 36);
    $entityKey = clean_string($_GET['entity_key'] ?? '', 100);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(200, max(20, (int) ($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $conditions = ['al.project_id = :project_id'];
    $params = [':project_id' => $projectId];

    if ($entityType !== '') {
        $conditions[] = 'al.entity_type = :entity_type';
        $params[':entity_type'] = $entityType;
    }

    if ($action !== '') {
        $allowedActions = ['CREATE', 'UPDATE', 'DELETE', 'IMPORT', 'LINK', 'UNLINK', 'COMMENT', 'LOGIN', 'EXPORT'];
        if (!in_array($action, $allowedActions, true)) {
            json_response(['success' => false, 'error' => 'Ungueltiger Aktionsfilter.'], 400);
        }
        $conditions[] = 'al.action = :action';
        $params[':action'] = $action;
    }

    if ($actorUserId > 0) {
        $conditions[] = 'al.actor_user_id = :actor_user_id';
        $params[':actor_user_id'] = $actorUserId;
    }

    if ($sourceType !== '') {
        $conditions[] = 'al.source_type = :source_type';
        $params[':source_type'] = $sourceType;
    }

    if ($batchId !== '') {
        $conditions[] = 'al.batch_id = :batch_id';
        $params[':batch_id'] = $batchId;
    }

    if ($entityKey !== '') {
        $conditions[] = 'al.entity_key = :entity_key';
        $params[':entity_key'] = $entityKey;
    }

    if ($dateFrom !== '') {
        $conditions[] = 'al.created_at >= :date_from';
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }

    if ($dateTo !== '') {
        $conditions[] = 'al.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
        $params[':date_to'] = $dateTo . ' 00:00:00';
    }

    if ($search !== '') {
        $conditions[] = '(
            al.entity_key LIKE :search
            OR al.entity_title LIKE :search
            OR al.actor_name LIKE :search
            OR u.username LIKE :search
            OR al.source_name LIKE :search
            OR al.hostname LIKE :search
            OR al.old_data LIKE :search
            OR al.new_data LIKE :search
        )';
        $params[':search'] = '%' . $search . '%';
    }

    $where = implode(' AND ', $conditions);

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM audit_log al
         LEFT JOIN users u ON u.id = al.actor_user_id
         WHERE {$where}"
    );
    $countStatement->execute($params);
    $total = (int) $countStatement->fetchColumn();

    $dataSql =
        "SELECT
            al.id,
            al.project_id,
            al.batch_id,
            al.entity_type,
            al.entity_id,
            al.entity_key,
            al.entity_title,
            al.action,
            al.old_data,
            al.new_data,
            al.changed_fields,
            al.actor_user_id,
            COALESCE(u.username, al.actor_name, 'System') AS actor_name,
            al.source_type,
            al.source_name,
            al.hostname,
            al.request_id,
            al.ip_address,
            al.created_at
         FROM audit_log al
         LEFT JOIN users u ON u.id = al.actor_user_id
         WHERE {$where}
         ORDER BY al.created_at DESC, al.id DESC
         LIMIT {$limit} OFFSET {$offset}";

    $dataStatement = $pdo->prepare($dataSql);
    $dataStatement->execute($params);
    $entries = $dataStatement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($entries as &$entry) {
        foreach (['old_data', 'new_data', 'changed_fields'] as $jsonField) {
            $raw = $entry[$jsonField] ?? null;
            if ($raw === null || $raw === '') {
                $entry[$jsonField] = null;
                continue;
            }

            $decoded = json_decode((string) $raw, true);
            $entry[$jsonField] = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }
    }
    unset($entry);

    $filterStatement = $pdo->prepare(
        "SELECT DISTINCT entity_type
         FROM audit_log
         WHERE project_id = :project_id
         ORDER BY entity_type"
    );
    $filterStatement->execute([':project_id' => $projectId]);
    $entityTypes = $filterStatement->fetchAll(PDO::FETCH_COLUMN);

    $sourceStatement = $pdo->prepare(
        "SELECT DISTINCT source_type
         FROM audit_log
         WHERE project_id = :project_id
           AND source_type IS NOT NULL
           AND source_type <> ''
         ORDER BY source_type"
    );
    $sourceStatement->execute([':project_id' => $projectId]);
    $sourceTypes = $sourceStatement->fetchAll(PDO::FETCH_COLUMN);

    $actorStatement = $pdo->prepare(
        "SELECT DISTINCT
            al.actor_user_id AS id,
            COALESCE(u.username, al.actor_name, 'System') AS name
         FROM audit_log al
         LEFT JOIN users u ON u.id = al.actor_user_id
         WHERE al.project_id = :project_id
         ORDER BY name"
    );
    $actorStatement->execute([':project_id' => $projectId]);
    $actors = $actorStatement->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'entries' => $entries,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $limit)),
        ],
        'filters' => [
            'entity_types' => $entityTypes,
            'source_types' => $sourceTypes,
            'actors' => $actors,
        ],
    ]);
} catch (Throwable $error) {
    error_log('get_history.php: ' . $error->getMessage());
    json_response([
        'success' => false,
        'error' => 'Die Historie konnte nicht geladen werden.',
    ], 500);
}
