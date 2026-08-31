<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';
header('Content-Type: application/json; charset=utf-8');

function risk_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function risk_ids(mixed $values): array {
    return array_values(array_unique(array_filter(array_map('intval', is_array($values) ? $values : []), static fn(int $id): bool => $id > 0)));
}
function validate_targets(PDO $pdo, string $sql, string $projectId, array $ids, string $label): void {
    if (!$ids) return;
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(str_replace('{ids}', $marks, $sql));
    $statement->execute(array_merge([$projectId], $ids));
    if ((int)$statement->fetchColumn() !== count($ids)) throw new RuntimeException("Mindestens eine verknüpfte {$label} ist ungültig.");
}
function audit_link(PDO $pdo, string $projectId, int $riskId, string $riskKey, string $riskTitle, string $action, string $group, array $ids, int $userId): void {
    if (!$ids) return;
    $statement = $pdo->prepare("INSERT INTO audit_log(project_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,source_type,source_name) VALUES(?,?,?,?,?,?,?,?,?,?)");
    $statement->execute([$projectId,'requirement',(string)$riskId,$riskKey,$riskTitle,$action,json_encode(['link_group'=>$group,'target_ids'=>$ids], JSON_UNESCAPED_UNICODE),$userId,'web','set_risk.php']);
}

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $role = (string)($_SESSION['role'] ?? 'viewer');
    if ($userId <= 0) risk_json(['success'=>false,'error'=>'Nicht angemeldet.'],401);
    if ($role === 'viewer') risk_json(['success'=>false,'error'=>'Keine Schreibberechtigung.'],403);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) throw new RuntimeException('Ungültige JSON-Daten.');

    $id = !empty($data['id']) ? (int)$data['id'] : null;
    $projectId = trim((string)($data['project_id'] ?? ''));
    $title = trim((string)($data['title'] ?? ''));
    if ($projectId === '' || $title === '') throw new RuntimeException('Projekt und Risikotitel sind Pflichtfelder.');

    $access = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');
    $access->execute([$projectId,$userId]);
    if ($role !== 'admin' && !$access->fetchColumn()) throw new RuntimeException('Kein Projektzugriff.');

    $w = min(5,max(1,(int)($data['w'] ?? 1)));
    $e = min(5,max(1,(int)($data['e'] ?? 1)));
    $rw = min(5,max(1,(int)($data['residual_w'] ?? $w)));
    $re = min(5,max(1,(int)($data['residual_e'] ?? $e)));
    $allowedStatuses = ['open','assessment','measures','implementation','verification','residual_review','accepted','closed'];
    $workflowStatus = (string)($data['workflow_status'] ?? 'open');
    if (!in_array($workflowStatus,$allowedStatuses,true)) $workflowStatus='open';

    $attributes = [
        'risk_type' => trim((string)($data['risk_type'] ?? 'technical_product')),
        'cause' => trim((string)($data['cause'] ?? '')),
        'malfunction' => trim((string)($data['malfunction'] ?? '')),
        'effect' => trim((string)($data['effect'] ?? '')),
        'w' => $w, 'e' => $e, 'risk_score' => $w*$e,
        'review_date' => trim((string)($data['review_date'] ?? '')),
        'workflow_status' => $workflowStatus,
        'decision' => trim((string)($data['decision'] ?? '')),
        'mitigation_plan' => trim((string)($data['mitigation_plan'] ?? '')),
        'implementation_status' => trim((string)($data['implementation_status'] ?? 'open')),
        'residual_w' => $rw, 'residual_e' => $re, 'residual_score' => $rw*$re,
        'residual_accepted' => !empty($data['residual_accepted']),
        'residual_reason' => trim((string)($data['residual_reason'] ?? ''))
    ];
    $requirementIds=risk_ids($data['requirement_ids']??[]);
    $verificationIds=risk_ids($data['verification_ids']??[]);
    $taskIds=risk_ids($data['task_ids']??[]);
    $issueIds=risk_ids($data['issue_ids']??[]);

    validate_targets($pdo,"SELECT COUNT(*) FROM requirements WHERE project_id=? AND type NOT IN ('RISK','TC','TR') AND id IN ({ids})",$projectId,$requirementIds,'Anforderung');
    validate_targets($pdo,"SELECT COUNT(*) FROM requirements WHERE project_id=? AND type IN ('TC','TR') AND id IN ({ids})",$projectId,$verificationIds,'Verifikation');
    validate_targets($pdo,"SELECT COUNT(*) FROM project_tasks WHERE project_id=? AND id IN ({ids})",$projectId,$taskIds,'Aufgabe');
    validate_targets($pdo,"SELECT COUNT(*) FROM issues WHERE project_id=? AND id IN ({ids})",$projectId,$issueIds,'Issue');

    $pdo->beginTransaction();
    set_audit_context($pdo,'web',basename($_SERVER['SCRIPT_NAME']));
    if ($id) {
        $existing=$pdo->prepare("SELECT * FROM requirements WHERE id=? AND project_id=? AND type='RISK' FOR UPDATE");
        $existing->execute([$id,$projectId]); $row=$existing->fetch(PDO::FETCH_ASSOC);
        if(!$row) throw new RuntimeException('Risiko wurde nicht gefunden.');
        $oldAttrs=json_decode($row['attributes']??'{}',true); if(!is_array($oldAttrs))$oldAttrs=[];
        $attributes=array_replace($oldAttrs,$attributes);
        $q=$pdo->prepare("UPDATE requirements SET title=?,description=?,source_contact=?,attributes=? WHERE id=? AND project_id=? AND type='RISK'");
        $q->execute([$title,$title,trim((string)($data['responsible']??'')),json_encode($attributes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$id,$projectId]);
        $savedId=$id; $riskKey=(string)$row['req_key'];
    } else {
        $lock=$pdo->prepare('SELECT id FROM projects WHERE id=? FOR UPDATE');$lock->execute([$projectId]);if(!$lock->fetchColumn())throw new RuntimeException('Projekt nicht gefunden.');
        $n=$pdo->prepare('SELECT COALESCE(MAX(serial_number),0)+1 FROM requirements WHERE project_id=?');$n->execute([$projectId]);$serial=(int)$n->fetchColumn();$riskKey='RISK-'.str_pad((string)$serial,3,'0',STR_PAD_LEFT);
        $q=$pdo->prepare("INSERT INTO requirements(project_id,serial_number,display_number,req_key,type,title,description,status,source_contact,review_status,parents,children,attributes) VALUES(?,?,?,?, 'RISK',?,?, 'open',?,'Neu','[]','[]',?)");
        $q->execute([$projectId,$serial,$serial,$riskKey,$title,$title,trim((string)($data['responsible']??'')),json_encode($attributes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$savedId=(int)$pdo->lastInsertId();
    }

    $oldReq=$pdo->prepare("SELECT requirement_id,link_group FROM risk_requirement_links WHERE risk_id=?");$oldReq->execute([$savedId]);$previousReq=['control'=>[],'verification'=>[]];foreach($oldReq->fetchAll(PDO::FETCH_ASSOC) as $r)$previousReq[$r['link_group']][]=(int)$r['requirement_id'];
    $oldTask=$pdo->prepare('SELECT task_id FROM risk_task_links WHERE risk_id=?');$oldTask->execute([$savedId]);$previousTasks=array_map('intval',$oldTask->fetchAll(PDO::FETCH_COLUMN));
    $oldIssue=$pdo->prepare('SELECT issue_id FROM risk_issue_links WHERE risk_id=?');$oldIssue->execute([$savedId]);$previousIssues=array_map('intval',$oldIssue->fetchAll(PDO::FETCH_COLUMN));

    $pdo->prepare('DELETE FROM risk_requirement_links WHERE risk_id=?')->execute([$savedId]);
    $pdo->prepare('DELETE FROM risk_task_links WHERE risk_id=?')->execute([$savedId]);
    $pdo->prepare('DELETE FROM risk_issue_links WHERE risk_id=?')->execute([$savedId]);
    $ri=$pdo->prepare('INSERT INTO risk_requirement_links(risk_id,requirement_id,link_group,created_by) VALUES(?,?,?,?)');foreach($requirementIds as $target)$ri->execute([$savedId,$target,'control',$userId]);foreach($verificationIds as $target)$ri->execute([$savedId,$target,'verification',$userId]);
    $ti=$pdo->prepare('INSERT INTO risk_task_links(risk_id,task_id,created_by) VALUES(?,?,?)');foreach($taskIds as $target)$ti->execute([$savedId,$target,$userId]);
    $ii=$pdo->prepare('INSERT INTO risk_issue_links(risk_id,issue_id,created_by) VALUES(?,?,?)');foreach($issueIds as $target)$ii->execute([$savedId,$target,$userId]);

    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'LINK','requirements',array_values(array_diff($requirementIds,$previousReq['control'])),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'UNLINK','requirements',array_values(array_diff($previousReq['control'],$requirementIds)),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'LINK','verification',array_values(array_diff($verificationIds,$previousReq['verification'])),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'UNLINK','verification',array_values(array_diff($previousReq['verification'],$verificationIds)),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'LINK','tasks',array_values(array_diff($taskIds,$previousTasks)),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'UNLINK','tasks',array_values(array_diff($previousTasks,$taskIds)),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'LINK','issues',array_values(array_diff($issueIds,$previousIssues)),$userId);
    audit_link($pdo,$projectId,$savedId,$riskKey,$title,'UNLINK','issues',array_values(array_diff($previousIssues,$issueIds)),$userId);

    $pdo->commit();risk_json(['success'=>true,'id'=>$savedId,'req_key'=>$riskKey]);
} catch(Throwable $error){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();risk_json(['success'=>false,'error'=>$error->getMessage()],500);}
