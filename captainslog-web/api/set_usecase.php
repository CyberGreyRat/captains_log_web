<?php
// api/set_usecase.php
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
    $primary_actor = $data['primary_actor'] ?? '';
    $preconditions = $data['preconditions'] ?? '';
    $main_scenario = $data['main_scenario'] ?? '';
    $alt_scenario = $data['alt_scenario'] ?? '';

    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE use_cases 
            SET title=?, primary_actor=?, preconditions=?, main_scenario=?, alt_scenario=? 
            WHERE id=? AND project_id=?
        ");
        $stmt->execute([$title, $primary_actor, $preconditions, $main_scenario, $alt_scenario, $id, $project_id]);
        $message = "Use Case erfolgreich aktualisiert.";
    } else {
        // INSERT - Automatischen Key generieren (z.B. UC-001)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM use_cases WHERE project_id = ?");
        $countStmt->execute([$project_id]);
        $count = $countStmt->fetchColumn() + 1;
        $uc_key = 'UC-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO use_cases (project_id, uc_key, title, primary_actor, preconditions, main_scenario, alt_scenario) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$project_id, $uc_key, $title, $primary_actor, $preconditions, $main_scenario, $alt_scenario]);
        $id = $pdo->lastInsertId();
        $message = "Use Case erfolgreich angelegt.";
    }

    echo json_encode(['success' => true, 'id' => $id, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>