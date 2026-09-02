<?php
declare(strict_types=1); session_start(); require_once __DIR__.'/../config/db.php'; header('Content-Type: application/json; charset=utf-8');
function reply(array $d,int $s=200):never{http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
try{
 if(empty($_SESSION['user_id']))reply(['success'=>false,'error'=>'Nicht angemeldet.'],401);
 $d=json_decode(file_get_contents('php://input'),true);if(!is_array($d))throw new RuntimeException('Ungültige Daten.');
 $project=trim((string)($d['project_id']??''));$tc=(int)($d['test_case_id']??0);$criteria=is_array($d['criteria']??null)?$d['criteria']:[];
 $q=$pdo->prepare("SELECT id FROM requirements WHERE id=? AND project_id=? AND type='TC'");$q->execute([$tc,$project]);if(!$q->fetchColumn())throw new RuntimeException('Test Case ungültig.');
 $pdo->beginTransaction();$pdo->prepare('DELETE FROM criterion_test_links WHERE test_case_id=?')->execute([$tc]);
 $check=$pdo->prepare("SELECT acceptance_criteria FROM requirements WHERE id=? AND project_id=? AND type IN ('USR','SYS','SEC','SRS','HRS','SWC')");$ins=$pdo->prepare("INSERT INTO criterion_test_links(requirement_id,criterion_index,test_case_id,verification_status) VALUES(?,?,?,'open')");$seen=[];
 foreach($criteria as $c){$rid=(int)($c['requirement_id']??0);$idx=(int)($c['criterion_index']??-1);$key="$rid:$idx";if($rid<=0||$idx<0||isset($seen[$key]))continue;$check->execute([$rid,$project]);$text=$check->fetchColumn();if($text===false)throw new RuntimeException('Anforderung ungültig.');$lines=preg_split('/\R/u',(string)$text)?:[];if(trim((string)($lines[$idx]??''))==='')throw new RuntimeException('Kriterium nicht mehr vorhanden.');$ins->execute([$rid,$idx,$tc]);$seen[$key]=1;}
 $pdo->commit();reply(['success'=>true,'saved'=>count($seen)]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();reply(['success'=>false,'error'=>$e->getMessage()],400);}
