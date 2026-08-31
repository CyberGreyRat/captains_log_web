<?php
declare(strict_types=1);
ini_set('display_errors','0');error_reporting(E_ALL);session_start();
require_once __DIR__.'/../config/db.php';header('Content-Type: application/json; charset=utf-8');
try{
 $userId=(int)($_SESSION['user_id']??0);$projectId=trim((string)($_GET['project_id']??''));$requirementId=(int)($_GET['requirement_id']??0);
 if($userId<=0||$projectId===''||$requirementId<=0)throw new RuntimeException('Ungültige Anfrage.');
 $access=$pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');$access->execute([$projectId,$userId]);if(($_SESSION['role']??'')!=='admin'&&!$access->fetchColumn())throw new RuntimeException('Kein Projektzugriff.');
 $q=$pdo->prepare("SELECT r.id,r.req_key,r.title,r.attributes,l.link_group FROM risk_requirement_links l JOIN requirements r ON r.id=l.risk_id WHERE l.requirement_id=? AND r.project_id=? AND r.type='RISK' AND r.review_status<>'Archiviert' ORDER BY r.serial_number,r.id");$q->execute([$requirementId,$projectId]);$items=$q->fetchAll(PDO::FETCH_ASSOC);
 foreach($items as &$item){$attrs=json_decode($item['attributes']??'{}',true)?:[];$item['workflow_status']=$attrs['workflow_status']??'open';$item['risk_score']=(int)($attrs['risk_score']??((int)($attrs['w']??1)*(int)($attrs['e']??1)));unset($item['attributes']);}unset($item);
 echo json_encode(['success'=>true,'risks'=>$items],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
