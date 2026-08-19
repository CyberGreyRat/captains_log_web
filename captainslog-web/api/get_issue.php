<?php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start(); require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
try {
 $id=(int)($_GET['id']??0); if(!$id||!isset($_SESSION['user_id'])) throw new Exception('ID oder Sitzung fehlt.');
 $s=$pdo->prepare('SELECT i.*,au.username assignee_name FROM issues i LEFT JOIN users au ON au.id=i.assignee_user_id JOIN project_members pm ON pm.project_id=i.project_id AND pm.user_id=? WHERE i.id=?');
 $s->execute([$_SESSION['user_id'],$id]); $issue=$s->fetch(); if(!$issue) throw new Exception('Issue nicht gefunden.');
 $r=$pdo->prepare('SELECT ir.requirement_id,ir.relation_type,r.req_key,r.title,r.type FROM issue_requirements ir JOIN requirements r ON r.id=ir.requirement_id WHERE ir.issue_id=?'); $r->execute([$id]);
 $t=$pdo->prepare('SELECT it.task_id,it.relation_type,t.wbs_code,t.title FROM issue_tasks it JOIN project_tasks t ON t.id=it.task_id WHERE it.issue_id=?'); $t->execute([$id]);
 $c=$pdo->prepare('SELECT ic.*,u.username FROM issue_comments ic LEFT JOIN users u ON u.id=ic.created_by WHERE ic.issue_id=? ORDER BY ic.created_at DESC'); $c->execute([$id]);
 echo json_encode(['success'=>true,'issue'=>$issue,'requirements'=>$r->fetchAll(),'tasks'=>$t->fetchAll(),'comments'=>$c->fetchAll()]);
} catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
