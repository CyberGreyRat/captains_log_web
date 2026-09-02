<?php
// api/set_requirements.php

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function editorType(string $artifactType): string
{
    return match ($artifactType) {
        'Heading' => 'heading',
        'Requirement' => 'requirement',
        'Risk' => 'risk',
        'Issue' => 'issue',
        'Task' => 'task',
        default => 'text'
    };
}

function artifactType(string $editorType): string
{
    return match (strtolower($editorType)) {
        'heading', 'header' => 'Heading',
        'requirement' => 'Requirement',
        'risk' => 'Risk',
        'issue' => 'Issue',
        'task' => 'Task',
        default => 'Text'
    };
}

function loadDocument(PDO $pdo, string $projectId): array
{
    $links = $pdo->prepare(
        'SELECT source_id, target_id, link_type
         FROM artifact_links
         WHERE source_id IN (SELECT id FROM artifacts WHERE project_id = ?)'
    );
    $links->execute([$projectId]);
    $linksBySource = [];
    foreach ($links->fetchAll(PDO::FETCH_ASSOC) as $link) {
        $linksBySource[$link['source_id']][] = [
            'target_id' => $link['target_id'],
            'link_type' => $link['link_type']
        ];
    }

    $query = $pdo->prepare(
        'SELECT id, artifact_type, content
         FROM artifacts
         WHERE project_id = ?
         ORDER BY sort_order, created_at, id'
    );
    $query->execute([$projectId]);
    $blocks = [];
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $content = json_decode((string) $row['content'], true);
        if (!is_array($content)) $content = [];
        $blockId = (string) ($content['_editorBlockId'] ?? '');
        unset($content['_editorBlockId']);
        $content['artifactId'] = $row['id'];
        $content['links'] = $linksBySource[$row['id']] ?? [];
        $block = ['type' => editorType($row['artifact_type']), 'data' => $content];
        if ($blockId !== '') $block['id'] = $blockId;
        $blocks[] = $block;
    }
    return ['time' => (int) round(microtime(true) * 1000), 'blocks' => $blocks, 'version' => '2.31.0'];
}

try {
    if (empty($_SESSION['user_id'])) respond(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $projectId = trim((string) ($_GET['project_id'] ?? ''));
        if ($projectId === '') respond(['success' => false, 'error' => 'Projekt-ID fehlt.'], 422);
        respond(['success' => true, 'document' => loadDocument($pdo, $projectId)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        respond(['success' => false, 'error' => 'Methode nicht erlaubt.'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(['success' => false, 'error' => 'Ungültiges JSON.'], 400);

    $projectId = trim((string) ($input['project_id'] ?? ''));
    $blocks = $input['blocks'] ?? null;
    if ($projectId === '' || !is_array($blocks)) {
        respond(['success' => false, 'error' => 'Projekt-ID und blocks-Array sind erforderlich.'], 422);
    }

    $pdo->beginTransaction();
    $project = $pdo->prepare('SELECT id FROM projects WHERE id = ? FOR UPDATE');
    $project->execute([$projectId]);
    if (!$project->fetchColumn()) throw new RuntimeException('Projekt wurde nicht gefunden.');

    $existingQuery = $pdo->prepare('SELECT id FROM artifacts WHERE project_id = ? FOR UPDATE');
    $existingQuery->execute([$projectId]);
    $existingIds = array_fill_keys($existingQuery->fetchAll(PDO::FETCH_COLUMN), true);

    $insert = $pdo->prepare(
        'INSERT INTO artifacts (id, project_id, artifact_type, content, sort_order)
         VALUES (?, ?, ?, ?, ?)'
    );
    $update = $pdo->prepare(
        'UPDATE artifacts SET artifact_type = ?, content = ?, sort_order = ?
         WHERE id = ? AND project_id = ?'
    );

    $keptIds = [];
    $pendingLinks = [];
    foreach (array_values($blocks) as $position => $block) {
        if (!is_array($block)) continue;
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $text = trim(strip_tags((string) ($data['text'] ?? '')));
        if ($text === '') continue;

        $requestedId = trim((string) ($data['artifactId'] ?? ''));
        $artifactId = isset($existingIds[$requestedId]) ? $requestedId : uuidV4();
        $type = artifactType((string) ($block['type'] ?? 'text'));
        $links = is_array($data['links'] ?? null) ? $data['links'] : [];
        unset($data['artifactId'], $data['links']);
        $data['_editorBlockId'] = (string) ($block['id'] ?? '');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (isset($existingIds[$artifactId])) {
            $update->execute([$type, $json, $position, $artifactId, $projectId]);
        } else {
            $insert->execute([$artifactId, $projectId, $type, $json, $position]);
        }
        $keptIds[] = $artifactId;
        foreach ($links as $link) $pendingLinks[] = [$artifactId, $link];
    }

    if ($keptIds) {
        $placeholders = implode(',', array_fill(0, count($keptIds), '?'));
        $delete = $pdo->prepare("DELETE FROM artifacts WHERE project_id = ? AND id NOT IN ($placeholders)");
        $delete->execute(array_merge([$projectId], $keptIds));
    } else {
        $pdo->prepare('DELETE FROM artifacts WHERE project_id = ?')->execute([$projectId]);
    }

    $pdo->prepare(
        'DELETE FROM artifact_links
         WHERE source_id IN (SELECT id FROM artifacts WHERE project_id = ?)'
    )->execute([$projectId]);

    $validIds = array_fill_keys($keptIds, true);
    $allowedLinks = ['parent_of', 'generates_risk', 'mitigated_by', 'depends_on'];
    $linkInsert = $pdo->prepare(
        'INSERT IGNORE INTO artifact_links (source_id, target_id, link_type) VALUES (?, ?, ?)'
    );
    foreach ($pendingLinks as [$sourceId, $link]) {
        if (!is_array($link)) continue;
        $targetId = trim((string) ($link['target_id'] ?? ''));
        $linkType = trim((string) ($link['link_type'] ?? ''));
        if ($sourceId !== $targetId && isset($validIds[$targetId]) && in_array($linkType, $allowedLinks, true)) {
            $linkInsert->execute([$sourceId, $targetId, $linkType]);
        }
    }

    $pdo->commit();
    respond(['success' => true, 'document' => loadDocument($pdo, $projectId)]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['success' => false, 'error' => $error->getMessage()], 500);
}
