<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config/db.php';

// Einmaliger lokaler Export. Nach Verwendung wieder loeschen.
$projectId = 'proj-c8088ca0aad7';
$expectedUserId = 8;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($currentUserId !== $expectedUserId) {
    http_response_code(403);
    exit('Nicht autorisiert.');
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="NRT-NG_project_snapshot_2026-09-01.json"');

function rows(PDO $pdo, string $sql, array $params = []): array {
    $q = $pdo->prepare($sql);
    $q->execute($params);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}
function ids(array $rows, string $field = 'id'): array {
    return array_values(array_filter(array_map('intval', array_column($rows, $field))));
}
function byIds(PDO $pdo, string $table, string $column, array $idList): array {
    if (!$idList) return [];
    $marks = implode(',', array_fill(0, count($idList), '?'));
    return rows($pdo, "SELECT * FROM `$table` WHERE `$column` IN ($marks)", $idList);
}

$out = [
    'export_info' => [
        'project_id' => $projectId,
        'exported_at' => date('c'),
        'exported_by_user_id' => $currentUserId,
        'purpose' => 'Review von Anforderungen, Akzeptanzkriterien, Tests, Aufgaben, Risiken und Nachweisen',
        'note' => 'Keine Passwoerter, API-Tokens oder vollstaendigen Benutzerdaten exportiert.'
    ]
];

$out['project'] = rows($pdo, 'SELECT * FROM projects WHERE id = ?', [$projectId]);
$out['requirements'] = rows($pdo, 'SELECT * FROM requirements WHERE project_id = ? ORDER BY display_number,id', [$projectId]);
$out['tasks'] = rows($pdo, 'SELECT * FROM project_tasks WHERE project_id = ? ORDER BY id', [$projectId]);
$out['issues'] = rows($pdo, 'SELECT * FROM issues WHERE project_id = ? ORDER BY id', [$projectId]);
$out['attachments'] = rows($pdo, 'SELECT * FROM project_attachments WHERE project_id = ? ORDER BY id', [$projectId]);
$out['evidences'] = rows($pdo, 'SELECT * FROM evidences WHERE project_id = ? ORDER BY id', [$projectId]);
$out['stakeholders'] = rows($pdo, 'SELECT * FROM stakeholders WHERE project_id = ? ORDER BY id', [$projectId]);
$out['project_members'] = rows($pdo, 'SELECT project_id,user_id,project_role,expertise,availability,is_active,joined_at FROM project_members WHERE project_id = ?', [$projectId]);
$out['report_settings'] = rows($pdo, 'SELECT * FROM project_report_settings WHERE project_id = ?', [$projectId]);
$out['sboms'] = rows($pdo, 'SELECT * FROM project_sboms WHERE project_id = ?', [$projectId]);
$out['user_stories'] = rows($pdo, 'SELECT * FROM user_stories WHERE project_id = ? ORDER BY id', [$projectId]);
$out['use_cases'] = rows($pdo, 'SELECT * FROM use_cases WHERE project_id = ? ORDER BY id', [$projectId]);

$reqIds = ids($out['requirements']);
$taskIds = ids($out['tasks']);
$issueIds = ids($out['issues']);
$attachmentIds = ids($out['attachments']);

$out['requirement_relations_parent'] = byIds($pdo, 'requirement_relations', 'parent_requirement_id', $reqIds);
$out['requirement_relations_child'] = byIds($pdo, 'requirement_relations', 'child_requirement_id', $reqIds);
$out['requirement_history'] = byIds($pdo, 'requirement_history', 'requirement_id', $reqIds);
$out['issue_requirements'] = byIds($pdo, 'issue_requirements', 'requirement_id', $reqIds);
$out['issue_tasks'] = byIds($pdo, 'issue_tasks', 'task_id', $taskIds);
$out['issue_comments'] = byIds($pdo, 'issue_comments', 'issue_id', $issueIds);
$out['issue_history'] = byIds($pdo, 'issue_history', 'issue_id', $issueIds);
$out['attachment_links'] = byIds($pdo, 'attachment_links', 'attachment_id', $attachmentIds);

// Falls der V2-Fix bereits installiert ist, Tests ebenfalls exportieren.
$hasRuns = (bool)rows($pdo, "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='test_runs'");
if ($hasRuns) {
    $out['test_runs'] = rows($pdo, 'SELECT * FROM test_runs WHERE project_id = ? ORDER BY id', [$projectId]);
    $runIds = ids($out['test_runs']);
    $out['test_run_files'] = byIds($pdo, 'test_run_files', 'test_run_id', $runIds);
    $out['criterion_test_links'] = byIds($pdo, 'criterion_test_links', 'requirement_id', $reqIds);
}

// Projektrelevantes Audit-Log, begrenzt auf dieses Projekt.
$out['audit_log'] = rows($pdo, 'SELECT * FROM audit_log WHERE project_id = ? ORDER BY created_at,id', [$projectId]);

// Kurzzusammenfassung der manuell gesetzten Kriterien.
$out['criteria_summary'] = [];
foreach ($out['requirements'] as $req) {
    $attrs = json_decode((string)($req['attributes'] ?? '{}'), true) ?: [];
    $states = $attrs['criteria_states'] ?? [];
    $criteria = preg_split('/\R/u', (string)($req['acceptance_criteria'] ?? '')) ?: [];
    foreach ($criteria as $index => $text) {
        $text = trim((string)preg_replace('/^-\s*/u', '', $text));
        if ($text === '') continue;
        $state = $states[(string)$index] ?? $states[$index] ?? null;
        $out['criteria_summary'][] = [
            'requirement_id' => (int)$req['id'],
            'req_key' => $req['req_key'],
            'requirement_title' => $req['title'],
            'criterion_index' => $index,
            'criterion_text' => $text,
            'currently_checked' => !empty($state['checked']),
            'state' => $state
        ];
    }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
