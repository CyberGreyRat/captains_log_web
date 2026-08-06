<?php
// profile.php
session_start();
require_once '../config/db.php';

// Sicherstellen, dass der Nutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($old_pass, $user['password_hash'])) {
        $error = "Das alte Passwort ist falsch.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Die neuen Passwörter stimmen nicht überein.";
    } elseif (strlen($new_pass) < 6) { 
        $error = "Das neue Passwort muss mindestens 6 Zeichen lang sein.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($updateStmt->execute([$new_hash, $user_id])) {
            $msg = "Dein Passwort wurde erfolgreich geändert!";
        } else {
            $error = "Fehler beim Speichern in der Datenbank.";
        }
    }
}

require_once 'header.php';
?>

<main class="p-8 h-full w-full overflow-auto bg-slate-50">
    <div class="max-w-xl mx-auto bg-white shadow-lg overflow-hidden mt-10 border border-slate-200">
        <div class="bg-[#0d3158] px-6 py-4">
            <h5 class="text-white text-xl font-bold">Mein Profil</h5>
        </div>
        <div class="p-8">
            <?php if ($msg): ?>
                <div class="bg-green-100 text-green-800 border border-green-300 p-3  mb-6 shadow-sm"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-800 border border-red-300 p-3  mb-6 shadow-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Benutzername</label>
                    <input type="text" class="w-full border border-slate-300  px-4 py-2 bg-slate-100 cursor-not-allowed text-slate-500" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled>
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Meine Rolle</label>
                    <input type="text" class="w-full border border-slate-300  px-4 py-2 bg-slate-100 cursor-not-allowed text-slate-500 uppercase" value="<?= htmlspecialchars($_SESSION['role']) ?>" disabled>
                </div>
                
                <hr class="mb-6 border-slate-200">
                <h6 class="text-xl font-bold mb-6 text-slate-800">Passwort ändern</h6>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Altes Passwort</label>
                    <input type="password" name="old_password" class="w-full border border-slate-300  px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#0d3158]" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Neues Passwort</label>
                    <input type="password" name="new_password" class="w-full border border-slate-300  px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#0d3158]" required>
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Neues Passwort bestätigen</label>
                    <input type="password" name="confirm_password" class="w-full border border-slate-300  px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#0d3158]" required>
                </div>
                <button type="submit" name="change_password" class="w-full bg-[#0d3158] text-white font-bold py-3 px-4  hover:bg-blue-800 transition shadow-md">Passwort speichern</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>