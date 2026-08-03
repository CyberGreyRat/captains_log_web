<?php
// dashboard/index.php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../config/db.php';

// Projekte des Nutzers laden
$stmt = $pdo->prepare("
    SELECT p.id, p.name 
    FROM projects p 
    JOIN project_members pm ON p.id = pm.project_id 
    WHERE pm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();
?>
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Captain's Log - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

    <div id="notificationArea" class="fixed right-4 top-4 z-[100] w-[min(92vw,520px)] space-y-3" aria-live="assertive"
        aria-atomic="true"></div>

    <!-- HEADER -->
    <header class="border-t-4 border-amber-400 bg-[#0d3158] text-white shrink-0">
        <div class="mx-auto flex h-24 max-w-screen-2xl items-center justify-between px-6">
            <div class="flex items-center gap-4">
                <img src="css/logo.png" class="h-16 w-auto" alt="Captain's Log Logo">
                <div>
                    <p class="text-xs uppercase tracking-[.2em] text-blue-200">Requirements · Risk · Verification</p>
                    <h1 class="text-2xl font-bold">Captain's Log</h1>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span id="session" class="text-sm">User: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>

                <select id="projectSelect" class="text-slate-800 rounded px-2 py-1 font-bold outline-none">
                    <option value="">-- Projekt wählen --</option>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')"
                    class="rounded border border-white px-3 py-1 text-sm hover:bg-white hover:text-[#0d3158] transition">Neues
                    Projekt</button>
                <a href="?logout=1" class="text-sm text-red-300 hover:text-red-100 transition ml-2">Logout</a>
            </div>
        </div>
    </header>

    <!-- NAVIGATION (Die neuen sauberen Reiter) -->
    <nav class="border-b bg-white shadow-sm shrink-0">
        <div class="mx-auto flex max-w-screen-2xl px-6">
            <button class="tab active border-b-2 px-4 py-3 font-medium transition-colors"
                data-panel="requirements">Anforderungen</button>
            <button
                class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors"
                data-panel="stakeholders">Stakeholder</button>
            <button
                class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors"
                data-panel="usecases">Use Cases</button>
            <button
                class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors"
                data-panel="userstories">User Stories</button>
            <button
                class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors"
                data-panel="history">Historie</button>
            <button
                class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors"
                data-panel="dashboard">Dashboard</button>
        </div>
    </nav>

    <!-- MAIN CONTENT (Lädt nur noch die Bausteine!) -->
    <main class="mx-auto max-w-screen-2xl w-full p-6 flex-1 overflow-hidden">

        <section id="requirements" class="panel show h-full">
            <?php include 'views/requirements.php'; ?>
        </section>

        <section id="stakeholders" class="panel h-full">
            <?php include 'views/stakeholders.php'; ?>
        </section>

        <section id="usecases" class="panel h-full">
            <?php include 'views/usecases.php'; ?>
        </section>

        <section id="userstories" class="panel h-full">
            <?php include 'views/userstories.php'; ?>
        </section>

        <section id="history" class="panel h-full">
            <?php include 'views/history.php'; ?>
        </section>
        <section id="dashboard" class="panel h-full">
            <?php include 'views/dashboard.php'; ?>
        </section>

    </main>

    <!-- Globales Projekt Modal -->
    <div id="newProjectModal" class="fixed inset-0 hidden items-center justify-center bg-slate-900/60 p-4 z-50">
        <form method="POST" action="../api/web_create_project.php"
            class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-bold text-blue-900">Neues Projekt erstellen</h2>
            <label class="block text-sm font-semibold text-slate-700">Projektname
                <input name="name" required
                    class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Beschreibung
                <textarea name="description" rows="3"
                    class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"></textarea>
            </label>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
                <button type="submit"
                    class="rounded bg-blue-900 px-4 py-2 text-white hover:bg-blue-800 font-medium transition shadow">Projekt
                    anlegen</button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script type="module" src="js/app.js"></script>
    <script>
        document.querySelectorAll('.tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active', 'text-blue-900');
                    t.classList.add('border-transparent', 'text-slate-600');
                });
                e.target.classList.add('active', 'text-blue-900');
                e.target.classList.remove('border-transparent', 'text-slate-600');

                document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
                document.getElementById(e.target.dataset.panel).classList.add('show');
            });
        });
    </script>
</body>

</html>