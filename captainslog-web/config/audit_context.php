<?php
// config/audit_context.php

declare(strict_types=1);

function set_audit_context(PDO $pdo, string $sourceType = 'web', ?string $sourceName = null, ?string $batchId = null): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $requestId = bin2hex(random_bytes(16));
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $actorName = $_SESSION['username'] ?? null;
    $hostname = $_SESSION['hostname'] ?? (gethostname() ?: 'unbekannt');

    $statement = $pdo->prepare(
        'SET @audit_user_id = ?, @audit_actor_name = ?, @audit_source_type = ?,
             @audit_source_name = ?, @audit_hostname = ?, @audit_request_id = ?,
             @audit_batch_id = ?'
    );
    $statement->execute([
        $userId,
        $actorName,
        $sourceType,
        $sourceName,
        $hostname,
        $requestId,
        $batchId
    ]);

    return $requestId;
}
