<?php
declare(strict_types=1); session_start(); require_once __DIR__.'/../config/db.php'; header('Content-Type: application/json; charset=utf-8');
function reply(array $d,int $s=200):never{http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
try{
 if(empty($_SESSION['user_id'])) reply(['success'=>false,'error'=>'Nicht angemeldet.'],401);
 $project=trim((string)($_GET['project_id']??'')); if($project==='') throw new RuntimeException('Projekt-ID fehlt.');
 $q=$pdo->prepare("SELECT id,req_key,title,description,review_status FROM requirements WHERE project_id=? AND type='TC' ORDER BY serial_number,id");$q->execute([$project]);$tcs=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare("SELECT id,req_key,type,title,acceptance_criteria,review_status FROM requirements WHERE project_id=? AND type IN ('USR','SYS','SEC','SRS','HRS','SWC') AND TRIM(COALESCE(acceptance_criteria,''))<>'' ORDER BY serial_number,id");$q->execute([$project]);$reqs=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$cs=[];foreach(preg_split('/\R/u',(string)$r['acceptance_criteria'])?:[] as $i=>$c){$c=trim((string)preg_replace('/^-\s*/u','',$c));if($c!=='')$cs[]=['index'=>(int)$i,'text'=>$c];}if($cs){unset($r['acceptance_criteria']);$r['id']=(int)$r['id'];$r['criteria']=$cs;$reqs[]=$r;}}
 $q=$pdo->prepare('SELECT l.id,l.test_case_id,l.requirement_id,l.criterion_index,l.verification_status FROM criterion_test_links l JOIN requirements tc ON tc.id=l.test_case_id JOIN requirements r ON r.id=l.requirement_id WHERE tc.project_id=? AND r.project_id=?');$q->execute([$project,$project]);$links=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $l)$links[]=['id'=>(int)$l['id'],'test_case_id'=>(int)$l['test_case_id'],'requirement_id'=>(int)$l['requirement_id'],'criterion_index'=>(int)$l['criterion_index'],'verification_status'=>$l['verification_status']?:'open'];
 reply(['success'=>true,'test_cases'=>$tcs,'requirements'=>$reqs,'links'=>$links]);
}catch(Throwable $e){reply(['success'=>false,'error'=>$e->getMessage()],400);}
