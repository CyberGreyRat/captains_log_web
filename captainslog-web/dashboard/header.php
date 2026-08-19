<?php
// Sicherstellen, dass die Session läuft, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($projects)) {
    $projects = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();
}

// Hostname und Rolle aus der Session laden (mit Fallback)
$hostname = $_SESSION['hostname'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'viewer';
$username = $_SESSION['username'] ?? 'Gast';
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Captain's Log - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="captains-theme.css">
    <link rel="shortcut icon" href="css/favicon.ico" />
    <style>
        .panel {
            display: none;
        }

        .panel.show {
            display: block;
        }

        .tab.active {
            color: #1e3a8a;
            border-color: #1e3a8a;
            background: #eff6ff;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 flex flex-col h-screen overflow-hidden">

    <!-- HEADER -->
    <header class="border-t-4 border-amber-400 bg-[#0d3158] text-white shrink-0">
        <div class="mx-auto flex h-24 max-w-screen-2xl items-center justify-between px-6">
            <div class="flex items-center gap-4">
                <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition cursor-pointer">
                    <img src="css/logo.png" class="h-16 w-auto" alt="Captain's Log Logo">
                    <div>
                        <p class="text-xs uppercase tracking-[.2em] text-blue-200">Requirements · Risk · Verification
                        </p>
                        <h1 class="text-2xl font-bold">Captain's Log</h1>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <!-- Navigation Links (Profil & Admin) -->
                <nav class="flex items-center gap-3 mr-2 text-sm border-r border-blue-400/40 pr-4">
                    <a href="profile.php" class="hover:text-amber-300 transition">Profil</a>
                    <?php if ($role === 'admin' || $role === 'editor'): ?>
                        <a href="admin_users.php" class="text-amber-300 font-semibold hover:underline">Nutzerverwaltung</a>
                    <?php endif; ?>
                    <a href="index.php" class="hover:text-amber-300 transition">Home</a>
                </nav>

                <div class="user-info text-sm text-blue-100">
                    Eingeloggt als: <strong class="text-white"><?php echo htmlspecialchars($username); ?></strong>
                    @ <?php echo htmlspecialchars($hostname); ?>
                </div>

                <select id="projectSelect" class="text-slate-800 px-2 py-1 font-bold outline-none ">
                    <option value="">-- Projekt wählen --</option>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')"
                    class="border border-white px-3 py-1 text-sm hover:bg-white hover:text-[#0d3158] transition ">Neues
                    Projekt</button>

                <a href="logout.php" class="text-sm text-red-300 hover:text-red-100 transition ml-2">Logout</a>
            </div>
        </div>
    </header>

    <!-- Eckiger Ladebalken -->
    <style>
        @keyframes loading-slide {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(300%);
            }
        }

        .animate-loading-bar {
            animation: loading-slide 1.5s infinite linear;
        }
    </style>

    <div id="loadingOverlay"
        class="fixed inset-0 bg-slate-900/60 z-[9999] hidden flex items-center justify-center backdrop-blur-sm">
        <div class="w-96 bg-white border-4 border-[#0d3158] p-8 shadow-2xl flex flex-col">
            <h2 class="text-[#0d3158] font-bold text-lg uppercase tracking-[.2em] mb-4 text-center">Lade Projekt...</h2>
            <!-- Der eigentliche Ladebalken -->
            <div class="w-full h-6 bg-slate-200 overflow-hidden relative border border-slate-300">
                <div class="absolute top-0 left-0 h-full bg-amber-400 w-1/3 animate-loading-bar"></div>
            </div>
        </div>
    </div>