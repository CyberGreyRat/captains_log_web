<?php
// api/delete_task.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['project_id'])) throw new Exception("ID fehlt.");

    // Löscht die Aufgabe und dank ON DELETE CASCADE in der DB ggf. auch Unteraufgaben
    $stmt = $pdo->prepare("DELETE FROM project_tasks WHERE id = ? AND project_id = ?");
    $stmt->execute([$data['id'], $data['project_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}