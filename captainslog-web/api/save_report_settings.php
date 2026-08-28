<?php
ini_set('display_errors', '0'); error_reporting(E_ALL); session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/report_common.php';
try {
    $uid = report_user();
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $projectId = trim((string)($data['project_id'] ?? ''));
    report_access($pdo, $projectId, $uid);
    $color = preg_match('/^#[0-9a-f]{6}$/i', (string)($data['accent_color'] ?? '')) ? $data['accent_color'] : '#1f4e79';
    $q = $pdo->prepare('INSERT INTO project_report_settings(project_id,header_text,footer_text,accent_color,company_name,classification,updated_by) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE header_text=VALUES(header_text),footer_text=VALUES(footer_text),accent_color=VALUES(accent_color),company_name=VALUES(company_name),classification=VALUES(classification),updated_by=VALUES(updated_by)');
    $q->execute([$projectId,mb_substr(trim((string)($data['header_text']??'')),0,500),mb_substr(trim((string)($data['footer_text']??'')),0,500),$color,mb_substr(trim((string)($data['company_name']??'')),0,255),mb_substr(trim((string)($data['classification']??'')),0,100),$uid]);
    report_json(['success'=>true]);
} catch (Throwable $e) {
    report_json(['success'=>false,'error'=>$e->getMessage()],400);
}
