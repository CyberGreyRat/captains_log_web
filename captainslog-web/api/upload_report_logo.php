<?php
ini_set('display_errors', '0'); error_reporting(E_ALL); session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/report_common.php';
try {
    $uid = report_user();
    $projectId = trim((string)($_POST['project_id'] ?? ''));
    report_access($pdo, $projectId, $uid);
    if (empty($_FILES['logo']['tmp_name'])) throw new RuntimeException('Logo fehlt.');
    $file = $_FILES['logo'];
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) throw new RuntimeException('Logo darf maximal 2 MB groß sein.');
    $info = getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_PNG=>'png',IMAGETYPE_JPEG=>'jpg',IMAGETYPE_WEBP=>'webp'];
    if (!$info || !isset($allowed[$info[2]])) throw new RuntimeException('Erlaubt sind PNG, JPG und WEBP.');
    $dir = __DIR__ . '/../storage/report_assets';
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) throw new RuntimeException('Logo-Ordner konnte nicht erstellt werden.');
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $projectId);
    $relative = 'storage/report_assets/' . $safe . '_logo.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $relative)) throw new RuntimeException('Logo konnte nicht gespeichert werden.');
    $q = $pdo->prepare('INSERT INTO project_report_settings(project_id,logo_path,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE logo_path=VALUES(logo_path),updated_by=VALUES(updated_by)');
    $q->execute([$projectId,$relative,$uid]);
    report_json(['success'=>true,'logo_path'=>$relative]);
} catch (Throwable $e) {
    report_json(['success'=>false,'error'=>$e->getMessage()],400);
}
