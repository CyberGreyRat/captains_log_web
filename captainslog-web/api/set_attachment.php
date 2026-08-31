<?php
require_once __DIR__.'/attachment_common.php';
try {
 $uid=att_user();$project=att_clean($_POST['project_id']??'',50);att_access($pdo,$project,$uid);
 $title=att_clean($_POST['title']??'',255);$storage=att_clean($_POST['storage_type']??'',20);if($project===''||$title===''||!in_array($storage,['upload','link'],true))throw new Exception('Pflichtfelder fehlen.');
 $allowedTypes=['requirement','use_case','task','issue','milestone','asset','risk','user_story'];$type=att_clean($_POST['entity_type']??'',30);$entity=(int)($_POST['entity_id']??0);
 $original=$stored=$relative=$mime=$sha=null;$size=null;
 if($storage==='upload'){
  if(empty($_FILES['file']['tmp_name']))throw new Exception('Datei fehlt.');$f=$_FILES['file'];if(($f['size']??0)>25*1024*1024)throw new Exception('Maximal 25 MB je Datei.');
  $original=basename((string)$f['name']);$ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));$denied=['php','phtml','phar','cgi','pl','exe','com','bat','cmd','js','html','htm','svg'];if(in_array($ext,$denied,true))throw new Exception('Dieser Dateityp ist nicht erlaubt.');
  $stored=bin2hex(random_bytes(18)).($ext!==''?'.'.$ext:'');$target=att_dir($project).'/'.$stored;if(!move_uploaded_file($f['tmp_name'],$target))throw new Exception('Datei konnte nicht gespeichert werden.');
  $relative='storage/attachments/'.preg_replace('/[^A-Za-z0-9_-]/','_',$project).'/'.$stored;$mime=(new finfo(FILEINFO_MIME_TYPE))->file($target)?:'application/octet-stream';$size=filesize($target);$sha=hash_file('sha256',$target);
 } else {$relative=att_clean($_POST['relative_path']??'',700);if($relative==='')throw new Exception('Projektpfad fehlt.');}
 $pdo->beginTransaction();$n=$pdo->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(attachment_key,"-",-1) AS UNSIGNED)),0)+1 FROM project_attachments WHERE project_id=? FOR UPDATE');$n->execute([$project]);$key='ATT-'.str_pad((string)$n->fetchColumn(),3,'0',STR_PAD_LEFT);
 $q=$pdo->prepare('INSERT INTO project_attachments(project_id,attachment_key,title,category,description,storage_type,original_filename,stored_filename,relative_path,mime_type,file_size,sha256,version_label,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
 $q->execute([$project,$key,$title,att_clean($_POST['category']??'Sonstiges',80),trim((string)($_POST['description']??'')),$storage,$original,$stored,$relative,$mime,$size,$sha,att_clean($_POST['version_label']??'',80)?:null,att_clean($_POST['status']??'working',20),$uid]);$id=(int)$pdo->lastInsertId();
 if(in_array($type,$allowedTypes,true)&&$entity>0){$l=$pdo->prepare('INSERT INTO attachment_links(attachment_id,entity_type,entity_id,entity_key,entity_title) VALUES(?,?,?,?,?)');$l->execute([$id,$type,$entity,att_clean($_POST['entity_key']??'',100),att_clean($_POST['entity_title']??'',255)]);}
 $pdo->commit();att_json(['success'=>true,'id'=>$id,'attachment_key'=>$key]);
} catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();att_json(['success'=>false,'error'=>$e->getMessage()],400);}
