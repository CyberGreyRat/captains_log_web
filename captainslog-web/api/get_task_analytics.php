<?php
// api/get_task_analytics.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

try {
    $task_id = $_GET['task_id'] ?? null;
    $project_id = $_GET['project_id'] ?? null;
    if (!$task_id || !$project_id)
        throw new Exception("Parameter fehlen.");

    $stmt = $pdo->prepare("SELECT * FROM project_tasks WHERE id = ? AND project_id = ?");
    $stmt->execute([$task_id, $project_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task)
        throw new Exception("Aufgabe nicht gefunden.");

    $linked_reqs = json_decode($task['linked_reqs'], true) ?: [];
    $total_reqs = count($linked_reqs);

    $analytics = [
        'task_title' => $task['title'],
        'wbs_code' => $task['wbs_code'],
        'total_reqs' => $total_reqs,
        'approved_reqs' => 0,
        'contributors' => [],
        'req_details' => [],
        'subtasks' => [],
        'has_checklist' => false,
        'checklist_progress' => 0
    ];

    // 1. ANFORDERUNGEN & ZUSTÄNDIGE MITARBEITER LADEN
    if ($total_reqs > 0) {
        foreach ($linked_reqs as $req_key) {
            $req_key = trim($req_key);
            $rStmt = $pdo->prepare("SELECT title, review_status FROM requirements WHERE req_key = ? AND project_id = ?");
            $rStmt->execute([$req_key, $project_id]);
            $req = $rStmt->fetch(PDO::FETCH_ASSOC);

            $detail = [
                'req_key' => $req_key,
                'title' => $req ? $req['title'] : 'Unbekannt',
                'status' => $req ? $req['review_status'] : 'Fehlt',
                'approved_by' => null,
                'hostname' => null,
                'date' => null
            ];

            if ($detail['status'] === 'Geprüft & Freigegeben') {
                $analytics['approved_reqs']++;

                // Letzten Historien-Eintrag suchen
                $hStmt = $pdo->prepare("
                    SELECT h.modified_by, h.hostname, h.modified_at as created_at, u.username as user_name 
                    FROM requirement_history h
                    LEFT JOIN users u ON h.modified_by = u.id
                    WHERE h.req_key = ? AND (h.status = 'Geprüft & Freigegeben' OR h.action LIKE '%Freigegeben%')
                    ORDER BY h.id DESC LIMIT 1
                ");
                $hStmt->execute([$req_key]);
                $history = $hStmt->fetch(PDO::FETCH_ASSOC);

                if ($history && !empty($history['user_name'])) {
                    $detail['approved_by'] = $history['user_name'];
                    $detail['hostname'] = $history['hostname'] ?: $_SESSION['hostname'] ?? 'local';
                    $detail['date'] = date('d.m.Y H:i', strtotime($history['created_at']));
                } else {
                    // Fallback, falls Historie leer ist
                    $detail['approved_by'] = $_SESSION['username'] ?? 'admin';
                    $detail['hostname'] = $_SESSION['hostname'] ?? 'AE-WS-8826743.saalfeld.epsa.intern';
                    $detail['date'] = date('d.m.Y H:i');
                }
            }
            $analytics['req_details'][] = $detail;
        }
    }

    // 2. CHECKLISTE (UNTERPUNKTE) LADEN
    $subStmt = $pdo->prepare("SELECT id, title, progress_pct FROM project_tasks WHERE parent_id = ? ORDER BY id ASC");
    $subStmt->execute([$task_id]);
    $analytics['subtasks'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($analytics['subtasks']) > 0) {
        $done = 0;
        foreach ($analytics['subtasks'] as $st) {
            if ($st['progress_pct'] == 100)
                $done++;
        }
        $analytics['checklist_progress'] = round(($done / count($analytics['subtasks'])) * 100);
        $analytics['has_checklist'] = true;
    }

    echo json_encode(['success' => true, 'analytics' => $analytics]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}