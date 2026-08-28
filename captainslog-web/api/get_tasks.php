<?php
// api/get_tasks.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) { echo json_encode(['success' => false, 'error' => 'Keine project_id.']); exit; }

try {
    // 1. Aufgaben laden
    $stmt = $pdo->prepare("SELECT * FROM project_tasks WHERE project_id = ? ORDER BY wbs_code ASC, id ASC");
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Templates laden
    $stmtTpl = $pdo->query("SELECT * FROM task_templates ORDER BY category, title");
    $templates = $stmtTpl->fetchAll(PDO::FETCH_ASSOC);

    // 3. Requirements laden (für Fortschritt)
    $stmtReq = $pdo->prepare("SELECT req_key, review_status FROM requirements WHERE project_id = ?");
    $stmtReq->execute([$project_id]);
    $req_map = [];
    foreach($stmtReq->fetchAll(PDO::FETCH_ASSOC) as $r) $req_map[$r['req_key']] = $r['review_status'];

// --- NEU: 4. Verknüpfte Issues laden (inkl. Verantwortliche) ---
    $stmtIss = $pdo->prepare("
        SELECT it.task_id, i.id as issue_id, i.status, u.username as issue_assignee 
        FROM issue_tasks it
        JOIN issues i ON it.issue_id = i.id
        LEFT JOIN users u ON i.assignee_user_id = u.id
        WHERE i.project_id = ?
    ");
    $stmtIss->execute([$project_id]);
    $issue_map = [];
    foreach($stmtIss->fetchAll(PDO::FETCH_ASSOC) as $iss) {
        $tid = $iss['task_id'];
        if(!isset($issue_map[$tid])) $issue_map[$tid] = [];
        $issue_map[$tid][] = $iss;
    }

    // 5. Unteraufgaben (Checklisten) ihren Eltern zuordnen
    $children_map = [];
    foreach($tasks as $t) {
        if ($t['parent_id']) {
            if (!isset($children_map[$t['parent_id']])) $children_map[$t['parent_id']] = [];
            $children_map[$t['parent_id']][] = $t;
        }
    }

// 6. Fortschritt berechnen (Kombination aus Reqs, Issues & Checklisten)
    foreach($tasks as &$t) {
        $tid = $t['id'];
        
        // Die Issue-IDs als JSON-String an das Task-Array anhängen (fürs Frontend)
        $linked_issues_data = $issue_map[$tid] ?? [];
        $t['linked_issues'] = json_encode(array_column($linked_issues_data, 'issue_id'));

        // Basis-Werte für Checkliste
        $t['has_checklist'] = false;
        $t['checklist_done'] = 0;
        $t['checklist_total'] = 0;

        if (isset($children_map[$tid])) {
            $t['has_checklist'] = true;
            $t['checklist_total'] = count($children_map[$tid]);
            foreach($children_map[$tid] as $sub) {
                if ($sub['progress_pct'] == 100) $t['checklist_done']++;
            }
        }

        // Auto-Progress aus ALLEM berechnen (Topf-Prinzip)
        if ($t['is_auto_progress'] == 1) {
            $linked_reqs = json_decode($t['linked_reqs'], true) ?: [];
            
            $total_items = $t['checklist_total'] + count($linked_reqs) + count($linked_issues_data);

            if ($total_items > 0) {
                $done_items = $t['checklist_done'];
                
                // Requirements prüfen
                foreach($linked_reqs as $key) {
                    if (isset($req_map[trim($key)]) && $req_map[trim($key)] === 'Geprüft & Freigegeben') {
                        $done_items++;
                    }
                }
                
                // Issues prüfen
                foreach($linked_issues_data as $iss) {
                    if (in_array($iss['status'], ['closed', 'approved', 'rejected'])) {
                        $done_items++;
                    }
                }
                
                $t['progress_pct'] = round(($done_items / $total_items) * 100);
            } else {
                $t['progress_pct'] = 0;
            }
        }
    }

    echo json_encode(['success' => true, 'tasks' => $tasks, 'templates' => $templates]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
