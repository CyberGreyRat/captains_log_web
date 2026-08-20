<?php
// api/set_requirements.php

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

require '../config/db.php';
require '../config/audit_context.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    set_audit_context(
        $pdo,
        'web',
        basename($_SERVER['SCRIPT_NAME'])
    );

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['project_id']) || empty($data['title'])) {
        throw new Exception('Projekt-ID und Titel fehlen!');
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $type = $data['type'] ?? 'SYS';
    $title = $data['title'];
    $description = $data['description'] ?? '';
    $rationale = $data['rationale'] ?? '';
    $status = $data['status'] ?? 'open';
    $source_contact = $data['source_contact'] ?? '';
    $effort = $data['effort'] ?? '';
    $acceptance_criteria = $data['acceptance_criteria'] ?? '';
    $review_status = $data['review_status'] ?? 'Neu';

    $parents_array = (
        isset($data['parents']) &&
        is_array($data['parents'])
    ) ? $data['parents'] : [];

    $children_array = (
        isset($data['children']) &&
        is_array($data['children'])
    ) ? $data['children'] : [];

    $parents = json_encode(
        $parents_array,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $children = json_encode(
        $children_array,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $user_id = $_SESSION['user_id'] ?? 1;
    $hostname = $_SESSION['hostname'] ?? 'LocalPC';

    $existing_attrs = [];
    $req_key = '';
    $old_row = null;

    if ($id) {
        $stmtOld = $pdo->prepare(
            'SELECT *
             FROM requirements
             WHERE id = ?
               AND project_id = ?'
        );

        $stmtOld->execute([
            $id,
            $project_id
        ]);

        $old_row = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if ($old_row) {
            $req_key = $old_row['req_key'];

            if (!empty($old_row['attributes'])) {
                $decoded = json_decode(
                    $old_row['attributes'],
                    true
                );

                if (is_array($decoded)) {
                    $existing_attrs = $decoded;
                }
            }
        }
    }

    if (
        isset($data['attributes']) &&
        is_array($data['attributes'])
    ) {
        foreach ($data['attributes'] as $key => $value) {
            $existing_attrs[$key] = $value;
        }
    }

    $attributes_json = json_encode(
        $existing_attrs,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $pdo->beginTransaction();

    if ($id && $old_row) {
        $changes = [];

        if ((string) $old_row['type'] !== (string) $type) {
            $changes[] = 'Typ geändert';
        }

        if ((string) $old_row['title'] !== (string) $title) {
            $changes[] = 'Titel geändert';
        }

        if ((string) $old_row['description'] !== (string) $description) {
            $changes[] = 'Beschreibung geändert';
        }

        if ((string) $old_row['rationale'] !== (string) $rationale) {
            $changes[] = 'Begründung geändert';
        }

        if ((string) $old_row['status'] !== (string) $status) {
            $changes[] = 'Status geändert';
        }

        if ((string) $old_row['source_contact'] !== (string) $source_contact) {
            $changes[] = 'Zuständigkeit geändert';
        }

        if ((string) $old_row['effort'] !== (string) $effort) {
            $changes[] = 'Aufwand geändert';
        }

        if (
            trim((string) $old_row['acceptance_criteria']) !==
            trim((string) $acceptance_criteria)
        ) {
            $changes[] = 'Akzeptanzkriterien geändert';
        }

        if (
            (string) $old_row['review_status'] !==
            (string) $review_status
        ) {
            $changes[] = 'Prüfstatus geändert';
        }

        $old_parents = json_decode(
            $old_row['parents'] ?? '[]',
            true
        ) ?: [];

        $old_children = json_decode(
            $old_row['children'] ?? '[]',
            true
        ) ?: [];

        if ($old_parents !== $parents_array) {
            $changes[] = 'Parents geändert';
        }

        if ($old_children !== $children_array) {
            $changes[] = 'Children geändert';
        }

        $old_attributes = json_decode(
            $old_row['attributes'] ?? '{}',
            true
        ) ?: [];

        if ($old_attributes !== $existing_attrs) {
            $changes[] = 'Spezifische Attribute geändert';
        }

        if (empty($changes)) {
            $pdo->rollBack();

            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Keine Änderungen erkannt.'
            ]);

            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE requirements
             SET
                type = ?,
                title = ?,
                description = ?,
                rationale = ?,
                status = ?,
                source_contact = ?,
                effort = ?,
                acceptance_criteria = ?,
                review_status = ?,
                parents = ?,
                children = ?,
                attributes = ?
             WHERE id = ?
               AND project_id = ?'
        );

        $stmt->execute([
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $source_contact,
            $effort,
            $acceptance_criteria,
            $review_status,
            $parents,
            $children,
            $attributes_json,
            $id,
            $project_id
        ]);

        $action = 'Geändert: ' . implode(', ', $changes);

        $histStmt = $pdo->prepare(
            'INSERT INTO requirement_history (
                requirement_id,
                req_key,
                project_id,
                type,
                title,
                description,
                rationale,
                status,
                parents,
                children,
                modified_by,
                action,
                attributes,
                hostname
             )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $histStmt->execute([
            $id,
            $req_key,
            $project_id,
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $parents,
            $children,
            $user_id,
            $action,
            $attributes_json,
            $hostname
        ]);

        $saved_id = (int) $id;
        $message = 'Anforderung erfolgreich aktualisiert.';
    } else {
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM requirements
             WHERE project_id = ?
               AND type = ?'
        );

        $countStmt->execute([
            $project_id,
            $type
        ]);

        $count = (int) $countStmt->fetchColumn() + 1;

        $req_key = $type . '-' . str_pad(
            (string) $count,
            3,
            '0',
            STR_PAD_LEFT
        );

        $stmt = $pdo->prepare(
            'INSERT INTO requirements (
                project_id,
                req_key,
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
             )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $project_id,
            $req_key,
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $source_contact,
            $effort,
            $acceptance_criteria,
            $review_status,
            $parents,
            $children,
            $attributes_json
        ]);

        $saved_id = (int) $pdo->lastInsertId();

        $histStmt = $pdo->prepare(
            'INSERT INTO requirement_history (
                requirement_id,
                req_key,
                project_id,
                type,
                title,
                description,
                rationale,
                status,
                parents,
                children,
                modified_by,
                action,
                attributes,
                hostname
             )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $histStmt->execute([
            $saved_id,
            $req_key,
            $project_id,
            $type,
            $title,
            $description,
            $rationale,
            $status,
            $parents,
            $children,
            $user_id,
            'CREATE (Neu angelegt)',
            $attributes_json,
            $hostname
        ]);

        $message = 'Anforderung erfolgreich angelegt.';
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id' => $saved_id,
        'message' => $message
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}
