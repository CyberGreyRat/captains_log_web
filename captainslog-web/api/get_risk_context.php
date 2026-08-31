<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function risk_context_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $role = (string)($_SESSION['role'] ?? 'viewer');
    $projectId = trim((string)($_GET['project_id'] ?? ''));
    $riskId = max(0, (int)($_GET['risk_id'] ?? 0));
    if ($userId <= 0) risk_context_json(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    if ($projectId === '') risk_context_json(['success' => false, 'error' => 'Projekt-ID fehlt.'], 400);

    if ($role !== 'admin') {
        $access = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');
        $access->execute([$projectId, $userId]);
        if (!$access->fetchColumn()) risk_context_json(['success' => false, 'error' => 'Kein Projektzugriff.'], 403);
    }

    $requirements = $pdo->prepare("SELECT id,req_key,type,title,review_status FROM requirements WHERE project_id=? AND type NOT IN ('RISK','TC','TR') ORDER BY type,serial_number,id");
    $requirements->execute([$projectId]);
    $verification = $pdo->prepare("SELECT id,req_key,type,title,review_status FROM requirements WHERE project_id=? AND type IN ('TC','TR') ORDER BY type,serial_number,id");
    $verification->execute([$projectId]);
    $tasks = $pdo->prepare('SELECT id,wbs_code,title,category,progress_pct FROM project_tasks WHERE project_id=? AND parent_id IS NULL ORDER BY wbs_code,id');
    $tasks->execute([$projectId]);
    $issues = $pdo->prepare('SELECT id,issue_key,title,status,issue_type FROM issues WHERE project_id=? ORDER BY updated_at DESC,id DESC');
    $issues->execute([$projectId]);

    $links = ['requirements' => [], 'verification' => [], 'tasks' => [], 'issues' => []];
    if ($riskId > 0) {
        $owned = $pdo->prepare("SELECT 1 FROM requirements WHERE id=? AND project_id=? AND type='RISK'");
        $owned->execute([$riskId, $projectId]);
        if (!$owned->fetchColumn()) throw new RuntimeException('Risiko wurde im Projekt nicht gefunden.');

        $q = $pdo->prepare('SELECT requirement_id,link_group FROM risk_requirement_links WHERE risk_id=?');
        $q->execute([$riskId]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $target = $row['link_group'] === 'verification' ? 'verification' : 'requirements';
            $links[$target][] = (int)$row['requirement_id'];
        }
        $q = $pdo->prepare('SELECT task_id FROM risk_task_links WHERE risk_id=?');
        $q->execute([$riskId]);
        $links['tasks'] = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
        $q = $pdo->prepare('SELECT issue_id FROM risk_issue_links WHERE risk_id=?');
        $q->execute([$riskId]);
        $links['issues'] = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    }

    risk_context_json([
        'success' => true,
        'requirements' => $requirements->fetchAll(PDO::FETCH_ASSOC),
        'verification' => $verification->fetchAll(PDO::FETCH_ASSOC),
        'tasks' => $tasks->fetchAll(PDO::FETCH_ASSOC),
        'issues' => $issues->fetchAll(PDO::FETCH_ASSOC),
        'links' => $links
    ]);
} catch (Throwable $error) {
    risk_context_json(['success' => false, 'error' => $error->getMessage()], 400);
}
