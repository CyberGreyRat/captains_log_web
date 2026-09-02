<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
function out(array $d, int $s = 200): never
{
    http_response_code($s);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function uid(): int
{
    if (empty($_SESSION['user_id']))
        out(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    return (int) $_SESSION['user_id'];
}
function body(): array
{
    $d = json_decode(file_get_contents('php://input'), true);
    if (!is_array($d))
        out(['success' => false, 'error' => 'Ungültiges JSON.'], 400);
    return $d;
}
function uuid4(): string
{
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 15) | 64);
    $d[8] = chr((ord($d[8]) & 63) | 128);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}
function doc(PDO $p, string $id): array
{
    $q = $p->prepare('SELECT * FROM requirement_documents WHERE id=?');
    $q->execute([$id]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    if (!$r)
        out(['success' => false, 'error' => 'Dokument nicht gefunden.'], 404);
    return $r;
}
function project(PDO $p, string $id): void
{
    $q = $p->prepare('SELECT id FROM projects WHERE id=?');
    $q->execute([$id]);
    if (!$q->fetchColumn())
        out(['success' => false, 'error' => 'Projekt nicht gefunden.'], 404);
}
function cycle(PDO $p, string $parent, string $child): bool
{
    if ($parent === $child)
        return true;
    $seen = [];
    $queue = [$child];
    $q = $p->prepare("SELECT target_document_id FROM requirement_document_links WHERE source_document_id=? AND link_type='parent_of'");
    while ($queue) {
        $x = array_shift($queue);
        if (isset($seen[$x]))
            continue;
        $seen[$x] = 1;
        if ($x === $parent)
            return true;
        $q->execute([$x]);
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $n)
            $queue[] = $n;
    }
    return false;
}
