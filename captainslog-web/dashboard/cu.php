<?php
require '../config/db.php';

$pass_hash = password_hash('12345678', PASSWORD_DEFAULT);
$token1 = bin2hex(random_bytes(16));
$token2 = bin2hex(random_bytes(16));

try {
    $pdo->query("INSERT INTO users (username, password_hash, role, api_token) VALUES ('Admin', '$pass_hash', 'admin', '$token1')");
    $pdo->query("INSERT INTO users (username, password_hash, role, api_token) VALUES ('Luke Eckardt', '$pass_hash', 'editor', '$token2')");
    echo "Nutzer 'Admin' und 'Luke Eckardt' mit Passwort '12345678' erfolgreich angelegt!";
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
?>