<?php
// config/db.php

$host = '127.0.0.1';
$db   = 'captainslog_db';
$user = 'root'; // Standard XAMPP User
$pass = '';     // Standard XAMPP Passwort

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Bei der API sollten wir JSON-Fehler werfen, beim Dashboard HTML
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}
?>