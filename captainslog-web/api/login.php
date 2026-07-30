


<?php
// api/login.php
header('Content-Type: application/json');
require '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username und Passwort fehlen.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $token = bin2hex(random_bytes(32));
    $updateStmt = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
    $updateStmt->execute([$token, $user['id']]);
    
    echo json_encode([
        'success' => true, 
        'token' => $token,
        'message' => 'Login erfolgreich.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Zugangsdaten.']);
}
?>