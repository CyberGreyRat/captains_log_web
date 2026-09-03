<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
function jout(array $data, int $status=200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function jbody(): array { $d=json_decode(file_get_contents('php://input'),true); if(!is_array($d)) jout(['success'=>false,'error'=>'Ungültiges JSON.'],400); return $d; }
function currentUser(): array { $id=(int)($_SESSION['user_id']??0); if($id<=0) jout(['success'=>false,'error'=>'Nicht angemeldet.'],401); return [$id,trim((string)($_SESSION['username']??''))]; }
function criterionLines(string $text): array { $out=[]; foreach(preg_split('/\R/u',$text)?:[] as $i=>$line){$line=trim((string)preg_replace('/^-\s*/u','',$line));if($line!=='')$out[(int)$i]=$line;} return $out; }
function criterionExists(PDO $pdo,string $project,int $req,int $idx): bool { $q=$pdo->prepare('SELECT acceptance_criteria FROM requirements WHERE id=? AND project_id=?');$q->execute([$req,$project]);$text=$q->fetchColumn();return $text!==false && array_key_exists($idx,criterionLines((string)$text)); }
function stateRank(string $s): int { return ['uncovered'=>0,'open'=>1,'partial'=>2,'validated'=>3,'blocked'=>4,'failed'=>5][$s]??0; }
function directCriterionState(PDO $pdo,string $project,int $req,int $idx): array {
  $q=$pdo->prepare("SELECT i.result,i.test_run_id,r.confirmed_at FROM validation_test_run_items i JOIN validation_test_runs r ON r.id=i.test_run_id WHERE r.project_id=? AND r.status IN ('passed','failed','blocked') AND i.requirement_id=? AND i.criterion_index=? ORDER BY r.confirmed_at DESC,r.id DESC");
  $q->execute([$project,$req,$idx]); $rows=$q->fetchAll(PDO::FETCH_ASSOC); if(!$rows)return ['status'=>'uncovered','run_id'=>null,'date'=>null];
  foreach($rows as $r) if($r['result']==='failed')return ['status'=>'failed','run_id'=>(int)$r['test_run_id'],'date'=>$r['confirmed_at']];
  foreach($rows as $r) if($r['result']==='blocked')return ['status'=>'blocked','run_id'=>(int)$r['test_run_id'],'date'=>$r['confirmed_at']];
  foreach($rows as $r) if($r['result']==='passed')return ['status'=>'validated','run_id'=>(int)$r['test_run_id'],'date'=>$r['confirmed_at']];
  return ['status'=>'open','run_id'=>null,'date'=>null];
}
function calculateCriterionState(PDO $pdo,string $project,int $req,int $idx,array &$memo,array &$stack): array {
  $key="$req:$idx"; if(isset($memo[$key]))return $memo[$key]; if(isset($stack[$key]))return ['status'=>'open','run_id'=>null,'date'=>null]; $stack[$key]=1;
  $direct=directCriterionState($pdo,$project,$req,$idx);
  $q=$pdo->prepare('SELECT child_requirement_id,child_criterion_index,aggregation_rule,is_required FROM validation_criterion_links WHERE project_id=? AND parent_requirement_id=? AND parent_criterion_index=?');$q->execute([$project,$req,$idx]);$links=$q->fetchAll(PDO::FETCH_ASSOC);
  if(!$links){unset($stack[$key]);return $memo[$key]=$direct;}
  $states=[];$rule='ALL'; foreach($links as $l){if(!(int)$l['is_required'])continue;$rule=$l['aggregation_rule'];$states[]=calculateCriterionState($pdo,$project,(int)$l['child_requirement_id'],(int)$l['child_criterion_index'],$memo,$stack)['status'];}
  if(!$states){$computed=$direct['status'];}
  elseif(in_array('failed',$states,true))$computed='failed';
  elseif(in_array('blocked',$states,true))$computed='blocked';
  elseif($rule==='ANY' && in_array('validated',$states,true))$computed='validated';
  elseif($rule==='ALL' && count(array_filter($states,fn($s)=>$s==='validated'))===count($states))$computed='validated';
  elseif(count(array_filter($states,fn($s)=>$s!=='uncovered'))>0)$computed='partial';
  else $computed='uncovered';
  if(stateRank($direct['status'])>stateRank($computed))$computed=$direct['status'];
  unset($stack[$key]);return $memo[$key]=['status'=>$computed,'run_id'=>$direct['run_id'],'date'=>$direct['date']];
}
function recalculateProjectValidation(PDO $pdo,string $project): array {
  $q=$pdo->prepare("SELECT id,acceptance_criteria,attributes,review_status FROM requirements WHERE project_id=? AND TRIM(COALESCE(acceptance_criteria,''))<>''");$q->execute([$project]);$requirements=$q->fetchAll(PDO::FETCH_ASSOC);$memo=[];$stack=[];$tot=0;$valid=0;
  $up=$pdo->prepare("INSERT INTO validation_criterion_states(project_id,requirement_id,criterion_index,status,source_run_id,validated_at) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),source_run_id=VALUES(source_run_id),validated_at=VALUES(validated_at)");
  $ur=$pdo->prepare('UPDATE requirements SET attributes=?,review_status=? WHERE id=?');
  foreach($requirements as $r){$lines=criterionLines((string)$r['acceptance_criteria']);$attrs=json_decode((string)($r['attributes']??'{}'),true);if(!is_array($attrs))$attrs=[];$attrs['criteria_states']=$attrs['criteria_states']??[];$all=true;
    foreach($lines as $idx=>$line){$s=calculateCriterionState($pdo,$project,(int)$r['id'],$idx,$memo,$stack);$tot++;if($s['status']==='validated')$valid++;else$all=false;$up->execute([$project,(int)$r['id'],$idx,$s['status'],$s['run_id'],$s['date']]);$attrs['criteria_states'][(string)$idx]=['checked'=>$s['status']==='validated','status'=>$s['status'],'date'=>$s['date'],'test_run_id'=>$s['run_id']];}
    $review=$all&&count($lines)>0?'Geprüft & Freigegeben':(($r['review_status']==='Geprüft & Freigegeben')?'Wartet auf Überprüfung':$r['review_status']);$ur->execute([json_encode($attrs,JSON_UNESCAPED_UNICODE),$review,(int)$r['id']]);
  }
  return ['total'=>$tot,'validated'=>$valid,'coverage'=>$tot?round($valid*100/$tot):0];
}
