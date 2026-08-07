<?php
// api/get_iso_templates.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    // Holt alle Templates sortiert nach Kategorie und Phase
    $stmt = $pdo->query("SELECT * FROM iso14001_templates ORDER BY category ASC, phase ASC, title ASC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'templates' => $templates]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}