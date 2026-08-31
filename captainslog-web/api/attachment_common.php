<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
function att_json(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function att_user(): int {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) att_json(['success'=>false,'error'=>'Nicht angemeldet.'], 401);
    return $id;
}
function att_access(PDO $pdo, string $projectId, int $userId): void {
    if (($_SESSION['role'] ?? '') === 'admin') return;
    $q=$pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');
    $q->execute([$projectId,$userId]);
    if (!$q->fetchColumn()) att_json(['success'=>false,'error'=>'Kein Projektzugriff.'],403);
}
function att_clean(mixed $value, int $max=255): string { return mb_substr(trim((string)$value),0,$max); }
function att_dir(string $projectId): string {
    $safe=preg_replace('/[^A-Za-z0-9_-]/','_',$projectId);
    $dir=__DIR__.'/../storage/attachments/'.$safe;
    if(!is_dir($dir) && !mkdir($dir,0770,true) && !is_dir($dir)) throw new RuntimeException('Anhangsordner konnte nicht erstellt werden.');
    return $dir;
}
function att_public(array $row): array {
    $row['id']=(int)$row['id']; $row['file_size']=$row['file_size']!==null?(int)$row['file_size']:null;
    $row['is_image']=str_starts_with((string)($row['mime_type']??''),'image/');
    $row['download_url']=$row['storage_type']==='upload'?'../api/download_attachment.php?id='.$row['id']:null;
    return $row;
}
