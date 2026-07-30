<?php
session_start();
header('Content-Type: application/json');
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Holt nur Projekte, bei denen der eingeloggte User Mitglied ist
$stmt = $pdo->prepare("
    SELECT p.id, p.name 
    FROM projects p 
    JOIN project_members pm ON p.id = pm.project_id 
    WHERE pm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['projects' => $stmt->fetchAll()]);
?>