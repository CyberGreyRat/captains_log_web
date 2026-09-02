<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function rdResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function rdUser(): int
{
    if (empty($_SESSION['user_id'])) {
        rdResponse([
            'success' => false,
            'error' => 'Nicht angemeldet.'
        ], 401);
    }

    return (int) $_SESSION['user_id'];
}

function rdBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        rdResponse([
            'success' => false,
            'error' => 'Ungültiges JSON.'
        ], 400);
    }

    return $data;
}

function rdUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($data), 4)
    );
}

function rdProject(PDO $pdo, string $projectId): void
{
    $statement = $pdo->prepare('SELECT id FROM projects WHERE id = ?');
    $statement->execute([$projectId]);

    if (!$statement->fetchColumn()) {
        rdResponse([
            'success' => false,
            'error' => 'Projekt nicht gefunden.'
        ], 404);
    }
}

function rdDocument(PDO $pdo, string $id, bool $lock = false): array
{
    $sql = 'SELECT * FROM requirement_documents WHERE id = ?';

    if ($lock) {
        $sql .= ' FOR UPDATE';
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([$id]);
    $document = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        rdResponse([
            'success' => false,
            'error' => 'Anforderung nicht gefunden.'
        ], 404);
    }

    return $document;
}

function rdCleanType(string $type): string
{
    $type = strtoupper(trim($type));
    $allowed = [
        'DOC',
        'USR',
        'SYS',
        'SEC',
        'SRS',
        'HRS',
        'SWC',
        'TC',
        'TR'
    ];

    if (!in_array($type, $allowed, true)) {
        rdResponse([
            'success' => false,
            'error' => 'Ungültiger Anforderungstyp.'
        ], 422);
    }

    return $type;
}

function rdAllowed(string $value, array $allowed, string $fallback): string
{
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function rdWouldCycle(PDO $pdo, string $parentId, string $childId): bool
{
    if ($parentId === $childId) {
        return true;
    }

    $visited = [];
    $queue = [$childId];

    $statement = $pdo->prepare(
        "SELECT target_document_id
         FROM requirement_document_links
         WHERE source_document_id = ?
           AND link_type = 'parent_of'"
    );

    while ($queue) {
        $currentId = array_shift($queue);

        if (isset($visited[$currentId])) {
            continue;
        }

        $visited[$currentId] = true;

        if ($currentId === $parentId) {
            return true;
        }

        $statement->execute([$currentId]);

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $nextId) {
            $queue[] = $nextId;
        }
    }

    return false;
}

/*
 * Kompatibilitätsfunktionen für die APIs aus Fix V1.
 * get_requirement_documents.php und requirement_templates.php
 * verwenden noch die alten Funktionsnamen.
 */
function reqRespond(array $data, int $status = 200): never
{
    rdResponse($data, $status);
}

function reqAuth(): int
{
    return rdUser();
}

function reqInput(): array
{
    return rdBody();
}

function reqUuid(): string
{
    return rdUuid();
}

function reqProject(PDO $pdo, string $projectId): void
{
    rdProject($pdo, $projectId);
}

function reqCleanType(string $type): string
{
    return rdCleanType($type);
}
