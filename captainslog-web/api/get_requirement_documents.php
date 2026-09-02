<?php
require_once __DIR__.'/requirement_document_common.php'; reqAuth();
try {
 $projectId=trim((string)($_GET['project_id']??'')); if($projectId==='') reqRespond(['success'=>false,'error'=>'Projekt-ID fehlt.'],422);
 reqProject($pdo,$projectId);
 $s=$pdo->prepare("SELECT d.id,d.requirement_key,d.requirement_type,d.title,d.status,d.priority,d.sort_order,d.updated_at,
 (SELECT COUNT(*) FROM requirement_document_links l WHERE l.source_document_id=d.id OR l.target_document_id=d.id) link_count
 FROM requirement_documents d WHERE d.project_id=? ORDER BY d.requirement_type,d.sort_order,d.requirement_key");
 $s->execute([$projectId]); reqRespond(['success'=>true,'documents'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
} catch(Throwable $e){reqRespond(['success'=>false,'error'=>$e->getMessage()],500);}
