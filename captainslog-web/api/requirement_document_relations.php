<?php
require_once __DIR__.'/requirement_document_common.php';$user=rdUser();
try{
 if($_SERVER['REQUEST_METHOD']==='GET'){
  $id=trim((string)($_GET['document_id']??''));$doc=rdDocument($pdo,$id);
  $q=$pdo->prepare("SELECT l.id,l.source_document_id,l.target_document_id,l.link_type,l.description,
   CASE WHEN l.source_document_id=? THEN 'child' ELSE 'parent' END relation_role,
   other.id related_id,other.requirement_key,other.requirement_type,other.title,other.status
   FROM requirement_document_links l
   JOIN requirement_documents other ON other.id=CASE WHEN l.source_document_id=? THEN l.target_document_id ELSE l.source_document_id END
   WHERE l.link_type='parent_of' AND (l.source_document_id=? OR l.target_document_id=?)
   ORDER BY relation_role,other.requirement_key");
  $q->execute([$id,$id,$id,$id]);$parents=[];$children=[];
  foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){if($r['relation_role']==='parent')$parents[]=$r;else$children[]=$r;}
  rdResponse(['success'=>true,'parents'=>$parents,'children'=>$children]);
 }
 if($_SERVER['REQUEST_METHOD']==='POST'){
  $d=rdBody();$document=trim((string)($d['document_id']??''));$related=trim((string)($d['related_document_id']??''));$role=trim((string)($d['role']??''));
  if(!in_array($role,['parent','child'],true))rdResponse(['success'=>false,'error'=>'Rolle muss parent oder child sein.'],422);
  $a=rdDocument($pdo,$document);$b=rdDocument($pdo,$related);if($a['project_id']!==$b['project_id'])rdResponse(['success'=>false,'error'=>'Anforderungen müssen im selben Projekt liegen.'],422);
  $parent=$role==='parent'?$related:$document;$child=$role==='parent'?$document:$related;
  if(rdWouldCycle($pdo,$parent,$child))rdResponse(['success'=>false,'error'=>'Die Verknüpfung würde einen Zyklus erzeugen.'],409);
  $q=$pdo->prepare("INSERT INTO requirement_document_links(id,project_id,source_document_id,target_document_id,link_type,description,metadata,created_by) VALUES(?,?,?,?, 'parent_of',?,?,?)");
  $q->execute([rdUuid(),$a['project_id'],$parent,$child,trim((string)($d['description']??'')),json_encode($d['metadata']??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$user]);
  rdResponse(['success'=>true],201);
 }
 if($_SERVER['REQUEST_METHOD']==='DELETE'){$d=rdBody();$q=$pdo->prepare("DELETE FROM requirement_document_links WHERE id=? AND link_type='parent_of'");$q->execute([trim((string)($d['id']??''))]);rdResponse(['success'=>true]);}
 rdResponse(['success'=>false,'error'=>'Methode nicht erlaubt.'],405);
}catch(PDOException $e){$status=$e->getCode()==='23000'?409:500;rdResponse(['success'=>false,'error'=>$status===409?'Diese Verknüpfung existiert bereits.':$e->getMessage()],$status);}catch(Throwable $e){rdResponse(['success'=>false,'error'=>$e->getMessage()],500);}
