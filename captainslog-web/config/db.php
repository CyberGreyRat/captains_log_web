<?php
// config/db.php

$host = '127.0.0.1';
$db = 'captainslog_db';
$user = 'root'; // Standard XAMPP User
$pass = '';     // Standard XAMPP Passwort

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Bei der API sollten wir JSON-Fehler werfen, beim Dashboard HTML
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

// Hilfsfunktion 1: Für die Benutzeroberfläche (um z.B. Speicher-Buttons auszublenden)
function can_edit()
{
    // Wenn es einen API-User gibt (über Token) oder einen Session-User, prüfen wir die Rolle
    global $user; // Falls du in APIs den $user global hast
    $role = $_SESSION['role'] ?? ($user['role'] ?? 'viewer');
    return $role !== 'viewer';
}

// Hilfsfunktion 2: Der "Türsteher" für Backend-Skripte & API (hartes Blockieren)
function require_edit_permission()
{
    if (!can_edit()) {
        // Prüfen, ob der Aufruf von der CLI/API kommt
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Push verweigert: Viewer-Rechte reichen nicht aus.']);
            exit;
        } else {
            // Normale Web-Antwort
            die("Zugriff verweigert: Dir fehlen die nötigen Schreibrechte.");
        }
    }
}
?>