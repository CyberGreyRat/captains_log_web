<?php
// api/web_get_reqs.php
session_start();
header('Content-Type: application/json');
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$project_id = $_GET['project_id'] ?? '';

if (!$project_id) {
    // Wenn noch kein Projekt ausgewählt wurde, leeres Array zurückgeben
    echo json_encode(['requirements' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM requirements WHERE project_id = ? ORDER BY id DESC");
    $stmt->execute([$project_id]);
    $reqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['requirements' => $reqs]);
} catch (PDOException $e) {
    // Falls die Tabelle fehlt oder ein SQL-Fehler auftritt, senden wir das ans JS
    echo json_encode(['error' => 'DB Fehler: ' . $e->getMessage()]);
}
?>