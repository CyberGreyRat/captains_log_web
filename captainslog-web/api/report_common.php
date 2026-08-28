<?php
declare(strict_types=1);
function report_json(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function report_user(): int {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) report_json(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    return $id;
}
function report_access(PDO $pdo, string $projectId, int $userId): void {
    if (($_SESSION['role'] ?? '') === 'admin') return;
    $q = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');
    $q->execute([$projectId, $userId]);
    if (!$q->fetchColumn()) report_json(['success' => false, 'error' => 'Kein Projektzugriff.'], 403);
}
function report_h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function report_v(array $row, string $key, string $fallback = ''): string {
    return trim((string)($row[$key] ?? $fallback));
}
