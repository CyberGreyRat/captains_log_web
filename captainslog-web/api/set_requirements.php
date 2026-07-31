<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['project_id']) || empty($data['title'])) {
        throw new Exception("Projekt-ID und Titel fehlen!");
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $type = $data['type'] ?? 'SYS';
    $title = $data['title'];
    $description = $data['description'] ?? '';
    $rationale = $data['rationale'] ?? '';
    $status = $data['status'] ?? 'open';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE requirements SET type=?, title=?, description=?, rationale=?, status=? WHERE id=? AND project_id=?");
        $stmt->execute([$type, $title, $description, $rationale, $status, $id, $project_id]);
    } else {
        // Key generieren (z.B. SYS-001)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND type = ?");
        $countStmt->execute([$project_id, $type]);
        $count = $countStmt->fetchColumn() + 1;
        $req_key = $type . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO requirements (project_id, req_key, type, title, description, rationale, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $req_key, $type, $title, $description, $rationale, $status]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>