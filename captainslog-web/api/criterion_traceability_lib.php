<?php
declare(strict_types=1);
function ctCriteria(PDO $pdo, string $documentId): array
{
    $q = $pdo->prepare("SELECT id,content FROM requirement_document_blocks WHERE document_id=? AND block_type='acceptanceCriteria' ORDER BY sort_order,id");
    $q->execute([$documentId]);
    $items = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $block) {
        $data = json_decode($block['content'] ?? '{}', true) ?: [];
        foreach (($data['items'] ?? []) as $criterion) {
            if (empty($criterion['id']))
                continue;
            $items[] = ['id' => (string) $criterion['id'], 'text' => (string) ($criterion['text'] ?? ''), 'revision' => max(1, (int) ($criterion['revision'] ?? 1)), 'rule' => (string) ($data['rule'] ?? 'ALL'), 'block_id' => $block['id']];
        }
    }
    return $items;
}
function ctCriterion(PDO $pdo, string $documentId, string $criterionId): ?array
{
    foreach (ctCriteria($pdo, $documentId) as $c)
        if ($c['id'] === $criterionId)
            return $c;
    return null;
}
function ctStatus(PDO $pdo, string $documentId, string $criterionId, array &$seen = []): array
{
    $key = $documentId . ':' . $criterionId;
    if (isset($seen[$key]))
        return ['status' => 'open', 'reason' => 'Zyklus'];
    $seen[$key] = 1;
    $criterion = ctCriterion($pdo, $documentId, $criterionId);
    if (!$criterion)
        return ['status' => 'open', 'reason' => 'Kriterium fehlt'];
    $e = $pdo->prepare('SELECT result,criterion_revision FROM document_criterion_evidence WHERE document_id=? AND criterion_id=? ORDER BY updated_at DESC');
    $e->execute([$documentId, $criterionId]);
    $evidence = $e->fetchAll(PDO::FETCH_ASSOC);
    if ($evidence) {
        $valid = array_values(array_filter($evidence, fn($x) => (int) $x['criterion_revision'] === (int) $criterion['revision']));
        if (!$valid)
            return ['status' => 'outdated', 'reason' => 'Nur Nachweise älterer Revisionen'];
        $results = array_column($valid, 'result');
        if (in_array('failed', $results, true))
            return ['status' => 'failed', 'reason' => 'Aktueller Test fehlgeschlagen'];
        if (in_array('blocked', $results, true))
            return ['status' => 'blocked', 'reason' => 'Aktueller Test blockiert'];
        if (in_array('passed', $results, true))
            return ['status' => 'passed', 'reason' => 'Aktueller Test bestanden'];
    }
    $q = $pdo->prepare('SELECT child_document_id,child_criterion_id,aggregation_rule FROM document_criterion_links WHERE parent_document_id=? AND parent_criterion_id=?');
    $q->execute([$documentId, $criterionId]);
    $links = $q->fetchAll(PDO::FETCH_ASSOC);
    if (!$links)
        return ['status' => 'open', 'reason' => 'Kein Child-Kriterium oder Testnachweis'];
    $states = [];
    foreach ($links as $l)
        $states[] = ctStatus($pdo, $l['child_document_id'], $l['child_criterion_id'], $seen)['status'];
    $rule = $links[0]['aggregation_rule'] ?? 'ALL';
    if (in_array('failed', $states, true))
        return ['status' => 'failed', 'reason' => 'Mindestens ein Child-Kriterium fehlgeschlagen'];
    if ($rule === 'ANY' && in_array('passed', $states, true))
        return ['status' => 'passed', 'reason' => 'Mindestens ein Child-Kriterium bestanden'];
    if ($rule === 'ALL' && count(array_filter($states, fn($s) => $s === 'passed')) === count($states))
        return ['status' => 'passed', 'reason' => 'Alle Child-Kriterien bestanden'];
    if (array_filter($states, fn($s) => in_array($s, ['passed', 'blocked', 'outdated'], true)))
        return ['status' => 'covered', 'reason' => 'Child-Kriterien teilweise nachgewiesen'];
    return ['status' => 'covered', 'reason' => 'Child-Kriterien zugeordnet'];
}
function ctRecalculate(PDO $pdo, string $documentId): void
{
    $documents = [$documentId];
    $queue = [$documentId];
    $p = $pdo->prepare("SELECT source_document_id FROM requirement_document_links WHERE target_document_id=? AND link_type='parent_of'");
    while ($queue) {
        $id = array_shift($queue);
        $p->execute([$id]);
        foreach ($p->fetchAll(PDO::FETCH_COLUMN) as $parent)
            if (!in_array($parent, $documents, true)) {
                $documents[] = $parent;
                $queue[] = $parent;
            }
    }
    foreach ($documents as $docId) {
        $criteria = ctCriteria($pdo, $docId);
        if (!$criteria)
            continue;
        $counts = ['covered' => 0, 'executed' => 0, 'passed' => 0, 'failed' => 0, 'blocked' => 0, 'outdated' => 0];
        foreach ($criteria as $c) {
            $seen = [];
            $status = ctStatus($pdo, $docId, $c['id'], $seen)['status'];
            if ($status !== 'open')
                $counts['covered']++;
            if (in_array($status, ['passed', 'failed', 'blocked'], true))
                $counts['executed']++;
            if (isset($counts[$status]))
                $counts[$status]++;
        }
        $total = count($criteria);
        $coverage = round($counts['executed'] / $total * 100, 2);
        $passed = round($counts['passed'] / $total * 100, 2);
        $status = $counts['outdated'] ? 'Outdated' : ($counts['failed'] ? 'Failed' : ($counts['passed'] === $total ? 'Passed' : ($counts['covered'] ? 'Partial' : 'Not Covered')));
        $r = $pdo->prepare('SELECT revision FROM requirement_documents WHERE id=?');
        $r->execute([$docId]);
        $revision = (int) $r->fetchColumn();
        $up = $pdo->prepare('INSERT INTO document_verification_snapshots(document_id,revision,leaf_count,covered_count,executed_count,passed_count,failed_count,blocked_count,outdated_count,coverage_percent,passed_percent,verification_status)VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE revision=VALUES(revision),leaf_count=VALUES(leaf_count),covered_count=VALUES(covered_count),executed_count=VALUES(executed_count),passed_count=VALUES(passed_count),failed_count=VALUES(failed_count),blocked_count=VALUES(blocked_count),outdated_count=VALUES(outdated_count),coverage_percent=VALUES(coverage_percent),passed_percent=VALUES(passed_percent),verification_status=VALUES(verification_status)');
        $up->execute([$docId, $revision, $total, $counts['covered'], $counts['executed'], $counts['passed'], $counts['failed'], $counts['blocked'], $counts['outdated'], $coverage, $passed, $status]);
    }
}
