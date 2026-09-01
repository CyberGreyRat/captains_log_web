<?php
declare(strict_types=1);
ini_set('display_errors','0'); error_reporting(E_ALL); session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
function out(array $d,int $s=200): never { http_response_code($s); echo json_encode($d,JSON_UNESCAPED_UNICODE); exit; }
try {
  $uid=(int)($_SESSION['user_id']??0); $uname=trim((string)($_SESSION['username']??''));
  if($uid<=0) out(['success'=>false,'error'=>'Nicht angemeldet'],401);
  $reqId=(int)($_POST['req_id']??0); $idx=(int)($_POST['criterion_idx']??-1);
  if($reqId<=0||$idx<0) throw new RuntimeException('Anforderung oder Kriterium fehlt.');
  $q=$pdo->prepare('SELECT id,project_id,req_key,acceptance_criteria,attributes,review_status FROM requirements WHERE id=?');
  $q->execute([$reqId]); $req=$q->fetch(PDO::FETCH_ASSOC); if(!$req) throw new RuntimeException('Anforderung nicht gefunden.');
  $lines=preg_split('/\R/u',(string)$req['acceptance_criteria'])?:[];
  $criterion=trim((string)preg_replace('/^-\s*/u','',$lines[$idx]??''));
  if($criterion==='') throw new RuntimeException('Akzeptanzkriterium nicht gefunden.');
  $testCaseId=(int)($_POST['test_case_id']??0); if($testCaseId<=0) throw new RuntimeException('Bitte einen Testfall auswählen.');
  $tc=$pdo->prepare("SELECT id FROM requirements WHERE id=? AND project_id=? AND type='TC'");$tc->execute([$testCaseId,$req['project_id']]);if(!$tc->fetchColumn()) throw new RuntimeException('Testfall ist ungültig.');
  $result=(string)($_POST['result']??'');
  if(!in_array($result,['passed','failed','blocked'],true)) throw new RuntimeException('Ungültiges Testergebnis.');
  $required=['title','test_description','expected_result','actual_result','software_version','hardware_revision','executed_at'];
  foreach($required as $f) if(trim((string)($_POST[$f]??''))==='') throw new RuntimeException("Pflichtfeld fehlt: $f");
  $runKey=trim((string)($_POST['run_key']??''));
  if($runKey==='') $runKey='RUN-'.date('Ymd-His').'-'.str_pad((string)$reqId,4,'0',STR_PAD_LEFT).'-'.$idx;
  $pdo->beginTransaction();
  $ins=$pdo->prepare('INSERT INTO test_runs(project_id,test_case_id,requirement_id,criterion_index,run_key,title,test_description,expected_result,actual_result,result,software_version,hardware_revision,test_setup,limitation_text,executed_by,executed_by_name,executed_at,reviewed_by,reviewed_by_name,reviewed_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $ins->execute([$req['project_id'],$testCaseId,$reqId,$idx,$runKey,trim($_POST['title']),trim($_POST['test_description']),trim($_POST['expected_result']),trim($_POST['actual_result']),$result,trim($_POST['software_version']),trim($_POST['hardware_revision']),trim((string)($_POST['test_setup']??''))?:null,trim((string)($_POST['limitation_text']??''))?:null,$uid,$uname,$_POST['executed_at'],$result==='passed'?$uid:null,$result==='passed'?$uname:null,$result==='passed'?date('Y-m-d H:i:s'):null]);
  $runId=(int)$pdo->lastInsertId();
  $link=$pdo->prepare('INSERT IGNORE INTO criterion_test_links(requirement_id,criterion_index,test_case_id) VALUES(?,?,?)');$link->execute([$reqId,$idx,$testCaseId]);
  $base=__DIR__.'/../storage/test-runs/'.preg_replace('/[^A-Za-z0-9_-]/','_',(string)$req['project_id']).'/'.$runId;
  if(!is_dir($base)&&!mkdir($base,0770,true)&&!is_dir($base)) throw new RuntimeException('Ablage konnte nicht erstellt werden.');
  $allowed=['text/plain','text/csv','application/pdf','image/png','image/jpeg','image/webp','application/json'];
  foreach($_FILES['evidence']['name']??[] as $i=>$original){
    if(($_FILES['evidence']['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
    if($_FILES['evidence']['error'][$i]!==UPLOAD_ERR_OK) throw new RuntimeException('Datei-Upload fehlgeschlagen.');
    $tmp=$_FILES['evidence']['tmp_name'][$i]; $size=(int)$_FILES['evidence']['size'][$i];
    if($size>15*1024*1024) throw new RuntimeException('Ein Nachweis ist größer als 15 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'application/octet-stream';
    if(!in_array($mime,$allowed,true)) throw new RuntimeException('Nicht erlaubter Dateityp: '.$mime);
    $safe=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)$original));
    $stored=bin2hex(random_bytes(8)).'_'.$safe; $dest=$base.'/'.$stored;
    if(!move_uploaded_file($tmp,$dest)) throw new RuntimeException('Nachweis konnte nicht gespeichert werden.');
    $rel='storage/test-runs/'.preg_replace('/[^A-Za-z0-9_-]/','_',(string)$req['project_id']).'/'.$runId.'/'.$stored;
    $f=$pdo->prepare('INSERT INTO test_run_files(test_run_id,original_name,stored_name,relative_path,mime_type,file_size,sha256) VALUES(?,?,?,?,?,?,?)');
    $f->execute([$runId,$original,$stored,$rel,$mime,$size,hash_file('sha256',$dest)]);
  }
  $attrs=json_decode((string)($req['attributes']??'{}'),true)?:[]; $attrs['criteria_states']=$attrs['criteria_states']??[];
  $attrs['criteria_states'][(string)$idx]=['checked'=>$result==='passed','status'=>$result,'by'=>$uname,'date'=>date('d.m.Y H:i'),'note'=>$runKey.': '.trim($_POST['actual_result']),'test_run_id'=>$runId,'test_run_key'=>$runKey];
  $valid=0;$checked=0; foreach($lines as $i=>$l){if(trim((string)preg_replace('/^-\s*/u','',$l))!==''){$valid++;if(!empty($attrs['criteria_states'][(string)$i]['checked']))$checked++;}}
  $status=($valid>0&&$valid===$checked)?'Geprüft & Freigegeben':$req['review_status'];
  $u=$pdo->prepare('UPDATE requirements SET attributes=?,review_status=? WHERE id=?');
  $u->execute([json_encode($attrs,JSON_UNESCAPED_UNICODE),$status,$reqId]);
  $pdo->commit(); out(['success'=>true,'run_id'=>$runId,'run_key'=>$runKey,'criterion_verified'=>$result==='passed']);
} catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();out(['success'=>false,'error'=>$e->getMessage()],400);}
