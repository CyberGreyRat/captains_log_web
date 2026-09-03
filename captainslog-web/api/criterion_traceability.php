<?php
declare(strict_types=1);
require_once __DIR__ . '/document_studio_common.php';
require_once __DIR__ . '/criterion_traceability_lib.php';
$userId = uid();
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'context';
    if ($method === 'GET' && $action === 'context') {
        $documentId = trim((string) ($_GET['document_id'] ?? ''));
        $doc = doc($pdo, $documentId);
        $criteria = ctCriteria($pdo, $documentId);
        $parents = [];
        $q = $pdo->prepare("SELECT d.id,d.requirement_key,d.title FROM requirement_document_links l JOIN requirement_documents d ON d.id=l.source_document_id WHERE l.target_document_id=? AND l.link_type='parent_of'");
        $q->execute([$documentId]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $p['criteria'] = ctCriteria($pdo, $p['id']);
            $parents[] = $p;
        }
        $m = $pdo->prepare('SELECT * FROM document_criterion_links WHERE child_document_id=?');
        $m->execute([$documentId]);
        $maps = $m->fetchAll(PDO::FETCH_ASSOC);
        $t = $pdo->prepare("SELECT id,result,COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.label')),CONCAT('Testnachweis ',id))label,document_revision FROM document_test_links WHERE document_id=? ORDER BY executed_at DESC,created_at DESC");
        $t->execute([$documentId]);
        $tests = $t->fetchAll(PDO::FETCH_ASSOC);
        foreach ($criteria as &$c) {
            $seen = [];
            $c += ctStatus($pdo, $documentId, $c['id'], $seen);
        }
        out(['success' => true, 'document' => $doc, 'criteria' => $criteria, 'parents' => $parents, 'mappings' => $maps, 'test_links' => $tests]);
    }
    if ($method === 'POST') {
        $x = body();
        $documentId = trim((string) ($x['document_id'] ?? ''));
        $doc = doc($pdo, $documentId);
        if ($action === 'map') {
            $childId = trim((string) ($x['child_criterion_id'] ?? ''));
            $parentDoc = trim((string) ($x['parent_document_id'] ?? ''));
            $parentId = trim((string) ($x['parent_criterion_id'] ?? ''));
            if (!ctCriterion($pdo, $documentId, $childId) || !ctCriterion($pdo, $parentDoc, $parentId))
                out(['success' => false, 'error' => 'Kriterium nicht gefunden.'], 422);
            $pdo->prepare('INSERT INTO document_criterion_links(id,project_id,parent_document_id,parent_criterion_id,child_document_id,child_criterion_id,aggregation_rule,created_by)VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE aggregation_rule=VALUES(aggregation_rule)')->execute([uuid4(), $doc['project_id'], $parentDoc, $parentId, $documentId, $childId, in_array($x['aggregation_rule'] ?? 'ALL', ['ALL', 'ANY'], true) ? $x['aggregation_rule'] : 'ALL', $userId]);
            ctRecalculate($pdo, $documentId);
            out(['success' => true], 201);
        }
        if ($action === 'evidence') {
            $criterionId = trim((string) ($x['criterion_id'] ?? ''));
            $testLinkId = trim((string) ($x['document_test_link_id'] ?? ''));
            $criterion = ctCriterion($pdo, $documentId, $criterionId);
            if (!$criterion)
                out(['success' => false, 'error' => 'Kriterium nicht gefunden.'], 422);
            $q = $pdo->prepare('SELECT result FROM document_test_links WHERE id=? AND document_id=?');
            $q->execute([$testLinkId, $documentId]);
            $result = $q->fetchColumn();
            if ($result === false)
                out(['success' => false, 'error' => 'Testnachweis nicht gefunden.'], 404);
            $pdo->prepare('INSERT INTO document_criterion_evidence(id,project_id,document_id,criterion_id,criterion_revision,document_test_link_id,result,created_by)VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE criterion_revision=VALUES(criterion_revision),result=VALUES(result),updated_at=CURRENT_TIMESTAMP')->execute([uuid4(), $doc['project_id'], $documentId, $criterionId, $criterion['revision'], $testLinkId, $result, $userId]);
            ctRecalculate($pdo, $documentId);
            out(['success' => true], 201);
        }
    }
    if ($method === 'DELETE') {
        $x = body();
        if ($action === 'map')
            $pdo->prepare('DELETE FROM document_criterion_links WHERE id=?')->execute([$x['id'] ?? '']);
        else
            $pdo->prepare('DELETE FROM document_criterion_evidence WHERE id=?')->execute([$x['id'] ?? '']);
        if (!empty($x['document_id']))
            ctRecalculate($pdo, (string) $x['document_id']);
        out(['success' => true]);
    }
    out(['success' => false, 'error' => 'Methode nicht erlaubt.'], 405);
} catch (Throwable $e) {
    out(['success' => false, 'error' => $e->getMessage()], 500);
}
