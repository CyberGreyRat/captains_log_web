<?php
require_once __DIR__.'/requirement_document_common.php'; $userId=reqAuth();
try {
 if($_SERVER['REQUEST_METHOD']==='GET'){
  $projectId=trim((string)($_GET['project_id']??'')); if($projectId==='') reqRespond(['success'=>false,'error'=>'Projekt-ID fehlt.'],422);
  $s=$pdo->prepare('SELECT id,template_key,name,requirement_type,description,blocks,default_metadata,is_system FROM requirement_document_templates WHERE is_active=1 AND (project_id IS NULL OR project_id=?) ORDER BY is_system DESC,name');
  $s->execute([$projectId]); $rows=$s->fetchAll(PDO::FETCH_ASSOC);
  foreach($rows as &$r){$r['blocks']=json_decode($r['blocks'],true)?:[];$r['default_metadata']=json_decode($r['default_metadata']??'{}',true)?:[];}
  reqRespond(['success'=>true,'templates'=>$rows]);
 }
 if($_SERVER['REQUEST_METHOD']!=='POST') reqRespond(['success'=>false,'error'=>'Methode nicht erlaubt.'],405);
 $d=reqInput(); $projectId=trim((string)($d['project_id']??'')); reqProject($pdo,$projectId);
 $name=trim((string)($d['name']??'')); $type=reqCleanType((string)($d['requirement_type']??'DOC'));
 $blocks=$d['blocks']??[]; if($name===''||!is_array($blocks)) reqRespond(['success'=>false,'error'=>'Name und Blöcke sind erforderlich.'],422);
 $id=reqUuid(); $key='CUSTOM_'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/','_',$name),0,35)).'_'.substr(str_replace('-','',$id),0,6);
 $s=$pdo->prepare('INSERT INTO requirement_document_templates(id,project_id,template_key,name,requirement_type,description,blocks,default_metadata,is_system,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,0,?,?)');
 $s->execute([$id,$projectId,$key,$name,$type,trim((string)($d['description']??'')),json_encode($blocks,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($d['default_metadata']??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$userId,$userId]);
 reqRespond(['success'=>true,'id'=>$id,'template_key'=>$key],201);
} catch(Throwable $e){reqRespond(['success'=>false,'error'=>$e->getMessage()],500);}
