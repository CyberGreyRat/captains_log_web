<?php
declare(strict_types=1); session_start(); require '../config/db.php'; header('Content-Type: application/json; charset=utf-8');
$project=trim((string)($_GET['project_id']??'')); if($project===''){http_response_code(400);echo json_encode(['success'=>false,'error'=>'Projekt-ID fehlt']);exit;}
$q=$pdo->prepare("SELECT id,req_key,title,description,acceptance_criteria,review_status FROM requirements WHERE project_id=? AND type='TC' ORDER BY display_number,id");$q->execute([$project]);
echo json_encode(['success'=>true,'test_cases'=>$q->fetchAll(PDO::FETCH_ASSOC)],JSON_UNESCAPED_UNICODE);
