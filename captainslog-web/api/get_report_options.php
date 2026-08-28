<?php
ini_set('display_errors', '0'); error_reporting(E_ALL); session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/report_common.php';
try {
    $uid = report_user();
    $projectId = trim((string)($_GET['project_id'] ?? ''));
    if ($projectId === '') throw new RuntimeException('Projekt-ID fehlt.');
    report_access($pdo, $projectId, $uid);

    $q = $pdo->prepare('SELECT id,wbs_code,title,category,progress_pct,start_date,end_date FROM project_tasks WHERE project_id=? AND parent_id IS NULL ORDER BY wbs_code,id');
    $q->execute([$projectId]);
    $tasks = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT id,issue_key,title,status,priority FROM issues WHERE project_id=? ORDER BY issue_key');
    $q->execute([$projectId]);
    $issues = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT * FROM project_report_settings WHERE project_id=?');
    $q->execute([$projectId]);
    $settings = $q->fetch(PDO::FETCH_ASSOC) ?: [];

    report_json(['success'=>true,'tasks'=>$tasks,'issues'=>$issues,'settings'=>$settings]);
} catch (Throwable $e) {
    report_json(['success'=>false,'error'=>$e->getMessage()],400);
}
