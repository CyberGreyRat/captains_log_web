<?php
// api/set_task.php
ini_set('display_errors', 0); 
error_reporting(E_ALL); 
session_start();
require '../config/db.php'; 
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['project_id']) || empty($data['title'])) {
        throw new Exception("Pflichtfelder fehlen.");
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $parent_id = !empty($data['parent_id']) ? $data['parent_id'] : null; // Die Obergruppe ist zurück!
    $category = $data['category'] ?? 'Allgemein';
    $title = $data['title'];
    $wbs_code = $data['wbs_code'] ?? '';
    $assignee = $data['assignee'] ?? '';
    $effort_mt = $data['effort_mt'] !== '' ? (float)$data['effort_mt'] : 0; // Wir behandeln das jetzt als Stunden
    $performance_pct = $data['performance_pct'] !== '' ? (int)$data['performance_pct'] : 100;
    $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
    $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
    $is_auto_progress = !empty($data['is_auto_progress']) ? 1 : 0;
    $progress_pct = $data['progress_pct'] !== '' ? (int)$data['progress_pct'] : 0;

    $raw_reqs = explode(',', $data['linked_reqs'] ?? '');
    $linked_reqs_arr = [];
    foreach($raw_reqs as $r) {
        $r = trim($r);
        if(!empty($r)) $linked_reqs_arr[] = $r;
    }
    $linked_reqs = json_encode($linked_reqs_arr);

    // --- MAGIC COMMAND PARSER FÜR KOMMENTARE ---
    $raw_description = $data['description'] ?? '';
    $lines = explode("\n", $raw_description);
    $clean_description = [];
    $magic_subtasks = [];

    foreach($lines as $line) {
        // Sucht nach Zeilen, die mit "-- " beginnen
        if (preg_match('/^\s*--\s*(.+)$/', $line, $matches)) {
            $magic_subtasks[] = trim($matches[1]);
        } else {
            $clean_description[] = $line;
        }
    }
    $description = trim(implode("\n", $clean_description)); // Bereinigter Kommentar

    // 1. Hauptaufgabe speichern
    if ($id) {
        $stmt = $pdo->prepare("UPDATE project_tasks SET parent_id=?, category=?, wbs_code=?, title=?, description=?, assignee=?, effort_mt=?, performance_pct=?, start_date=?, end_date=?, progress_pct=?, is_auto_progress=?, linked_reqs=? WHERE id=? AND project_id=?");
        $stmt->execute([$parent_id, $category, $wbs_code, $title, $description, $assignee, $effort_mt, $performance_pct, $start_date, $end_date, $progress_pct, $is_auto_progress, $linked_reqs, $id, $project_id]);
        $main_task_id = $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO project_tasks (project_id, parent_id, category, wbs_code, title, description, assignee, effort_mt, performance_pct, start_date, end_date, progress_pct, is_auto_progress, linked_reqs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $parent_id, $category, $wbs_code, $title, $description, $assignee, $effort_mt, $performance_pct, $start_date, $end_date, $progress_pct, $is_auto_progress, $linked_reqs]);
        $main_task_id = $pdo->lastInsertId();
    }

    // 2. MAGIC SUBTASKS ANLEGEN
    // Wenn "-- " gefunden wurde, legen wir diese direkt als Kinder der Hauptaufgabe an!
    if (!empty($magic_subtasks)) {
        $stmtSub = $pdo->prepare("INSERT INTO project_tasks (project_id, parent_id, category, title, is_auto_progress) VALUES (?, ?, ?, ?, ?)");
        foreach($magic_subtasks as $sub_title) {
            $stmtSub->execute([$project_id, $main_task_id, $category, $sub_title, 1]);
        }
    }

    // 3. ANWENDUNG LERNT DAZU
    $stmtCheck = $pdo->prepare("SELECT id FROM task_templates WHERE LOWER(title) = LOWER(?)");
    $stmtCheck->execute([$title]);
    if (!$stmtCheck->fetch()) {
        $stmtLearn = $pdo->prepare("INSERT INTO task_templates (category, title, default_effort) VALUES (?, ?, ?)");
        $stmtLearn->execute([$category, $title, $effort_mt]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}