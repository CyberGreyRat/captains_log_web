<?php
session_start();
session_unset();     // Alle Session-Variablen löschen
session_destroy();   // Die Session komplett zerstören

// Zurück zur Login-Seite leiten
header("Location: login.php");
exit;