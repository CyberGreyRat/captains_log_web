<?php
// api/get_health_diagnostics.php
ini_set('display_errors', 0); error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode(['success' => false, 'error' => 'Projekt-ID fehlt.']);
    exit;
}

try {
    $warnings = [];

    // 1. Offene Issues ohne Zuweisung
    $stmtIss = $pdo->prepare("SELECT id, issue_key, title FROM issues WHERE project_id = ? AND status IN ('open', 'in_progress') AND (assignee_user_id IS NULL OR assignee_user_id = 0) AND (external_assignee IS NULL OR external_assignee = '')");
    $stmtIss->execute([$project_id]);
    foreach ($stmtIss->fetchAll(PDO::FETCH_ASSOC) as $iss) {
        $warnings[] = [
            'type' => 'issue',
            'severity' => 'critical',
            'message' => "Issue <b>{$iss['issue_key']}</b> ist offen, hat aber keine Zuweisung.",
            'id' => $iss['id']
        ];
    }

    // 2. Aufgaben ohne Kategorie (Bereich)
    $stmtTask = $pdo->prepare("SELECT id, wbs_code, title FROM project_tasks WHERE project_id = ? AND (category IS NULL OR category = '') AND parent_id IS NULL");
    $stmtTask->execute([$project_id]);
    foreach ($stmtTask->fetchAll(PDO::FETCH_ASSOC) as $task) {
        $warnings[] = [
            'type' => 'task',
            'severity' => 'warning',
            'message' => "Aufgabe <b>" . ($task['wbs_code'] ?: 'Neu') . "</b> (" . htmlspecialchars($task['title']) . ") hat keine Kategorie.",
            'id' => $task['id']
        ];
    }

    // 3. Traceability: Freigegebene Anforderungen ohne verknüpfte Aufgabe
    // Zuerst alle freigegebenen Reqs holen
    $stmtReq = $pdo->prepare("SELECT req_key, title FROM requirements WHERE project_id = ? AND LOWER(review_status) IN ('geprüft & freigegeben', 'freigegeben', 'genehmigt')");
    $stmtReq->execute([$project_id]);
    $approvedReqs = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    // Dann alle verknüpften Reqs aus den Tasks holen
    $stmtLinks = $pdo->prepare("SELECT linked_reqs FROM project_tasks WHERE project_id = ? AND linked_reqs IS NOT NULL AND linked_reqs != ''");
    $stmtLinks->execute([$project_id]);
    $allLinkedReqs = [];
    foreach ($stmtLinks->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $arr = json_decode($row['linked_reqs'], true);
        if (is_array($arr)) {
            $allLinkedReqs = array_merge($allLinkedReqs, $arr);
        }
    }
    $allLinkedReqs = array_unique($allLinkedReqs);

    foreach ($approvedReqs as $req) {
        if (!in_array($req['req_key'], $allLinkedReqs)) {
            $warnings[] = [
                'type' => 'requirement',
                'severity' => 'warning',
                'message' => "Traceability-Lücke: Anforderung <b>{$req['req_key']}</b> ist freigegeben, wird aber in keiner Aufgabe umgesetzt.",
                'id' => $req['req_key']
            ];
        }
    }

    echo json_encode(['success' => true, 'warnings' => $warnings]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>