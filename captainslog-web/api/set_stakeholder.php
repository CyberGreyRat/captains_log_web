<?php
// api/set_stakeholder.php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

header('Content-Type: application/json');

try {
    set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $data = json_decode(file_get_contents('php://input'), true);

    // Pflichtfeld prüfen
    if (empty($data['project_id']) || empty($data['name'])) {
        throw new Exception("Projekt-ID und Name sind Pflichtfelder!");
    }

    $id = $data['id'] ?? null;
    $project_id = $data['project_id'];
    $name = $data['name'];
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $role = $data['role'] ?? '';
    $position = $data['position'] ?? '';
    $expertise = $data['expertise'] ?? '';
    $availability = $data['availability'] ?? '';
    $influence = $data['influence'] ?? 'Low';
    $interest = $data['interest'] ?? 'Low';

    if ($id) {
        // UPDATE (Bestehenden Stakeholder ändern)
        $stmt = $pdo->prepare("
            UPDATE stakeholders 
            SET name=?, email=?, phone=?, role=?, position=?, expertise=?, availability=?, influence=?, interest=? 
            WHERE id=? AND project_id=?
        ");
        $stmt->execute([$name, $email, $phone, $role, $position, $expertise, $availability, $influence, $interest, $id, $project_id]);
        $message = "Stakeholder erfolgreich aktualisiert.";
    } else {
        // INSERT (Neuen Stakeholder anlegen)
        $stmt = $pdo->prepare("
            INSERT INTO stakeholders (project_id, name, email, phone, role, position, expertise, availability, influence, interest) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$project_id, $name, $email, $phone, $role, $position, $expertise, $availability, $influence, $interest]);
        $id = $pdo->lastInsertId();
        $message = "Stakeholder erfolgreich angelegt.";
    }

    echo json_encode(['success' => true, 'id' => $id, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
