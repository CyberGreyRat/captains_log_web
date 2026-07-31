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
    <link rel="shortcut icon" href="css/favicon.ico" />
    <style>
        .panel {
            display: none
        }

        .panel.show {
            display: block
        }

        .tab.active {
            color: #1e3a8a;
            border-color: #1e3a8a;
            background: #eff6ff
        }
        
        /* Spezifisches Styling für das Stakeholder-Board */
        #ecosystem-canvas {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">
    <div id="notificationArea" class="fixed right-4 top-4 z-[100] w-[min(92vw,520px)] space-y-3" aria-live="assertive"
        aria-atomic="true"></div>

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
                <span id="session" class="text-sm">Angemeldet als:
                    <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>

                <!-- Projekt Auswahl -->
                <select id="projectSelect" class="text-slate-800 rounded px-2 py-1">
                    <option value="">-- Projekt wählen --</option>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')"
                    class="rounded border border-white px-3 py-1 text-sm hover:bg-white hover:text-[#0d3158] transition">Neues
                    Projekt</button>
                <button id="new" class="rounded bg-white px-4 py-2 font-semibold text-blue-900 shadow hover:bg-blue-50 transition">Neue
                    Anforderung</button>
                <a href="?logout=1" class="text-sm text-red-300 hover:text-red-100 transition">Logout</a>
            </div>
        </div>
    </header>

    <nav class="border-b bg-white shadow-sm">
        <div class="mx-auto flex max-w-screen-2xl px-6">
            <button class="tab active border-b-2 px-4 py-3 font-medium transition-colors" data-panel="requirements">Anforderungen</button>
            <button class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors" data-panel="stakeholders">Stakeholder & Use Cases</button>
            <button class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors" data-panel="workflow">Code & Tests</button>
            <button class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors" data-panel="documents">Dokumente</button>
            <button class="tab border-b-2 border-transparent px-4 py-3 font-medium text-slate-600 hover:text-blue-900 transition-colors" data-panel="history">Historie</button>
        </div>
    </nav>

    <main class="mx-auto max-w-screen-2xl p-6">
        
        <!-- PANEL 1: Klassischer Baum & Details -->
        <section id="requirements" class="panel show">
            <div class="grid gap-5 lg:grid-cols-[350px_1fr]">
                <aside class="rounded-lg border bg-white shadow-sm h-[calc(100vh-250px)] overflow-y-auto">
                    <div id="items" class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>
                </aside>
                <article id="detail" class="rounded-lg border bg-white p-6 shadow-sm h-[calc(100vh-250px)] overflow-y-auto">
                    <div class="flex h-full items-center justify-center text-slate-400 italic">
                        Wähle einen Eintrag aus dem Baum aus, um Details zu sehen.
                    </div>
                </article>
            </div>
        </section>

        <!-- PANEL 2: Stakeholder & Use Case Board -->
        <section id="stakeholders" class="panel">
            <div class="rounded-lg border bg-white shadow-sm h-[calc(100vh-250px)] flex flex-col">
                <div class="border-b bg-slate-50 p-3 flex items-center justify-between z-10">
                    <h2 class="text-lg font-bold text-blue-900">Projekt-Ökosystem</h2>
                    <div class="text-xs text-slate-500 flex gap-4">
                        <span class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-purple-100 border border-purple-300"></div> Stakeholder</span>
                        <span class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></div> Use Case</span>
                        <span class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></div> User Story</span>
                    </div>
                </div>
                <!-- Canvas Bereich -->
                <div id="ecosystem-canvas" class="flex-1 overflow-auto bg-slate-50 p-8 relative">
                    <!-- Wrapper, der mitwächst -->
                    <div class="relative min-w-full min-h-full">
                        <svg id="ecosystem-lines" class="absolute inset-0 w-full h-full pointer-events-none z-0"></svg>
                        <div id="ecosystem-nodes" class="relative z-10 w-full"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PANEL 3: Code & Tests (Platzhalter) -->
        <section id="workflow" class="panel">
            <div class="rounded-lg border bg-white p-6 shadow-sm h-[calc(100vh-250px)]">Code & Tests kommen später...</div>
        </section>

        <!-- PANEL 4: Dokumente (Platzhalter) -->
        <section id="documents" class="panel">
            <div class="rounded-lg border bg-white p-6 shadow-sm h-[calc(100vh-250px)]">Lastenheft-Generator kommt später...</div>
        </section>

        <!-- PANEL 5: Historie -->
        <section id="history" class="panel">
            <div id="historyContainer" class="rounded-lg border bg-white p-6 shadow-sm h-[calc(100vh-250px)] overflow-y-auto">
                Lade Historie...
            </div>
        </section>

    </main>

    <!-- Modal für Neues Projekt -->
    <div id="newProjectModal" class="fixed inset-0 hidden items-center justify-center bg-slate-900/60 p-4 z-50">
        <form method="POST" action="../api/web_create_project.php"
            class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-bold text-blue-900">Neues Projekt erstellen</h2>
            <label class="block text-sm font-semibold text-slate-700">Projektname
                <input name="name" required class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Beschreibung
                <textarea name="description" rows="3" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"></textarea>
            </label>
            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')"
                    class="rounded border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
                <button type="submit" class="rounded bg-blue-900 px-4 py-2 text-white hover:bg-blue-800 font-medium transition shadow">Projekt anlegen</button>
            </div>
        </form>
    </div>

    <!-- Modal für Neue Anforderung / Stakeholder -->
    <!-- WICHTIG: Flex items-center justify-center hält es immer zentriert! -->
    <div id="reqModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 sm:p-6 backdrop-blur-sm">
        
        <!-- max-h-[90vh] und overflow-y-auto garantieren Scrollbarkeit, falls das Fenster zu klein ist -->
        <form id="reqForm"
            class="w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col space-y-4 rounded-xl bg-white p-6 sm:p-8 shadow-2xl">
            
            <h2 id="reqHeading" class="text-xl font-bold text-blue-900 border-b pb-3">Neue Anforderung</h2>

            <div>
                <label class="text-sm font-semibold block mb-1 text-slate-700">Typ</label>
                <select id="type" onchange="onTypeChange()"
                    class="w-full rounded border p-2 text-sm bg-slate-50 font-medium focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <optgroup label="Projektkontext">
                        <option value="STK">Stakeholder (STK)</option>
                    </optgroup>
                    <optgroup label="Anforderungsanalyse">
                        <option value="US">User Story (US)</option>
                        <option value="UC">Use Case (UC)</option>
                        <option value="USR">User Requirement (USR)</option>
                    </optgroup>
                    <optgroup label="System & Architektur">
                        <option value="SYS">System Requirement (SYS)</option>
                        <option value="SRS">Software/Hardware Requirement (SRS)</option>
                        <option value="SWC">Komponente / Modul (SWC)</option>
                    </optgroup>
                    <optgroup label="Verifikation & Test">
                        <option value="TC">Test Case / Specification (TC)</option>
                        <option value="TR">Test Result / Protocol (TR)</option>
                    </optgroup>
                </select>
            </div>

            <label class="block text-sm font-semibold text-slate-700">Titel / Name
                <input id="title" required class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </label>

            <label class="block text-sm font-semibold text-slate-700">Beschreibung
                <textarea id="text" required rows="3" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"></textarea>
            </label>

            <label class="block text-sm font-semibold text-slate-700">Begründung (Rationale)
                <textarea id="rationale" rows="2" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"></textarea>
            </label>

            <!-- Dynamische Attribute (wird per JS gefüllt) -->
            <div id="dynamicAttributes" class="hidden rounded-lg bg-slate-50 p-5 border border-slate-200 shadow-inner">
                <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500">Spezifische Attribute</h3>
                <div id="attributeFields" class="grid gap-4 md:grid-cols-2"></div>
            </div>

            <!-- Traceability (Verlinkungen) -->
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 pt-5 mt-2">
                <!-- Parents -->
                <div>
                    <label class="text-sm font-semibold block mb-1 text-slate-700">Parents (Erfüllt)</label>
                    <input type="text" id="parentSearch" placeholder="Suchen..."
                        class="w-full text-xs rounded border p-1.5 mb-2 bg-slate-50 focus:border-blue-500 outline-none"
                        oninput="filterCheckboxes('parentSearch', 'parentsCheckboxList')">
                    <div id="parentsCheckboxList"
                        class="h-40 overflow-y-auto rounded border bg-white p-2 space-y-1 text-xs shadow-inner">
                        <!-- Wird dynamisch gefüllt -->
                    </div>
                </div>
                <!-- Children -->
                <div>
                    <label class="text-sm font-semibold block mb-1 text-slate-700">Children (Wird erfüllt durch)</label>
                    <input type="text" id="childSearch" placeholder="Suchen..."
                        class="w-full text-xs rounded border p-1.5 mb-2 bg-slate-50 focus:border-blue-500 outline-none"
                        oninput="filterCheckboxes('childSearch', 'childrenCheckboxList')">
                    <div id="childrenCheckboxList"
                        class="h-40 overflow-y-auto rounded border bg-white p-2 space-y-1 text-xs shadow-inner">
                        <!-- Wird dynamisch gefüllt -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" id="cancelReq"
                    class="rounded border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
                <button type="submit"
                    class="rounded bg-blue-900 px-5 py-2 font-medium text-white shadow hover:bg-blue-800 transition">Speichern</button>
            </div>
        </form>
    </div>

    <!-- Dein JavaScript lädt die Logik -->
    <script type="module" src="js/app.js"></script>
    
    <!-- Kleines Inline-Script für die Tab-Steuerung -->
    <script>
        document.querySelectorAll('.tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Alle Tabs deaktivieren
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active', 'text-blue-900');
                    t.classList.add('border-transparent', 'text-slate-600');
                });
                // Geklickten Tab aktivieren
                e.target.classList.add('active', 'text-blue-900');
                e.target.classList.remove('border-transparent', 'text-slate-600');
                
                // Alle Panels ausblenden
                document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
                // Passendes Panel einblenden
                document.getElementById(e.target.dataset.panel).classList.add('show');
            });
        });
        
        // Modal Schließen Logik für den Abbrechen-Button
        document.getElementById('cancelReq').addEventListener('click', () => {
            document.getElementById('reqModal').classList.add('hidden');
        });
    </script>
</body>
</html>