<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
try {
  require_edit_permission();
  $d = json_decode(file_get_contents('php://input'), true) ?: [];
  $project = $d['project_id'] ?? '';
  $title = trim($d['title'] ?? '');
  $id = (int) ($d['id'] ?? 0);
  $uid = $_SESSION['user_id'] ?? 0;
  if (!$project || !$title || !$uid)
    throw new Exception('Projekt und Titel sind Pflichtfelder.');
  $a = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=?');
  $a->execute([$project, $uid]);
  if (!$a->fetchColumn())
    throw new Exception('Kein Projektzugriff.');
  $allowedStatus = ['open', 'in_progress', 'waiting_response', 'ready_for_test', 'approved', 'closed', 'rejected'];
  $allowedType = ['bug', 'change_request', 'customer_feedback', 'question', 'deviation', 'improvement'];
  $allowedPriority = ['low', 'medium', 'high', 'critical'];
  $allowedSeverity = ['none', 'low', 'medium', 'high', 'critical'];
  $status = in_array($d['status'] ?? '', $allowedStatus, true) ? $d['status'] : 'open';
  $type = in_array($d['issue_type'] ?? '', $allowedType, true) ? $d['issue_type'] : 'bug';
  $priority = in_array($d['priority'] ?? '', $allowedPriority, true) ? $d['priority'] : 'medium';
  $severity = in_array($d['severity'] ?? '', $allowedSeverity, true) ? $d['severity'] : 'medium';
  $pdo->beginTransaction();
  if ($id) {
    $old = $pdo->prepare('SELECT * FROM issues WHERE id=? AND project_id=?');
    $old->execute([$id, $project]);
    $before = $old->fetch();
    if (!$before)
      throw new Exception('Issue nicht gefunden.');
    $q = $pdo->prepare('UPDATE issues SET external_id=?,issue_type=?,title=?,description=?,status=?,priority=?,severity=?,category=?,assignee_user_id=?,external_reporter=?,external_assignee=?,reported_at=?,due_date=?,external_response=?,internal_response=?,resolution=?,resolved_at=IF(?="closed",COALESCE(resolved_at,NOW()),NULL) WHERE id=? AND project_id=?');
    $q->execute([$d['external_id'] ?? null, $type, $title, $d['description'] ?? null, $status, $priority, $severity, $d['category'] ?? null, $d['assignee_user_id'] ?: null, $d['external_reporter'] ?? null, $d['external_assignee'] ?? null, $d['reported_at'] ?: null, $d['due_date'] ?: null, $d['external_response'] ?? null, $d['internal_response'] ?? null, $d['resolution'] ?? null, $status, $id, $project]);
    $action = 'UPDATE';
  } else {
    $n = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(issue_key,'-',-1) AS UNSIGNED)),0)+1 FROM issues WHERE project_id=?");
    $n->execute([$project]);
    $key = 'ISSUE-' . str_pad((string) $n->fetchColumn(), 3, '0', STR_PAD_LEFT);
    $q = $pdo->prepare('INSERT INTO issues(project_id,issue_key,external_id,issue_type,title,description,status,priority,severity,category,reporter_user_id,assignee_user_id,external_reporter,external_assignee,reported_at,due_date,external_response,internal_response,resolution,source_type,source_document,source_sheet,source_row,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$project, $key, $d['external_id'] ?? null, $type, $title, $d['description'] ?? null, $status, $priority, $severity, $d['category'] ?? null, $uid, $d['assignee_user_id'] ?: null, $d['external_reporter'] ?? null, $d['external_assignee'] ?? null, $d['reported_at'] ?: null, $d['due_date'] ?: null, $d['external_response'] ?? null, $d['internal_response'] ?? null, $d['resolution'] ?? null, $d['source_type'] ?? 'manual', $d['source_document'] ?? null, $d['source_sheet'] ?? null, $d['source_row'] ?: null, $uid]);
    $id = (int) $pdo->lastInsertId();
    $action = 'CREATE';
  }
  $pdo->prepare('DELETE FROM issue_requirements WHERE issue_id=?')->execute([$id]);
  $ir = $pdo->prepare('INSERT INTO issue_requirements(issue_id,requirement_id,relation_type) VALUES(?,?,?)');
  foreach (($d['requirement_ids'] ?? []) as $rid) {
    if ((int) $rid)
      $ir->execute([$id, (int) $rid, 'affects']);
  }
  $pdo->prepare('DELETE FROM issue_tasks WHERE issue_id=?')->execute([$id]);
  $it = $pdo->prepare('INSERT INTO issue_tasks(issue_id,task_id,relation_type) VALUES(?,?,?)');
  foreach (($d['task_ids'] ?? []) as $tid) {
    if ((int) $tid)
      $it->execute([$id, (int) $tid, 'implementation']);
  }
  $hist = $pdo->prepare('INSERT INTO issue_history(issue_id,project_id,action,change_data,modified_by,hostname) VALUES(?,?,?,?,?,?)');
  $hist->execute([$id, $project, $action, json_encode(['status' => $status, 'title' => $title], JSON_UNESCAPED_UNICODE), $uid, $_SESSION['hostname'] ?? 'LocalPC']);
  $pdo->commit();
  echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
  if ($pdo->inTransaction())
    $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
