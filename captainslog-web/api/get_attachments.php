<?php
require_once __DIR__.'/attachment_common.php';
try {
 $uid=att_user(); $project=att_clean($_GET['project_id']??'',50); if($project==='') throw new Exception('Projekt-ID fehlt.'); att_access($pdo,$project,$uid);
 $type=att_clean($_GET['entity_type']??'',30); $entity=(int)($_GET['entity_id']??0);
 $sql='SELECT a.*,u.username FROM project_attachments a LEFT JOIN users u ON u.id=a.created_by WHERE a.project_id=?'; $params=[$project];
 if($type!==''&&$entity>0){$sql.=' AND EXISTS(SELECT 1 FROM attachment_links l WHERE l.attachment_id=a.id AND l.entity_type=? AND l.entity_id=?)';$params[]=$type;$params[]=$entity;}
 $sql.=' ORDER BY a.updated_at DESC,a.id DESC'; $q=$pdo->prepare($sql);$q->execute($params);$items=array_map('att_public',$q->fetchAll(PDO::FETCH_ASSOC));
 if($items){$ids=array_column($items,'id');$marks=implode(',',array_fill(0,count($ids),'?'));$l=$pdo->prepare("SELECT * FROM attachment_links WHERE attachment_id IN ($marks) ORDER BY entity_type,entity_key");$l->execute($ids);$map=[];foreach($l->fetchAll(PDO::FETCH_ASSOC) as $row)$map[(int)$row['attachment_id']][]=$row;foreach($items as &$item)$item['links']=$map[$item['id']]??[];unset($item);}
 att_json(['success'=>true,'attachments'=>$items]);
} catch(Throwable $e){att_json(['success'=>false,'error'=>$e->getMessage()],400);}
