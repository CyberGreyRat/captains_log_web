<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $project_id = $_GET['project_id'] ?? '';
    if (!$project_id || !isset($_SESSION['user_id']))
        throw new Exception('Nicht autorisiert oder Projekt fehlt.');
    $access = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=?');
    $access->execute([$project_id, $_SESSION['user_id']]);
    if (!$access->fetchColumn())
        throw new Exception('Kein Projektzugriff.');
    $stmt = $pdo->prepare("SELECT i.*, au.username AS assignee_name, ru.username AS reporter_name,
        (SELECT COUNT(*) FROM issue_requirements ir WHERE ir.issue_id=i.id) AS requirement_count,
        (SELECT COUNT(*) FROM issue_tasks it WHERE it.issue_id=i.id) AS task_count,
        (SELECT COUNT(*) FROM issue_comments ic WHERE ic.issue_id=i.id) AS comment_count
        FROM issues i
        LEFT JOIN users au ON au.id=i.assignee_user_id
        LEFT JOIN users ru ON ru.id=i.reporter_user_id
        WHERE i.project_id=? ORDER BY FIELD(i.status,'open','in_progress','waiting_response','ready_for_test','approved','closed','rejected'), i.updated_at DESC");
    $stmt->execute([$project_id]);
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->prepare('SELECT u.id,u.username FROM users u JOIN project_members pm ON pm.user_id=u.id WHERE pm.project_id=? ORDER BY u.username');
    $users->execute([$project_id]);
    $reqs = $pdo->prepare('SELECT id,req_key,title,type FROM requirements WHERE project_id=? ORDER BY type,req_key');
    $reqs->execute([$project_id]);
    $tasks = $pdo->prepare('SELECT id,wbs_code,title FROM project_tasks WHERE project_id=? AND parent_id IS NULL ORDER BY wbs_code,id');
    $tasks->execute([$project_id]);
    echo json_encode(['success' => true, 'issues' => $issues, 'users' => $users->fetchAll(), 'requirements' => $reqs->fetchAll(), 'tasks' => $tasks->fetchAll()]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
