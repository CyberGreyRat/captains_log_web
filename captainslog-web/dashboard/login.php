<?php
// login.php
session_start();
require '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];

        // In deinem Login-Skript, direkt nachdem das Passwort geprüft wurde:
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $hostname = gethostbyaddr($client_ip);

        // Falls die IP nicht aufgelöst werden kann, gibt gethostbyaddr() die IP zurück
        $_SESSION['hostname'] = ($hostname !== $client_ip) ? $hostname : 'LocalPC';
        header("Location: index.php");
        exit;
    } else {
        $error = "Falscher Benutzername oder Passwort.";
    }
}
?>
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Captain's Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="../dashboard/css/favicon.ico" />
</head>

<body class="bg-slate-50 flex items-center justify-center h-screen">
    <div class="bg-white p-8  shadow-md w-96 border-t-4 border-[#0d3158]">
        <div class="flex justify-center mb-6">
            <img src="../dashboard/css/logo.png" class="h-20 w-auto" alt="Logo">
        </div>
        <h2 class="text-2xl font-bold mb-6 text-center text-[#0d3158]">System Login</h2>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-900 border border-red-300 p-3  mb-4 text-sm"><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Benutzername</label>
                <input type="text" name="username" required
                    class="w-full border  p-2 focus:outline-none focus:ring-2 focus:ring-[#0d3158]">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-1">Passwort</label>
                <input type="password" name="password" required
                    class="w-full border  p-2 focus:outline-none focus:ring-2 focus:ring-[#0d3158]">
            </div>
            <button type="submit"
                class="w-full bg-[#0d3158] text-white font-bold py-2 px-4  hover:bg-blue-900 transition">Einloggen</button>
        </form>
    </div>
</body>

</html>