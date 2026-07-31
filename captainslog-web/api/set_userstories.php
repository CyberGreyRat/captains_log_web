<?php
// api/set_userstories.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';

header('Content-Type: application/json');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['project_id']) || empty($data['title'])) {
        throw new Exception("Projekt-ID und Titel sind Pflichtfelder!");
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $title = $data['title'];
    $us_role = $data['us_role'] ?? '';
    $us_action = $data['us_action'] ?? '';
    $us_benefit = $data['us_benefit'] ?? '';
    $acceptance_criteria = $data['acceptance_criteria'] ?? '';
    $story_points = $data['story_points'] ?? null;
    if ($story_points === '') $story_points = null; // Fix für leere Zahlenfelder

    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE user_stories 
            SET title=?, us_role=?, us_action=?, us_benefit=?, acceptance_criteria=?, story_points=? 
            WHERE id=? AND project_id=?
        ");
        $stmt->execute([$title, $us_role, $us_action, $us_benefit, $acceptance_criteria, $story_points, $id, $project_id]);
        $message = "User Story erfolgreich aktualisiert.";
    } else {
        // INSERT - Automatischen Key generieren (z.B. US-001)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_stories WHERE project_id = ?");
        $countStmt->execute([$project_id]);
        $count = $countStmt->fetchColumn() + 1;
        $us_key = 'US-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO user_stories (project_id, us_key, title, us_role, us_action, us_benefit, acceptance_criteria, story_points) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$project_id, $us_key, $title, $us_role, $us_action, $us_benefit, $acceptance_criteria, $story_points]);
        $id = $pdo->lastInsertId();
        $message = "User Story erfolgreich angelegt.";
    }

    echo json_encode(['success' => true, 'id' => $id, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>