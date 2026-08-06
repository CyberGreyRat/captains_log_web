<?php
// api/push_sbom.php
require_once '../config/db.php';

header('Content-Type: application/json');

// 1. Header auslesen
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$projectId = $headers['X-Project-Id'] ?? null;

// 2. Token prüfen
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["error" => "Kein API-Token angegeben."]);
    exit;
}
$apiToken = $matches[1];

// 3. User validieren
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$apiToken]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(403);
    echo json_encode(["error" => "Ungültiger API-Token."]);
    exit;
}

if (!$projectId) {
    http_response_code(400);
    echo json_encode(["error" => "X-Project-Id Header fehlt."]);
    exit;
}

// 4. Rohes JSON (Body) auslesen
$sbomData = file_get_contents('php://input');
if (empty($sbomData) || !json_decode($sbomData)) {
    http_response_code(400);
    echo json_encode(["error" => "Ungültiges oder leeres JSON im Body."]);
    exit;
}

// 5. In DB speichern
$stmt = $pdo->prepare("INSERT INTO project_sboms (project_id, sbom_data) VALUES (?, ?)");
if ($stmt->execute([$projectId, $sbomData])) {
    echo json_encode(["success" => true, "message" => "SBOM erfolgreich gespeichert."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Datenbankfehler beim Speichern."]);
}
?>