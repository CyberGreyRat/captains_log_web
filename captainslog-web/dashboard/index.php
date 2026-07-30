<?php
// dashboard/index.php
session_start();

// Simpler Logout-Mechanismus
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

// Projekte des Nutzers laden (nur Projekte, wo er Mitglied ist!)
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
    <!-- Pfade korrigiert, da wir schon im dashboard/ Ordner sind -->
    <link rel="shortcut icon" href="css/favicon.ico" />
    <style>
        .panel { display: none }
        .panel.show { display: block }
        .tab.active { color: #1e3a8a; border-color: #1e3a8a; background: #eff6ff }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">
    <div id="notificationArea" class="fixed right-4 top-4 z-[100] w-[min(92vw,520px)] space-y-3" aria-live="assertive" aria-atomic="true"></div>
    
    <header class="border-t-4 border-amber-400 bg-[#0d3158] text-white">
        <div class="mx-auto flex h-24 max-w-screen-2xl items-center justify-between px-6">
            <div class="flex items-center gap-4">
                <img src="css/logo.png" class="h-16 w-auto" alt="Captain's Log Logo">
                <div>
                    <p class="text-xs uppercase tracking-[.2em] text-blue-200">Requirements · Risk · Verification</p>
                    <h1 class="text-2xl font-bold">Captain's Log</h1>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span id="session" class="text-sm">Angemeldet als: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
                
                <!-- Projekt Auswahl -->
                <select id="projectSelect" class="text-slate-800 rounded px-2 py-1">
                    <option value="">-- Projekt wählen --</option>
                    <?php foreach($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')" class="rounded border border-white px-3 py-1 text-sm hover:bg-white hover:text-[#0d3158] transition">Neues Projekt</button>
                <button id="new" class="rounded bg-white px-4 py-2 font-semibold text-blue-900">Neue Anforderung</button>
                <a href="?logout=1" class="text-sm text-red-300 hover:text-red-100">Logout</a>
            </div>
        </div>
    </header>

    <nav class="border-b bg-white">
        <div class="mx-auto flex max-w-screen-2xl px-6">
            <button class="tab active border-b-2 px-4 py-3" data-panel="requirements">Anforderungen</button>
            <button class="tab border-b-2 border-transparent px-4 py-3" data-panel="workflow">Code & Tests</button>
            <button class="tab border-b-2 border-transparent px-4 py-3" data-panel="documents">Dokumente</button>
            <button class="tab border-b-2 border-transparent px-4 py-3" data-panel="history">Historie</button>
        </div>
    </nav>

    <main class="mx-auto max-w-screen-2xl p-6">
        <section id="requirements" class="panel show">
            <div class="grid gap-5 lg:grid-cols-[350px_1fr]">
                <aside class="rounded border bg-white shadow h-[calc(100vh-250px)] overflow-y-auto">
                    <div id="items" class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>
                </aside>
                <article id="detail" class="rounded border bg-white p-6 shadow h-[calc(100vh-250px)] overflow-y-auto">
                    Anforderung auswählen
                </article>
            </div>
        </section>
    </main>

    <!-- Modal für Neues Projekt (mit korrigiertem Pfad!) -->
    <div id="newProjectModal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/50 p-4 z-50">
        <form method="POST" action="../api/web_create_project.php" class="w-full max-w-md space-y-3 rounded bg-white p-6">
            <h2 class="text-xl font-bold text-blue-900">Neues Projekt erstellen</h2>
            <label class="block text-sm font-semibold">Projektname
                <input name="name" required class="mt-1 w-full rounded border p-2">
            </label>
            <label class="block text-sm font-semibold">Beschreibung
                <textarea name="description" rows="3" class="mt-1 w-full rounded border p-2"></textarea>
            </label>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')" class="rounded border px-4 py-2 hover:bg-slate-50">Abbrechen</button>
                <button type="submit" class="rounded bg-blue-900 px-4 py-2 text-white hover:bg-blue-800">Projekt anlegen</button>
            </div>
        </form>
    </div>

    <!-- Modal für Neue Anforderung (Verknüpft mit deiner app.js) -->
    <div id="reqModal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/50 p-4 z-50">
        <form id="reqForm" class="w-full max-w-2xl max-h-[90vh] overflow-y-auto space-y-4 rounded bg-white p-6 shadow-xl">
            <h2 id="reqHeading" class="text-xl font-bold text-blue-900">Neue Anforderung</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <label class="block text-sm font-semibold">Typ
                    <select id="type" class="mt-1 w-full rounded border p-2 font-normal">
                        <option value="USR">User Requirement (USR)</option>
                        <option value="SYS">System Requirement (SYS)</option>
                        <option value="RISK">Risk (RISK)</option>
                        <option value="SRS">Software Requirement (SRS)</option>
                    </select>
                </label>
                <label class="block text-sm font-semibold">Titel
                    <input id="title" required class="mt-1 w-full rounded border p-2 font-normal">
                </label>
            </div>

            <label class="block text-sm font-semibold">Beschreibung
                <textarea id="text" required rows="3" class="mt-1 w-full rounded border p-2 font-normal"></textarea>
            </label>

            <label class="block text-sm font-semibold">Begründung (Rationale)
                <textarea id="rationale" rows="2" class="mt-1 w-full rounded border p-2 font-normal"></textarea>
            </label>

            <!-- Dynamische Attribute (wird per JS gefüllt) -->
            <div id="dynamicAttributes" class="hidden rounded bg-slate-50 p-4 border border-slate-200">
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Spezifische Attribute</h3>
                <div id="attributeFields" class="grid gap-4 md:grid-cols-2"></div>
            </div>

            <!-- Traceability (Verlinkungen) -->
            <div class="grid grid-cols-2 gap-4 border-t pt-4 mt-2">
                <label class="block text-sm font-semibold">Parents (Erfüllt)
                    <select id="parents" multiple class="mt-1 w-full rounded border p-2 font-normal text-sm" size="3"></select>
                </label>
                <label class="block text-sm font-semibold">Children (Wird erfüllt durch)
                    <select id="children" multiple class="mt-1 w-full rounded border p-2 font-normal text-sm" size="3"></select>
                </label>
            </div>

            <div class="flex justify-end gap-2 mt-4 border-t pt-4">
                <button type="button" id="cancelReq" class="rounded border px-4 py-2 hover:bg-slate-50 transition">Abbrechen</button>
                <button type="submit" class="rounded bg-blue-900 px-4 py-2 text-white hover:bg-blue-800 transition">Speichern</button>
            </div>
        </form>
    </div>

    <!-- Dein JavaScript lädt die Logik -->
    <script type="module" src="js/app.js"></script>
</body>
</html>