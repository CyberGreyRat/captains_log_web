<?php
// api/get_stakeholders.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';

header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;

if (!$project_id) {
    echo json_encode(['success' => false, 'error' => 'Keine project_id übergeben.']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Einfach alles aus der einen Tabelle holen
    $stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE project_id = ? ORDER BY name ASC");
    $stmt->execute([$project_id]);
    
    $stakeholders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'stakeholders' => $stakeholders]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>