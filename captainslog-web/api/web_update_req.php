<?php
session_start();
header('Content-Type: application/json');
require '../config/db.php';

// 1. Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

// 2. JSON-Daten aus dem Frontend auslesen
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Keine ID angegeben']);
    exit;
}

// 3. Hostnamen oder IP des Clients robust ermitteln (ideal für Ubuntu-Server im Intranet)
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (strpos($clientIp, ',') !== false) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}

$resolvedHost = @gethostbyaddr($clientIp);
if ($resolvedHost && $resolvedHost !== $clientIp) {
    $hostname = explode('.', $resolvedHost)[0];
} else {
    $hostname = $clientIp;
}

try {
    // 4. Aktuellen Stand der Anforderung vor dem Update auslesen (für das Historien-Archiv)
    $stmt = $pdo->prepare("SELECT * FROM requirements WHERE id = ?");
    $stmt->execute([$id]);
    $oldReq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldReq) {
        echo json_encode(['success' => false, 'error' => 'Anforderung nicht gefunden']);
        exit;
    }

    // 5. Alten Stand inklusive Benutzer und Hostname in die Historien-Tabelle schreiben
    $histStmt = $pdo->prepare("
        INSERT INTO requirement_history 
        (requirement_id, req_key, project_id, type, title, description, rationale, status, parents, children, modified_by, hostname) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $histStmt->execute([
        $oldReq['id'],
        $oldReq['req_key'],
        $oldReq['project_id'],
        $oldReq['type'],
        $oldReq['title'],
        $oldReq['description'],
        $oldReq['rationale'],
        $oldReq['status'],
        $oldReq['parents'],
        $oldReq['children'],
        $_SESSION['user_id'],
        $hostname
    ]);

    // 6. Bestehende Anforderung mit den neuen Daten aktualisieren (ID und req_key bleiben exakt erhalten)
    $updateStmt = $pdo->prepare("
        UPDATE requirements 
        SET title = ?, type = ?, description = ?, rationale = ?, status = ?, parents = ?, children = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([
        $data['title'] ?? $oldReq['title'],
        $data['type'] ?? $oldReq['type'],
        $data['text'] ?? $oldReq['description'] ?? '',
        $data['rationale'] ?? '',
        $data['status'] ?? 'open',
        json_encode($data['parents'] ?? []),
        json_encode($data['children'] ?? []),
        $id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>