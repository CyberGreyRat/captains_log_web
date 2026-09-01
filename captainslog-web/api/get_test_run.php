<?php
declare(strict_types=1); session_start(); require '../config/db.php'; header('Content-Type: application/json; charset=utf-8');
$id=(int)($_GET['id']??0); if($id<=0){http_response_code(400);echo json_encode(['success'=>false]);exit;}
$q=$pdo->prepare('SELECT * FROM test_runs WHERE id=?');$q->execute([$id]);$run=$q->fetch(PDO::FETCH_ASSOC);
if(!$run){http_response_code(404);echo json_encode(['success'=>false]);exit;}
$f=$pdo->prepare('SELECT id,original_name,mime_type,file_size,sha256 FROM test_run_files WHERE test_run_id=? ORDER BY id');$f->execute([$id]);
echo json_encode(['success'=>true,'run'=>$run,'files'=>$f->fetchAll(PDO::FETCH_ASSOC)],JSON_UNESCAPED_UNICODE);
