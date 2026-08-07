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

    // 1. Aufgabe laden
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
        'effort_mt' => $task['effort_mt'],
        'total_reqs' => $total_reqs,
        'approved_reqs' => 0,
        'contributors' => [],
        'req_details' => []
    ];

    if ($total_reqs > 0) {
        foreach ($linked_reqs as $req_key) {
            $req_key = trim($req_key);

            // Aktuellen Status holen
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

            // Wenn freigegeben, suchen wir den Helden in der Historie!
            if ($detail['status'] === 'Geprüft & Freigegeben') {
                $analytics['approved_reqs']++;

                // Letzten Historien-Eintrag suchen, der auf diesen Status gesetzt hat
                // Wir joinen mit der User-Tabelle für den Namen (Fallback auf ID, falls Tabelle anders heißt)
                $hStmt = $pdo->prepare("
                    SELECT h.modified_by, h.hostname, h.created_at, 
                           COALESCE(u.username, u.name, 'User') as user_name 
                    FROM requirement_history h 
                    LEFT JOIN users u ON h.modified_by = u.id 
                    WHERE h.req_key = ? AND h.status = 'Geprüft & Freigegeben' 
                    ORDER BY h.id DESC LIMIT 1
                ");
                $hStmt->execute([$req_key]);
                $history = $hStmt->fetch(PDO::FETCH_ASSOC);

                if ($history) {
                    $detail['approved_by'] = $history['user_name'];
                    $detail['hostname'] = $history['hostname'] ?: 'localhost';
                    $detail['date'] = $history['created_at'];

                    // Contributor-Punkte zusammenrechnen
                    $contributor_key = $detail['approved_by'] . ' @ ' . $detail['hostname'];
                    if (!isset($analytics['contributors'][$contributor_key])) {
                        $analytics['contributors'][$contributor_key] = 0;
                    }
                    $analytics['contributors'][$contributor_key]++;
                }
            }
            $analytics['req_details'][] = $detail;
        }
    }
    // --- NEU: Checkliste laden ---
    $subStmt = $pdo->prepare("SELECT id, title, progress_pct FROM project_tasks WHERE parent_id = ? ORDER BY id ASC");
    $subStmt->execute([$task_id]);
    $analytics['subtasks'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fortschritt für das Panel neu berechnen, falls Checkliste vorhanden
    if (count($analytics['subtasks']) > 0) {
        $done = 0;
        foreach ($analytics['subtasks'] as $st) {
            if ($st['progress_pct'] == 100)
                $done++;
        }
        $analytics['checklist_progress'] = round(($done / count($analytics['subtasks'])) * 100);
        $analytics['has_checklist'] = true;
    } else {
        $analytics['has_checklist'] = false;
    }

    echo json_encode(['success' => true, 'analytics' => $analytics]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}