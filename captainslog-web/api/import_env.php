<?php
// api/import_env.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $source_id = $data['source_project_id'] ?? null;
    $target_id = $data['target_project_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? 1;

    if (!$source_id || !$target_id) throw new Exception("Projekt-IDs fehlen.");

    // Alle ENV-Einträge aus dem alten Projekt holen
    $stmt = $pdo->prepare("SELECT * FROM requirements WHERE project_id = ? AND type = 'ENV'");
    $stmt->execute([$source_id]);
    $envs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($envs) === 0) {
        echo json_encode(['success' => false, 'error' => 'Keine Umweltaspekte im gewählten Projekt gefunden.']);
        exit;
    }

    // Höchste laufende Nummer im Ziel-Projekt finden
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM requirements WHERE project_id = ? AND type = 'ENV'");
    $cStmt->execute([$target_id]);
    $count = (int)$cStmt->fetchColumn();

    $insStmt = $pdo->prepare("INSERT INTO requirements (project_id, req_key, type, title, description, rationale, status, review_status, attributes) VALUES (?, ?, 'ENV', ?, ?, ?, 'open', 'Neu', ?)");
    
    foreach ($envs as $env) {
        $count++;
        $new_key = 'ENV-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        
        // Status wird beim Kopieren immer auf "Neu" zurückgesetzt
        $insStmt->execute([
            $target_id, $new_key, $env['title'], $env['description'], $env['rationale'], $env['attributes']
        ]);
    }

    echo json_encode(['success' => true, 'imported' => count($envs)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}