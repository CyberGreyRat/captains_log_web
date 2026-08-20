<?php
// api/web_create_project.php
session_start();

// Pfad anpassen: Da wir im /api/ Ordner sind, müssen wir mit '../' eins hoch!
require '../config/db.php';
require_once __DIR__ . '/../config/audit_context.php';

// Ist der Nutzer überhaupt im Browser eingeloggt?
if (!isset($_SESSION['user_id'])) {
    die("Sitzung abgelaufen oder nicht autorisiert. Bitte neu einloggen.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulardaten aus der index.php abgreifen
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($name !== '') {
        // Eine zufällige, sichere Projekt-ID generieren (z.B. proj-5f4a8b...)
        // Diese ID nutzt der Entwickler später für "cap link"
        $project_id = 'proj-' . bin2hex(random_bytes(6));

        try {
    set_audit_context($pdo, 'web', basename($_SERVER['SCRIPT_NAME']));

            // Transaktion starten (entweder es klappt alles, oder nichts)
            $pdo->beginTransaction();

            // 1. Das Projekt in die DB eintragen
            $stmt = $pdo->prepare("INSERT INTO projects (id, name, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$project_id, $name, $description, $user_id]);

            // 2. Den Ersteller in die project_members Tabelle eintragen, 
            // damit er sofortigen Zugriff darauf hat
            $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmtMember->execute([$project_id, $user_id]);

            $pdo->commit();

            // Nach erfolgreichem Speichern leiten wir direkt zurück zum Dashboard
            header("Location: ../dashboard/index.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Datenbank-Fehler beim Erstellen des Projekts: " . $e->getMessage());
        }
    } else {
        die("Der Projektname darf nicht leer sein.");
    }
} else {
    // Falls jemand die Datei direkt im Browser ohne POST aufruft
    header("Location: ../index.php");
    exit;
}
?>
