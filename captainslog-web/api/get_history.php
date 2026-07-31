<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode(['success' => false, 'history' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT h.*, u.username as modified_by_user 
        FROM requirement_history h 
        LEFT JOIN users u ON h.modified_by = u.id 
        WHERE h.project_id = ? 
        ORDER BY h.modified_at DESC
    ");
    $stmt->execute([$project_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'history' => [], 'error' => $e->getMessage()]);
}
?>