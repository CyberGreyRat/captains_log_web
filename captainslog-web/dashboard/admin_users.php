<?php
// admin_users.php
session_start();
require_once '../config/db.php';
require_once 'header.php'; 

// Harter Check: Nur Admins dürfen hier rein
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("<div class='p-8 text-red-600 font-bold text-xl'>Zugriff verweigert: Nur für Administratoren.</div>");
}

$msg = '';
$error = '';

// 1. Neuen User anlegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_user = trim($_POST['username']);
    $new_pass = $_POST['password'];
    $role = $_POST['role'];
    
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

// 2. Passwort zurücksetzen (auf 000000)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $target_id = $_POST['target_user_id'];
    $new_hash = password_hash('000000', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt->execute([$new_hash, $target_id])) {
        $msg = "Passwort für ID $target_id erfolgreich auf '000000' zurückgesetzt!";
    } else {
        $error = "Fehler beim Zurücksetzen des Passworts.";
    }
}

// 3. Nutzer löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = $_POST['target_user_id'];
    
    // Selbstlöschung verhindern
    if ($target_id == $_SESSION['user_id']) {
        $error = "Du kannst deinen eigenen Admin-Account nicht löschen!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$target_id])) {
            $msg = "Nutzer erfolgreich gelöscht!";
        } else {
            $error = "Fehler beim Löschen des Nutzers.";
        }
    }
}

// Alle Nutzer laden
$users = $pdo->query("SELECT id, username, role, api_token, created_at FROM users")->fetchAll();
?>

<main class="p-8 max-w-7xl mx-auto overflow-auto h-full w-full">
    <h2 class="text-3xl font-bold mb-6 text-[#0d3158]">Nutzerverwaltung</h2>

    <?php if (!empty($msg)) echo "<div class='bg-green-100 text-green-800 border border-green-300 p-4  mb-6 shadow-sm'>$msg</div>"; ?>
    <?php if (!empty($error)) echo "<div class='bg-red-100 text-red-800 border border-red-300 p-4  mb-6 shadow-sm'>$error</div>"; ?>

    <!-- Formular für neue Nutzer -->
    <div class="bg-white shadow -lg p-6 mb-8 border border-slate-200">
        <h5 class="text-lg font-bold mb-4 text-slate-800">Neuen Nutzer anlegen</h5>
        <form method="POST" class="flex gap-4">
            <input type="text" name="username" class="border border-slate-300  px-4 py-2 flex-1 focus:outline-none focus:border-blue-500" placeholder="Benutzername" required>
            <input type="password" name="password" class="border border-slate-300  px-4 py-2 flex-1 focus:outline-none focus:border-blue-500" placeholder="Initiales Passwort" required>
            <select name="role" class="border border-slate-300  px-4 py-2 flex-1 bg-white focus:outline-none focus:border-blue-500">
                <option value="viewer">Viewer (Nur lesen)</option>
                <option value="editor">Editor (Schreiben)</option>
                <option value="admin">Admin (Alles)</option>
            </select>
            <button type="submit" name="create_user" class="bg-[#0d3158] text-white px-6 py-2  hover:bg-blue-800 transition font-semibold shadow">Anlegen</button>
        </form>
    </div>

    <!-- Nutzerliste -->
    <div class="bg-white shadow -lg overflow-hidden border border-slate-200">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-100 border-b border-slate-200">
                <tr>
                    <th class="p-4 font-semibold text-slate-700">ID</th>
                    <th class="p-4 font-semibold text-slate-700">Name</th>
                    <th class="p-4 font-semibold text-slate-700">Rolle</th>
                    <th class="p-4 font-semibold text-slate-700">API-Token (CLI)</th>
                    <th class="p-4 font-semibold text-slate-700">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                    <td class="p-4 text-slate-600"><?= $u['id'] ?></td>
                    <td class="p-4 font-bold text-slate-800"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="p-4">
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 -full uppercase tracking-wide">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td class="p-4"><code class="bg-slate-100 text-slate-700 px-2 py-1  text-sm border border-slate-200"><?= htmlspecialchars($u['api_token']) ?></code></td>
                    <td class="p-4 flex gap-2">
                        <!-- Passwort Reset -->
                        <form method="POST" onsubmit="return confirm('Passwort für <?= htmlspecialchars($u['username']) ?> wirklich auf 000000 zurücksetzen?');" class="inline">
                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="reset_password" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-1 px-2  shadow-sm transition">Reset PW</button>
                        </form>
                        
                        <!-- Löschen (nicht für sich selbst) -->
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('Nutzer <?= htmlspecialchars($u['username']) ?> wirklich komplett löschen?');" class="inline">
                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete_user" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-2  shadow-sm transition">Löschen</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>