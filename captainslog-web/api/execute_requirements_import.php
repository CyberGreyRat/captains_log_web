<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';
require_once __DIR__ . '/requirements_import_common.php';
$uid = imp_user();
try {
    $d = json_decode(file_get_contents('php://input'), true) ?: [];
    $project = imp_clean($d['project_id'] ?? '');
    $rows = $d['rows'] ?? [];
    $mode = $d['import_mode'] ?? 'skip';
    if (!$project || !is_array($rows) || !in_array($mode, ['skip', 'update'], true))
        throw new Exception('Importdaten sind ungültig.');
    if (count($rows) > 3000)
        throw new Exception('Maximal 3000 Zeilen je Import.');
    $batch = 'reqimp-' . bin2hex(random_bytes(14));
    $source = imp_clean($d['source_name'] ?? 'Import');
    set_audit_context($pdo, $d['source_format'] ?? 'import', $source, $batch);
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT id FROM requirements WHERE project_id=? AND req_key=? LIMIT 1');
    $count = $pdo->prepare('SELECT COUNT(*)+1 FROM requirements WHERE project_id=? AND type=?');
    $insert = $pdo->prepare('INSERT INTO requirements(project_id,req_key,type,title,description,rationale,status,source_contact,effort,acceptance_criteria,review_status,parents,children,attributes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $update = $pdo->prepare('UPDATE requirements SET type=?,title=?,description=?,rationale=?,source_contact=?,effort=?,acceptance_criteria=?,review_status=? WHERE id=? AND project_id=?');
    $created = $updated = $skipped = $failed = 0;
    $errors = [];
    foreach ($rows as $i => $row) {
        try {
            if (empty($row['import']))
                continue;
            $type = strtoupper(imp_clean($row['type'] ?? 'SYS')) ?: 'SYS';
            $key = imp_clean($row['req_key'] ?? '');
            $title = imp_clean($row['title'] ?? '');
            $description = imp_clean($row['description'] ?? '');
            if ($title === '' && $description !== '')
                $title = mb_substr($description, 0, 240);
            if ($title === '')
                throw new Exception('Titel fehlt.');
            if ($key === '') {
                $count->execute([$project, $type]);
                $key = $type . '-' . str_pad((string) $count->fetchColumn(), 3, '0', STR_PAD_LEFT);
            }
            $find->execute([$project, $key]);
            $id = $find->fetchColumn();
            if ($id) {
                if ($mode === 'update') {
                    $update->execute([$type, $title, $description, $row['rationale'] ?? '', $row['source_contact'] ?? '', $row['effort'] ?? '', $row['acceptance_criteria'] ?? '', $row['review_status'] ?? 'Neu', $id, $project]);
                    $updated++;
                } else
                    $skipped++;
            } else {
                $attrs = json_encode(['import_batch_id' => $batch, 'source_name' => $source], JSON_UNESCAPED_UNICODE);
                $insert->execute([$project, $key, $type, $title, $description, $row['rationale'] ?? '', 'open', $row['source_contact'] ?? '', $row['effort'] ?? '', $row['acceptance_criteria'] ?? '', $row['review_status'] ?? 'Neu', '[]', '[]', $attrs]);
                $created++;
            }
        } catch (Throwable $e) {
            $failed++;
            $errors[] = ['row' => $i + 1, 'error' => $e->getMessage()];
            if (!empty($d['abort_on_error']))
                throw $e;
        }
    }
    $status = $failed ? 'completed_with_errors' : 'completed';
    $q = $pdo->prepare('INSERT INTO requirement_import_batches(id,project_id,original_filename,source_format,extraction_mode,profile_id,import_mode,total_rows,created_rows,updated_rows,skipped_rows,failed_rows,status,result_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$batch, $project, $source, $d['source_format'] ?? 'text', $d['extraction_mode'] ?? 'table', $d['profile_id'] ?? null, $mode, count($rows), $created, $updated, $skipped, $failed, $status, json_encode($errors, JSON_UNESCAPED_UNICODE), $uid]);
    $pdo->commit();
    imp_json(['success' => true, 'batch_id' => $batch, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction())
        $pdo->rollBack();
    imp_json(['success' => false, 'error' => $e->getMessage()], 400);
}
