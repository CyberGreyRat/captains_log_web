<?php
// admin_users.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';
require_once 'header.php'; // Der Header wird jetzt korrekt geladen!

$currentRole = $_SESSION['role'] ?? 'viewer';
$currentUserId = $_SESSION['user_id'] ?? 0;

// Check: Nur Admins und Editoren (Projektleiter) dürfen verwalten
if ($currentRole !== 'admin' && $currentRole !== 'editor') {
    die("<main class='p-8 max-w-7xl mx-auto'><div class='bg-red-100 text-red-800 p-8 font-bold text-xl rounded shadow'>Zugriff verweigert: Nur für Administratoren und Projektleiter.</div></main>");
}

$msg = '';
$error = '';

// 1. Neuen User anlegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_user = trim($_POST['username']);
    $new_pass = $_POST['password'];
    $role = $_POST['role'];
    
    if ($currentRole !== 'admin' && $role === 'admin') {
        $error = "Dir fehlen die Rechte, um einen Administrator-Account anzulegen.";
    } else {
        $api_token = bin2hex(random_bytes(16)); 
        $pass_hash = password_hash($new_pass, PASSWORD_DEFAULT); 
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, api_token) VALUES (?, ?, ?, ?)");
            $stmt->execute([$new_user, $pass_hash, $role, $api_token]);
            $msg = "Nutzer '$new_user' erfolgreich angelegt!";
        } catch (\PDOException $e) {
            $error = "Fehler beim Anlegen: Möglicherweise existiert der Name schon.";
        }
    }
}

// 2. Projekt zuweisen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_project'])) {
    $target_user = $_POST['assign_user_id'];
    $target_project = $_POST['assign_project_id'];
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
        $stmt->execute([$target_project, $target_user]);
        $msg = "Nutzer wurde erfolgreich dem Projekt zugewiesen!";
    } catch (\PDOException $e) {
        $error = "Fehler bei der Zuweisung.";
    }
}

// 3. Aus Projekt entfernen (NEU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_project'])) {
    $target_user = $_POST['remove_user_id'];
    $target_project = $_POST['remove_project_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$target_project, $target_user]);
        $msg = "Nutzer wurde aus dem Projekt entfernt!";
    } catch (\PDOException $e) {
        $error = "Fehler beim Entfernen.";
    }
}

// 4. Passwort zurücksetzen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $target_id = $_POST['target_user_id'];
    $new_hash = password_hash('000000', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt->execute([$new_hash, $target_id])) {
        $msg = "Passwort für ID $target_id erfolgreich auf '000000' zurückgesetzt!";
    } else {
        $error = "Fehler beim Zurücksetzen.";
    }
}

// 5. Nutzer löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = $_POST['target_user_id'];
    $target_role = $_POST['target_user_role'];
    if ($target_id == $currentUserId) {
        $error = "Du kannst deinen eigenen Account nicht löschen!";
    } elseif ($currentRole !== 'admin' && $target_role === 'admin') {
        $error = "Dir fehlen die Rechte, um einen Administrator zu löschen.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$target_id])) {
            $msg = "Nutzer erfolgreich gelöscht!";
        } else {
            $error = "Fehler beim Löschen.";
        }
    }
}

// Daten laden
$users = $pdo->query("SELECT id, username, role, api_token, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Projekt-Zuweisungen laden (für die Live-Anzeige)
$pm_stmt = $pdo->query("
    SELECT pm.project_id, u.id as user_id, u.username, u.role 
    FROM project_members pm 
    JOIN users u ON pm.user_id = u.id
");
$projectMembers = [];
foreach($pm_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $projectMembers[$row['project_id']][] = $row;
}
?>

<main class="p-8 max-w-7xl mx-auto overflow-auto h-full w-full">
    <h2 class="text-3xl font-bold mb-6 text-[#0d3158]">Team & Projektzuweisung</h2>

    <?php if (!empty($msg)) echo "<div class='bg-green-100 text-green-800 border border-green-300 p-4 mb-6 shadow-sm'>$msg</div>"; ?>
    <?php if (!empty($error)) echo "<div class='bg-red-100 text-red-800 border border-red-300 p-4 mb-6 shadow-sm'>$error</div>"; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        
        <!-- Bereich: Neuen Nutzer anlegen -->
        <div class="bg-white shadow-sm p-6 border border-slate-200">
            <h5 class="text-lg font-bold mb-4 text-slate-800">Neuen Nutzer anlegen</h5>
            <form method="POST" class="flex flex-col gap-3">
                <input type="text" name="username" class="border border-slate-300 px-4 py-2 focus:outline-none focus:border-blue-500" placeholder="Benutzername" required>
                <input type="password" name="password" class="border border-slate-300 px-4 py-2 focus:outline-none focus:border-blue-500" placeholder="Initiales Passwort" required>
                <select name="role" class="border border-slate-300 px-4 py-2 bg-white focus:outline-none focus:border-blue-500">
                    <option value="viewer">Viewer (Nur lesen)</option>
                    <option value="editor">Editor (Projektleiter / Schreiben)</option>
                    <?php if ($currentRole === 'admin'): ?>
                        <option value="admin">Admin (Systemverwaltung)</option>
                    <?php endif; ?>
                </select>
                <button type="submit" name="create_user" class="bg-[#0d3158] text-white px-4 py-2 hover:bg-blue-800 transition font-bold mt-2">Nutzer anlegen</button>
            </form>
        </div>

        <!-- Bereich: Nutzer zu Projekt zuweisen -->
        <div class="bg-white shadow-sm p-6 border border-slate-200">
            <h5 class="text-lg font-bold mb-4 text-slate-800">Projektzugang verwalten</h5>
            <form method="POST" class="flex flex-col gap-3">
                <select id="project_selector" name="assign_project_id" required class="border border-slate-300 px-4 py-2 bg-white focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="">-- Projekt auswählen --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <!-- HIER WERDEN DIE BEREITS ZUGEWIESENEN NUTZER LIVE ANGEZEIGT -->
                <div id="current_project_members" class="bg-slate-50 border border-slate-200 p-3 min-h-[80px]">
                    <p class="text-sm text-slate-400 italic text-center mt-2">Bitte wähle oben ein Projekt, um das Team zu sehen.</p>
                </div>

                <div class="flex gap-2 mt-2">
                    <select name="assign_user_id" required class="border border-slate-300 px-4 py-2 bg-white focus:outline-none focus:border-blue-500 flex-1 cursor-pointer">
                        <option value="">-- Nutzer hinzufügen --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_project" class="bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 transition font-bold shrink-0 shadow-sm">Hinzufügen</button>
                </div>
            </form>
        </div>

    </div>

    <!-- Nutzerliste -->
    <div class="bg-white shadow-sm overflow-hidden border border-slate-200">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-slate-100 border-b border-slate-200">
                <tr>
                    <th class="p-4 font-semibold text-slate-700 w-16">ID</th>
                    <th class="p-4 font-semibold text-slate-700">Name</th>
                    <th class="p-4 font-semibold text-slate-700">Rolle</th>
                    <th class="p-4 font-semibold text-slate-700">API-Token</th>
                    <th class="p-4 font-semibold text-slate-700 text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                    <td class="p-4 text-slate-500"><?= $u['id'] ?></td>
                    <td class="p-4 font-bold text-slate-800"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="p-4">
                        <?php
                            $roleColor = 'bg-slate-100 text-slate-600';
                            if($u['role'] === 'admin') $roleColor = 'bg-red-100 text-red-800';
                            if($u['role'] === 'editor') $roleColor = 'bg-blue-100 text-blue-800';
                        ?>
                        <span class="<?= $roleColor ?> text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td class="p-4 font-mono text-xs text-slate-500">
                        <?= $u['api_token'] ? substr($u['api_token'], 0, 8) . '...' : 'NULL' ?>
                    </td>
                    <td class="p-4 text-right">
                        <form method="POST" class="inline-flex gap-2">
                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="target_user_role" value="<?= $u['role'] ?>">
                            <button type="submit" name="reset_password" onclick="return confirm('Passwort für <?= htmlspecialchars($u['username']) ?> auf 000000 setzen?');" class="text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 font-semibold transition rounded">PW Reset</button>
                            <?php if ($u['id'] != $currentUserId && ($currentRole === 'admin' || $u['role'] !== 'admin')): ?>
                                <button type="submit" name="delete_user" onclick="return confirm('Nutzer <?= htmlspecialchars($u['username']) ?> löschen?');" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 font-semibold transition rounded">Löschen</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Live-Update für Projektmitglieder
    const projectMembers = <?= json_encode($projectMembers) ?>;
    
    document.getElementById('project_selector').addEventListener('change', function() {
        const projectId = this.value;
        const container = document.getElementById('current_project_members');
        
        if (!projectId) {
            container.innerHTML = '<p class="text-sm text-slate-400 italic text-center mt-2">Bitte wähle oben ein Projekt, um das Team zu sehen.</p>';
            return;
        }

        if (!projectMembers[projectId] || projectMembers[projectId].length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 italic text-center mt-2">Noch niemand zugewiesen.</p>';
            return;
        }

        let html = '<ul class="space-y-1">';
        projectMembers[projectId].forEach(u => {
            let badgeClass = 'bg-slate-200 text-slate-700';
            if (u.role === 'admin') badgeClass = 'bg-red-200 text-red-900';
            if (u.role === 'editor') badgeClass = 'bg-blue-200 text-blue-900';

            html += `
                <li class="flex justify-between items-center bg-white border border-slate-200 px-3 py-1.5 shadow-sm text-sm">
                    <div>
                        <span class="font-bold text-slate-800 mr-2">${u.username}</span>
                        <span class="${badgeClass} text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">${u.role}</span>
                    </div>
                    <form method="POST" class="inline m-0 p-0" onsubmit="return confirm('${u.username} wirklich aus dem Projekt werfen?');">
                        <input type="hidden" name="remove_project_id" value="${projectId}">
                        <input type="hidden" name="remove_user_id" value="${u.user_id}">
                        <button type="submit" name="remove_from_project" class="text-slate-300 hover:text-red-600 font-bold px-2 py-0.5 transition" title="Aus Projekt entfernen">✕</button>
                    </form>
                </li>
            `;
        });
        html += '</ul>';
        container.innerHTML = html;
    });
</script>

</body>
</html>