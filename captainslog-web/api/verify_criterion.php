<?php // api/verify_criterion.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || empty($data['req_id']) || !isset($data['criterion_idx'])) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter oder nicht eingeloggt.']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $username = $_SESSION['username'];
    
    // Aktuelle Attribute laden
    $stmt = $pdo->prepare("SELECT attributes FROM requirements WHERE id = ?");
    $stmt->execute([$data['req_id']]);
    $row = $stmt->fetch();
    
    if(!$row) throw new Exception("Anforderung nicht gefunden.");

    $attr = $row['attributes'] ? json_decode($row['attributes'], true) : [];
    if(!isset($attr['criteria_states'])) {
        $attr['criteria_states'] = [];
    }

    // Status setzen
    $attr['criteria_states'][$data['criterion_idx']] = [
        'checked' => true,
        'by' => $username,
        'date' => date('d.m.Y H:i'),
        'note' => htmlspecialchars($data['note'])
    ];

    $json_attr = json_encode($attr);

    // In DB speichern
    $upd = $pdo->prepare("UPDATE requirements SET attributes = ? WHERE id = ?");
    $upd->execute([$json_attr, $data['req_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>