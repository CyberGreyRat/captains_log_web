<?php
// api/get_task_analytics.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

$task_id = $_GET['task_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
if (!$task_id || !$project_id) { echo json_encode(['success' => false]); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM project_tasks WHERE id = ? AND project_id = ?");
    $stmt->execute([$task_id, $project_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) throw new Exception("Task not found");

    $analytics = [
        'task_title' => $task['title'],
        'wbs_code' => $task['wbs_code'],
        'has_checklist' => false,
        'checklist_progress' => 0,
        'subtasks' => [],
        'total_reqs' => 0,
        'approved_reqs' => 0,
        'total_issues' => 0,
        'closed_issues' => 0,
        'req_details' => [],
        'issue_details' => [],
        'contributors' => []
    ];

    // 1. Checkliste laden
    $stmtSub = $pdo->prepare("SELECT id, title, progress_pct FROM project_tasks WHERE parent_id = ? ORDER BY id ASC");
    $stmtSub->execute([$task_id]);
    $subs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
    if (count($subs) > 0) {
        $analytics['has_checklist'] = true;
        $analytics['subtasks'] = $subs;
        $done = 0;
        foreach($subs as $s) { if($s['progress_pct'] == 100) $done++; }
        $analytics['checklist_progress'] = round(($done / count($subs)) * 100);
    }

    // 2. Requirements laden
    $reqs = json_decode($task['linked_reqs'], true) ?: [];
    $analytics['total_reqs'] = count($reqs);
    if (count($reqs) > 0) {
        $inClause = str_repeat('?,', count($reqs) - 1) . '?';
        $stmtReq = $pdo->prepare("SELECT req_key, title, status, review_status, attributes FROM requirements WHERE project_id = ? AND req_key IN ($inClause)");
        $params = array_merge([$project_id], $reqs);
        $stmtReq->execute($params);
        
        foreach($stmtReq->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $isAppr = ($r['review_status'] === 'Geprüft & Freigegeben');
            if ($isAppr) $analytics['approved_reqs']++;
            
            $apprBy = 'Unbekannt';
            $apprDate = '-';
            if ($isAppr) {
                $attrs = json_decode($r['attributes'], true) ?: [];
                if (isset($attrs['criteria_states'])) {
                    foreach($attrs['criteria_states'] as $state) {
                        if (isset($state['checked']) && $state['checked']) {
                            $apprBy = $state['by'] ?? $apprBy;
                            $apprDate = $state['date'] ?? $apprDate;
                        }
                    }
                }
                if ($apprBy !== 'Unbekannt') {
                    $analytics['contributors'][$apprBy] = ($analytics['contributors'][$apprBy] ?? 0) + 1;
                }
            }
            
            $analytics['req_details'][] = [
                'req_key' => $r['req_key'],
                'title' => $r['title'],
                'status' => $r['review_status'],
                'approved_by' => $apprBy,
                'date' => $apprDate
            ];
        }
    }

    // 3. Issues laden
    $stmtIss = $pdo->prepare("SELECT i.id, i.issue_key, i.title, i.status, u.username FROM issue_tasks it JOIN issues i ON it.issue_id = i.id LEFT JOIN users u ON i.assignee_user_id = u.id WHERE it.task_id = ?");
    $stmtIss->execute([$task_id]);
    $issues = $stmtIss->fetchAll(PDO::FETCH_ASSOC);
    $analytics['total_issues'] = count($issues);
    
    foreach($issues as $iss) {
        $isClosed = in_array($iss['status'], ['closed', 'approved', 'rejected']);
        if ($isClosed) {
            $analytics['closed_issues']++;
            $user = $iss['username'] ?: 'Unbekannt';
            if ($user !== 'Unbekannt') {
                $analytics['contributors'][$user] = ($analytics['contributors'][$user] ?? 0) + 1;
            }
        }
        $analytics['issue_details'][] = [
            'issue_key' => $iss['issue_key'],
            'title' => $iss['title'],
            'status' => $iss['status'],
            'assignee' => $iss['username'] ?: 'Niemand'
        ];
    }

    echo json_encode(['success' => true, 'analytics' => $analytics]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}