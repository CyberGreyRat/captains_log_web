<?php
ini_set('display_errors',0);session_start();require '../config/db.php';header('Content-Type: application/json; charset=utf-8');
try{require_edit_permission();$d=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($d['issue_id']??0);$text=trim($d['comment_text']??'');if(!$id||!$text)throw new Exception('Kommentar fehlt.');
$q=$pdo->prepare('INSERT INTO issue_comments(issue_id,comment_type,comment_text,created_by) SELECT i.id,?,?,? FROM issues i JOIN project_members pm ON pm.project_id=i.project_id AND pm.user_id=? WHERE i.id=?');$q->execute([$d['comment_type']??'internal',$text,$_SESSION['user_id'],$_SESSION['user_id'],$id]);echo json_encode(['success'=>true]);}
catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
