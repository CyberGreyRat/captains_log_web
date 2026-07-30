<?php
session_start();
header('Content-Type: application/json');
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['history' => []]);
    exit;
}

$project_id = $_GET['project_id'] ?? '';

if (!$project_id) {
    echo json_encode(['history' => []]);
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

    echo json_encode(['history' => $history]);
} catch (PDOException $e) {
    echo json_encode(['history' => [], 'error' => $e->getMessage()]);
}
?>