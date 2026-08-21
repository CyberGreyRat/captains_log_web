<?php
// api/set_requirements.php

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

header('Content-Type: application/json; charset=utf-8');

function requirement_json(array $data, int $status = 200): never
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

try {
    if (empty($_SESSION['user_id'])) {
        requirement_json([
            'success' => false,
            'error' => 'Nicht angemeldet.'
        ], 401);
    }

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    set_audit_context(
        $pdo,
        'web',
        basename($_SERVER['SCRIPT_NAME'])
    );

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        throw new Exception('Ungültige JSON-Daten.');
    }

    $id = !empty($data['id'])
        ? (int) $data['id']
        : null;

    $projectId = trim((string) ($data['project_id'] ?? ''));
    $type = strtoupper(trim((string) ($data['type'] ?? 'SYS')));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $rationale = trim((string) ($data['rationale'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'open'));
    $sourceContact = trim((string) ($data['source_contact'] ?? ''));
    $effort = $data['effort'] ?? null;
    $acceptanceCriteria = trim((string) ($data['acceptance_criteria'] ?? ''));
    $reviewStatus = trim((string) ($data['review_status'] ?? 'Neu'));
    $sourceReference = trim((string) ($data['source_reference'] ?? ''));
    $sourceDocument = trim((string) ($data['source_document'] ?? ''));
    $sourcePage = ($data['source_page'] ?? '') !== ''
        ? (int) $data['source_page']
        : null;
    $attributes = is_array($data['attributes'] ?? null)
        ? $data['attributes']
        : [];

    if ($projectId === '' || $title === '') {
        throw new Exception('Projekt-ID und Titel sind Pflichtfelder.');
    }

    $allowedTypes = [
        'USR',
        'SYS',
        'SEC',
        'SRS',
        'HRS',
        'SWC',
        'TC',
        'TR',
        'AST',
        'GOAL',
        'RISK',
        'ENV'
    ];

    if (!in_array($type, $allowedTypes, true)) {
        throw new Exception('Unbekannter Anforderungstyp.');
    }

    $parentIds = array_values(array_unique(array_filter(array_map(
        'intval',
        $data['parent_ids'] ?? []
    ))));

    $childIds = array_values(array_unique(array_filter(array_map(
        'intval',
        $data['child_ids'] ?? []
    ))));

    /* Rückwärtskompatibilität: alte UI sendet noch Keys. */
    $parentKeys = array_values(array_filter(array_map(
        static fn($value) => trim((string) $value),
        $data['parents'] ?? []
    )));

    $childKeys = array_values(array_filter(array_map(
        static fn($value) => trim((string) $value),
        $data['children'] ?? []
    )));

    $resolver = $pdo->prepare(
        'SELECT id
         FROM requirements
         WHERE project_id = ?
           AND (
               req_key = ?
               OR source_reference = ?
           )
         LIMIT 1'
    );

    foreach ($parentKeys as $key) {
        $resolver->execute([$projectId, $key, $key]);
        $resolvedId = (int) $resolver->fetchColumn();
        if ($resolvedId > 0)
            $parentIds[] = $resolvedId;
    }

    foreach ($childKeys as $key) {
        $resolver->execute([$projectId, $key, $key]);
        $resolvedId = (int) $resolver->fetchColumn();
        if ($resolvedId > 0)
            $childIds[] = $resolvedId;
    }

    $parentIds = array_values(array_unique($parentIds));
    $childIds = array_values(array_unique($childIds));

    $pdo->beginTransaction();

    if ($id) {
        $existingStatement = $pdo->prepare(
            'SELECT *
             FROM requirements
             WHERE id = ?
               AND project_id = ?
             FOR UPDATE'
        );

        $existingStatement->execute([$id, $projectId]);
        $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Anforderung wurde nicht gefunden.');
        }

        $serialNumber = (int) $existing['serial_number'];
        $newKey = $type . '-' . str_pad(
            (string) $serialNumber,
            3,
            '0',
            STR_PAD_LEFT
        );

        if ($sourceReference === '') {
            $sourceReference = (string) ($existing['source_reference'] ?? '');
        }

        if ($sourceDocument === '') {
            $sourceDocument = (string) ($existing['source_document'] ?? '');
        }

        if ($sourcePage === null && $existing['source_page'] !== null) {
            $sourcePage = (int) $existing['source_page'];
        }

        $existingAttributes = json_decode(
            $existing['attributes'] ?? '{}',
            true
        );

        if (!is_array($existingAttributes)) {
            $existingAttributes = [];
        }

        $attributes = array_replace(
            $existingAttributes,
            $attributes
        );

        $update = $pdo->prepare(
            'UPDATE requirements
             SET
                req_key = ?,
                type = ?,
                title = ?,
                description = ?,
                rationale = ?,
                status = ?,
                source_contact = ?,
                effort = ?,
                acceptance_criteria = ?,
                review_status = ?,
                source_reference = ?,
                source_document = ?,
                source_page = ?,
                attributes = ?
             WHERE id = ?
               AND project_id = ?'
        );

        $update->execute([
            $newKey,
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $sourceContact,
            $effort === '' ? null : $effort,
            $acceptanceCriteria,
            $reviewStatus,
            $sourceReference ?: null,
            $sourceDocument ?: null,
            $sourcePage,
            json_encode(
                $attributes,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            $id,
            $projectId
        ]);

        $savedId = $id;
    } else {
        /* Projektzeile sperren, damit die Nummer nicht doppelt vergeben wird. */
        $projectLock = $pdo->prepare(
            'SELECT id
             FROM projects
             WHERE id = ?
             FOR UPDATE'
        );
        $projectLock->execute([$projectId]);

        if (!$projectLock->fetchColumn()) {
            throw new Exception('Projekt wurde nicht gefunden.');
        }

        $numberStatement = $pdo->prepare(
            'SELECT COALESCE(MAX(serial_number), 0) + 1
             FROM requirements
             WHERE project_id = ?'
        );
        $numberStatement->execute([$projectId]);
        $serialNumber = (int) $numberStatement->fetchColumn();

        $newKey = $type . '-' . str_pad(
            (string) $serialNumber,
            3,
            '0',
            STR_PAD_LEFT
        );

       $insert = $pdo->prepare(
            'INSERT INTO requirements (
                project_id,
                serial_number,
                display_number, 
                req_key,
                source_reference,
                source_document,
                source_page,
                type,
                title,
                description,
                rationale,
                status,
                source_contact,
                effort,
                acceptance_criteria,
                review_status,
                parents,
                children,
                attributes
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?
             )'
        );

        $insert->execute([
            $projectId,
            $serialNumber,
            $serialNumber, 
            $newKey,
            $sourceReference ?: null,
            $sourceDocument ?: null,
            $sourcePage,
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $sourceContact,
            $effort === '' ? null : $effort,
            $acceptanceCriteria,
            $reviewStatus,
            '[]',
            '[]',
            json_encode(
                $attributes,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        ]);

        $savedId = (int) $pdo->lastInsertId();
    }

    $parentIds = array_values(array_filter(
        $parentIds,
        static fn($relatedId) => $relatedId !== $savedId
    ));

    $childIds = array_values(array_filter(
        $childIds,
        static fn($relatedId) => $relatedId !== $savedId
    ));

    $pdo->prepare(
        'DELETE FROM requirement_relations
         WHERE child_requirement_id = ?
            OR parent_requirement_id = ?'
    )->execute([$savedId, $savedId]);

    $relationInsert = $pdo->prepare(
        'INSERT IGNORE INTO requirement_relations (
            parent_requirement_id,
            child_requirement_id,
            relation_type,
            created_by
         ) VALUES (?, ?, ?, ?)'
    );

    foreach ($parentIds as $parentId) {
        $relationInsert->execute([
            $parentId,
            $savedId,
            'fulfills',
            (int) $_SESSION['user_id']
        ]);
    }

    foreach ($childIds as $childId) {
        $relationInsert->execute([
            $savedId,
            $childId,
            'fulfills',
            (int) $_SESSION['user_id']
        ]);
    }

    /* JSON-Snapshots für bestehenden Frontend-Code aktuell halten. */
    // 1. Alle potenziell betroffenen IDs sammeln (Aktuelles Element + alte & neue Verwandte)
    $stmtOldRels = $pdo->prepare("SELECT parent_requirement_id, child_requirement_id FROM requirement_relations WHERE child_requirement_id = ? OR parent_requirement_id = ?");
    $stmtOldRels->execute([$savedId, $savedId]);

    $affectedIds = [$savedId];
    foreach ($stmtOldRels->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $affectedIds[] = $r['parent_requirement_id'];
        $affectedIds[] = $r['child_requirement_id'];
    }
    $affectedIds = array_unique(array_merge($affectedIds, $parentIds, $childIds));

    // 2. Für jede betroffene ID die neuen Parents/Children berechnen
    $stmtGetParents = $pdo->prepare('SELECT p.req_key FROM requirement_relations r JOIN requirements p ON p.id = r.parent_requirement_id WHERE r.child_requirement_id = ? ORDER BY p.serial_number');
    $stmtGetChildren = $pdo->prepare('SELECT c.req_key FROM requirement_relations r JOIN requirements c ON c.id = r.child_requirement_id WHERE r.parent_requirement_id = ? ORDER BY c.serial_number');

    $stmtCheck = $pdo->prepare("SELECT parents, children FROM requirements WHERE id = ?");
    $stmtUpdateRel = $pdo->prepare("UPDATE requirements SET parents = ?, children = ? WHERE id = ?");

    // 3. MAGIC TRICK: Wir updaten nur, wenn sich WIRKLICH etwas geändert hat!
    foreach ($affectedIds as $affId) {
        $stmtCheck->execute([$affId]);
        $currentRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$currentRow)
            continue;

        $stmtGetParents->execute([$affId]);
        $newParentsArr = $stmtGetParents->fetchAll(PDO::FETCH_COLUMN);
        $newParentsJson = empty($newParentsArr) ? "[]" : json_encode($newParentsArr, JSON_UNESCAPED_UNICODE);

        $stmtGetChildren->execute([$affId]);
        $newChildrenArr = $stmtGetChildren->fetchAll(PDO::FETCH_COLUMN);
        $newChildrenJson = empty($newChildrenArr) ? "[]" : json_encode($newChildrenArr, JSON_UNESCAPED_UNICODE);

        // Nur wenn die Links anders sind als vorher, lösen wir den Datenbank-Trigger aus!
        if ($currentRow['parents'] !== $newParentsJson || $currentRow['children'] !== $newChildrenJson) {
            $stmtUpdateRel->execute([$newParentsJson, $newChildrenJson, $affId]);
        }
    }

    $pdo->commit();

    requirement_json([
        'success' => true,
        'id' => $savedId,
        'req_key' => $newKey,
        'serial_number' => $serialNumber,
        'type' => $type,
        'message' => $id
            ? 'Anforderung erfolgreich aktualisiert.'
            : 'Anforderung erfolgreich angelegt.'
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    requirement_json([
        'success' => false,
        'error' => $error->getMessage()
    ], 500);
}
