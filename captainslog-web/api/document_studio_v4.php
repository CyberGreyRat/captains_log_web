<?php
require_once __DIR__ . '/document_studio_common.php';
$u = uid();
try {
    $action = $_GET['action'] ?? '';
    if ($action === 'list') {
        $pid = trim((string) ($_GET['project_id'] ?? ''));
        project($pdo, $pid);
        $q = $pdo->prepare("SELECT d.id,d.requirement_key,d.requirement_type,d.document_type_id,d.title,d.status,d.priority,d.relevance,d.review_status,d.revision,COALESCE(t.is_chapter,0)is_chapter,COALESCE(v.coverage_percent,0)coverage_percent,COALESCE(v.verification_status,'Not Covered')verification_status FROM requirement_documents d LEFT JOIN document_types t ON t.id=d.document_type_id LEFT JOIN document_verification_snapshots v ON v.document_id=d.id WHERE d.project_id=? ORDER BY d.sort_order,d.requirement_key");
        $q->execute([$pid]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        $l = $pdo->prepare("SELECT source_document_id parent_id,target_document_id child_id FROM requirement_document_links WHERE project_id=? AND link_type='parent_of'");
        $l->execute([$pid]);
        $parents = [];
        foreach ($l->fetchAll(PDO::FETCH_ASSOC) as $x)
            $parents[$x['child_id']][] = $x['parent_id'];
        foreach ($rows as &$r)
            $r['parent_ids'] = $parents[$r['id']] ?? [];
        out(['success' => true, 'documents' => $rows]);
    }
    if ($action === 'get') {
        $id = trim((string) ($_GET['id'] ?? ''));
        $d = doc($pdo, $id);
        $d['metadata'] = json_decode($d['metadata'] ?? '{}', true) ?: [];
        $q = $pdo->prepare('SELECT id,editor_block_id,block_type,content FROM requirement_document_blocks WHERE document_id=? ORDER BY sort_order,id');
        $q->execute([$id]);
        $blocks = [];
        $seen = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $data = json_decode($r['content'], true) ?: [];
            $data['_recordId'] = $r['id'];
            if (in_array($r['block_type'], ['image', 'file'], true) && !empty($data['fileId']))
                $data['url'] = '../api/document_file.php?id=' . rawurlencode($data['fileId']);
            $type = $r['block_type'] === 'requirement' ? 'text' : $r['block_type'];
            unset($data['status'], $data['priority']);
            if ($type === 'heading')
                $data['level'] = max(3, min(5, (int) ($data['level'] ?? 3)));
            $b = ['type' => $type, 'data' => $data];
            $eid = (string) ($r['editor_block_id'] ?? '');
            $blocks[] = $b;
        }
        out(['success' => true, 'document' => $d, 'editor' => ['time' => (int) (microtime(true) * 1000), 'blocks' => $blocks, 'version' => '2.31.0']]);
    }
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $x = body();
        $pid = trim((string) ($x['project_id'] ?? ''));
        project($pdo, $pid);
        $typeId = trim((string) ($x['document_type_id'] ?? ''));
        $q = $pdo->prepare('SELECT * FROM document_types WHERE id=? AND is_active=1 AND(project_id IS NULL OR project_id=?)');
        $q->execute([$typeId, $pid]);
        $type = $q->fetch(PDO::FETCH_ASSOC);
        if (!$type)
            out(['success' => false, 'error' => 'Dokumenttyp nicht gefunden.'], 404);
        $title = trim((string) ($x['title'] ?? ''));
        if ($title === '')
            out(['success' => false, 'error' => 'Titel fehlt.'], 422);
        $blocks = [];
        $defaults = ['status' => 'Open', 'priority' => 'Medium', 'relevance' => 'Must', 'review_status' => 'New'];
        $tid = trim((string) ($x['template_id'] ?? ''));
        if ($tid !== '') {
            $t = $pdo->prepare('SELECT blocks,default_metadata FROM requirement_document_templates WHERE id=? AND is_active=1 AND(project_id IS NULL OR project_id=?)');
            $t->execute([$tid, $pid]);
            if ($tpl = $t->fetch(PDO::FETCH_ASSOC)) {
                $blocks = json_decode($tpl['blocks'], true) ?: [];
                $defaults = array_replace($defaults, json_decode($tpl['default_metadata'] ?? '{}', true) ?: []);
            }
        }
        $pdo->beginTransaction();
        $key = (string) $type['type_key'];
        $pdo->prepare('INSERT INTO requirement_document_sequences(project_id,requirement_type,last_number)VALUES(?,?,1)ON DUPLICATE KEY UPDATE last_number=last_number+1')->execute([$pid, $key]);
        $n = $pdo->prepare('SELECT last_number FROM requirement_document_sequences WHERE project_id=? AND requirement_type=? FOR UPDATE');
        $n->execute([$pid, $key]);
        $num = (int) $n->fetchColumn();
        $display = str_replace(['{TYPE}', '{NUMBER:3}', '{NUMBER:4}', '{NUMBER}'], [$key, str_pad((string) $num, 3, '0', STR_PAD_LEFT), str_pad((string) $num, 4, '0', STR_PAD_LEFT), (string) $num], (string) $type['number_pattern']);
        $id = uuid4();
        $pdo->prepare('INSERT INTO requirement_documents(id,project_id,document_type_id,requirement_key,requirement_type,title,status,priority,relevance,review_status,revision,metadata,created_by,updated_by)VALUES(?,?,?,?,?,?,?,?,?,?,1,?,?,?)')->execute([$id, $pid, $typeId, $display, $key, $title, $defaults['status'], $defaults['priority'], $defaults['relevance'], $defaults['review_status'], json_encode($defaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $u, $u]);
        $ins = $pdo->prepare('INSERT INTO requirement_document_blocks(id,document_id,editor_block_id,block_type,content,sort_order)VALUES(?,?,?,?,?,?)');
        foreach (array_values($blocks ?: [['type' => 'text', 'data' => ['text' => '']]]) as $i => $b) {
            $typeName = $b['type'] ?? 'text';
            if ($typeName === 'requirement')
                $typeName = 'text';
            $ins->execute([uuid4(), $id, null, $typeName, json_encode($b['data'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $i]);
        }
        $pdo->commit();
        out(['success' => true, 'id' => $id, 'key' => $display], 201);
    }
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $x = body();
        $id = trim((string) ($x['id'] ?? ''));
        $blocks = $x['blocks'] ?? null;
        if (!is_array($blocks))
            out(['success' => false, 'error' => 'Blöcke fehlen.'], 422);
        $pdo->beginTransaction();
        $before = doc($pdo, $id);
        $normalized = [];
        foreach (array_values($blocks) as $b) {
            $data = is_array($b['data'] ?? null) ? $b['data'] : [];
            unset($data['_recordId'], $data['url']);
            $normalized[] = ['type' => $b['type'] ?? 'text', 'data' => $data];
        }
        $fingerprint = hash('sha256', json_encode([$x['title'] ?? '', $normalized], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $oldMeta = json_decode($before['metadata'] ?? '{}', true) ?: [];
        $revision = (int) $before['revision'] + ($fingerprint !== ($oldMeta['content_fingerprint'] ?? '') ? 1 : 0);
        $meta = array_replace($oldMeta, is_array($x['metadata'] ?? null) ? $x['metadata'] : []);
        $meta['content_fingerprint'] = $fingerprint;
        $pdo->prepare('UPDATE requirement_documents SET title=?,status=?,priority=?,relevance=?,review_status=?,revision=?,metadata=?,updated_by=? WHERE id=?')->execute([trim((string) ($x['title'] ?? '')), $x['status'] ?? 'Open', $x['priority'] ?? 'Medium', $x['relevance'] ?? 'Must', $x['review_status'] ?? 'New', $revision, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $u, $id]);
        $o = $pdo->prepare('SELECT id FROM requirement_document_blocks WHERE document_id=?');
        $o->execute([$id]);
        $known = array_fill_keys($o->fetchAll(PDO::FETCH_COLUMN), 1);
        $keep = [];
        $usedEditor = [];
        $ins = $pdo->prepare('INSERT INTO requirement_document_blocks(id,document_id,editor_block_id,block_type,content,sort_order)VALUES(?,?,?,?,?,?)');
        $upd = $pdo->prepare('UPDATE requirement_document_blocks SET editor_block_id=?,block_type=?,content=?,sort_order=? WHERE id=? AND document_id=?');
        foreach (array_values($blocks) as $i => $b) {
            $data = is_array($b['data'] ?? null) ? $b['data'] : [];
            $rid = trim((string) ($data['_recordId'] ?? ''));
            unset($data['_recordId'], $data['url']);
            $eid = trim((string) ($b['id'] ?? ''));
            $eid = null;
            $typeName = $b['type'] ?? 'text';
            if ($typeName === 'requirement')
                $typeName = 'text';
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (isset($known[$rid])) {
                $upd->execute([$eid, $typeName, $json, $i, $rid, $id]);
                $keep[] = $rid;
            } else {
                $rid = uuid4();
                $ins->execute([$rid, $id, $eid, $typeName, $json, $i]);
                $keep[] = $rid;
            }
        }
        if ($keep) {
            $marks = implode(',', array_fill(0, count($keep), '?'));
            $pdo->prepare("DELETE FROM requirement_document_blocks WHERE document_id=? AND id NOT IN($marks)")->execute(array_merge([$id], $keep));
        } else
            $pdo->prepare('DELETE FROM requirement_document_blocks WHERE document_id=?')->execute([$id]);
        $pdo->commit();
        out(['success' => true, 'revision' => $revision]);
    }
    out(['success' => false, 'error' => 'Unbekannte Aktion.'], 400);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction())
        $pdo->rollBack();
    out(['success' => false, 'error' => $e->getMessage()], 500);
}
