<?php
// api/get_tasks.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) { echo json_encode(['success' => false, 'error' => 'Keine project_id.']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM project_tasks WHERE project_id = ? ORDER BY wbs_code ASC, id ASC");
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtTpl = $pdo->query("SELECT * FROM task_templates ORDER BY category, title");
    $templates = $stmtTpl->fetchAll(PDO::FETCH_ASSOC);

    $stmtReq = $pdo->prepare("SELECT req_key, review_status FROM requirements WHERE project_id = ?");
    $stmtReq->execute([$project_id]);
    $req_map = [];
    foreach($stmtReq->fetchAll(PDO::FETCH_ASSOC) as $r) $req_map[$r['req_key']] = $r['review_status'];

    // Unteraufgaben (Checklisten) ihren Eltern zuordnen
    $children_map = [];
    foreach($tasks as $t) {
        if ($t['parent_id']) {
            if (!isset($children_map[$t['parent_id']])) $children_map[$t['parent_id']] = [];
            $children_map[$t['parent_id']][] = $t;
        }
    }

    // Fortschritt berechnen (Checkliste überschreibt Requirements)
    foreach($tasks as &$t) {
        if (isset($children_map[$t['id']])) {
            // Hat Checklisten-Punkte! Fortschritt = Abgehakte / Alle
            $total_sub = count($children_map[$t['id']]);
            $done_sub = 0;
            foreach($children_map[$t['id']] as $sub) {
                if ($sub['progress_pct'] == 100) $done_sub++;
            }
            $t['progress_pct'] = round(($done_sub / $total_sub) * 100);
            $t['has_checklist'] = true;
            $t['checklist_done'] = $done_sub;
            $t['checklist_total'] = $total_sub;
        } else {
            $t['has_checklist'] = false;
            // Wenn keine Checkliste, aber Requirements verknüpft sind
            if ($t['is_auto_progress'] == 1 && !empty($t['linked_reqs'])) {
                $linked = json_decode($t['linked_reqs'], true) ?: [];
                if (count($linked) > 0) {
                    $approved = 0;
                    foreach($linked as $key) {
                        if (isset($req_map[trim($key)]) && $req_map[trim($key)] === 'Geprüft & Freigegeben') $approved++;
                    }
                    $t['progress_pct'] = round(($approved / count($linked)) * 100);
                } else {
                    $t['progress_pct'] = 0;
                }
            }
        }
    }

    echo json_encode(['success' => true, 'tasks' => $tasks, 'templates' => $templates]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}