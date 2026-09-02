<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($projects))
    $projects = $pdo->query("SELECT id,name FROM projects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$hostname = $_SESSION['hostname'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'viewer';
$username = $_SESSION['username'] ?? 'Gast';
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Captain's Log</title>
    <link rel="shortcut icon" href="css/favicon.ico">
    <link rel="stylesheet" href="css/captains-theme.css">
    <link rel="stylesheet" href="css/app-shell.css">
    <link rel="stylesheet" href="css/navigation.css">
    <link rel="stylesheet" href="css/document-studio.css">
    <link rel="stylesheet" href="css/acceptance-criteria-tool.css">
    <link rel="stylesheet" href="css/criterion-traceability.css">
</head>

<body class="cl-body">
    <header class="cl-header">
        <div class="cl-header-inner"><a href="index.php" class="cl-brand"><img src="css/logo.png"
                    alt="Captain's Log"><span><small>REQUIREMENTS · RISK · VERIFICATION</small><strong>Captain's
                        Log</strong></span></a>
            <div class="cl-project"><label for="projectSelect">Projekt</label><select id="projectSelect">
                    <option value="">-- Projekt wählen --</option><?php foreach ($projects as $proj): ?>
                        <option value="<?= htmlspecialchars($proj['id']) ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="cl-user"><span class="cl-login">Angemeldet:
                    <strong><?= htmlspecialchars($username) ?></strong><small><?= htmlspecialchars($hostname) ?></small></span>
                <details class="cl-user-menu">
                    <summary>Konto</summary>
                    <div><a href="profile.php">Profil</a><?php if ($role === 'admin'): ?><a
                                href="admin_users.php">Nutzerverwaltung</a><?php endif; ?><button type="button"
                            data-open-project>Neues Projekt</button><a class="danger-link" href="logout.php">Logout</a>
                    </div>
                </details>
            </div>
        </div>
    </header>
    <div id="loadingOverlay" class="cl-overlay hidden">
        <div class="cl-loading"><strong>Lade Projekt...</strong><i></i></div>
    </div>