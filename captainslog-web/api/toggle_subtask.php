<?php
// api/toggle_subtask.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $completed = !empty($data['completed']) ? 100 : 0; // 100% = Abgehakt

    if (!$id) throw new Exception("ID fehlt.");

    $stmt = $pdo->prepare("UPDATE project_tasks SET progress_pct = ? WHERE id = ?");
    $stmt->execute([$completed, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}