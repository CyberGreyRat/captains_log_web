<?php
ini_set('display_errors',0);session_start();require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';header('Content-Type: application/json; charset=utf-8');
try{
    set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));
require_edit_permission();$d=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($d['id']??0);$project=$d['project_id']??'';
if(!$id||!$project)throw new Exception('ID fehlt.');$q=$pdo->prepare('DELETE i FROM issues i JOIN project_members pm ON pm.project_id=i.project_id AND pm.user_id=? WHERE i.id=? AND i.project_id=?');$q->execute([$_SESSION['user_id'],$id,$project]);echo json_encode(['success'=>true]);}
catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
