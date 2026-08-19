<?php
// 1. Session starten
session_start();

// 2. Datenbank und Rechteprüfung einbinden
require_once '../config/db.php';

// 3. Wenn nicht eingeloggt, direkt rausschmeißen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 4. Header mit Menü und Hostname laden
require_once 'header.php';
?>


<!-- Globaler Ladescreen -->
<div id="globalLoader"
    class="fixed inset-0 z-[999] hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white p-6 rounded-lg shadow-2xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-slate-200 border-t-blue-900 rounded-full animate-spin"></div>
        <p class="mt-4 text-sm font-bold text-blue-900">Synchronisiere Daten...</p>
    </div>
</div>

<script>
    // Mache die Lade-Befehle global verfügbar
    window.showLoader = () => document.getElementById('globalLoader').classList.remove('hidden');
    window.hideLoader = () => document.getElementById('globalLoader').classList.add('hidden');
</script>

<div id="notificationArea" class="fixed right-4 top-4 z-[100] w-[min(92vw,520px)] space-y-3" aria-live="assertive"
    aria-atomic="true"></div>



<!-- NAVIGATION (Die neuen sauberen Reiter) -->
<!-- NAVIGATION (Jetzt mit Ziele & Assets auf oberster Ebene) -->
<nav class="border-b bg-white shadow-sm shrink-0">
    <div class="mx-auto flex max-w-screen-2xl px-6">
        <!-- Das 'requirements' Panel wird nun für 3 Tabs genutzt, gesteuert über data-filter -->
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="projectteam">Projektteam</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="dashboard">Dashboard</button>

        <button class="tab active border-b-2 border-blue-900 px-4 py-3 font-bold text-blue-900 transition-colors"
            data-panel="requirements">
            Anforderungen
        </button>

        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="assets">
            Assets
        </button>

        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="goals">
            Ziele
        </button>

        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="projectplan">Projektplan</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="issues">Issues</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="risks">Risiko</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="stakeholders">Stakeholder</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="usecases">Use Cases</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="userstories">User Stories</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="history">Historie</button>

        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="sbom">SBOM</button>
        <button
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 hover:text-blue-900 transition-colors"
            data-panel="iso14001">ISO 14001</button>
    </div>
</nav>



<!-- MAIN CONTENT (Lädt nur noch die Bausteine!) -->
<main class="mx-auto max-w-screen-2xl w-full p-6 flex-1 overflow-hidden">

    <section id="projectteam" class="panel h-full">
        <?php include 'views/project_team.php'; ?>
    </section>
    <section id="dashboard" class="panel show h-full">
        <?php include 'views/dashboard.php'; ?>
    </section>
    <section id="requirements" class="panel h-full">
        <?php include 'views/requirements.php'; ?>
    </section>

    <section id="assets" class="panel h-full">
        <?php include 'views/assets.php'; ?>
    </section>

    <section id="goals" class="panel h-full">
        <?php include 'views/goals.php'; ?>
    </section>
    <section id="projectplan" class="panel h-full">
        <?php include 'views/project_plan.php'; ?>
    </section>
    <section id="issues" class="panel h-full">
        <?php include 'views/issues.php'; ?>
    </section>
    <section id="risks" class="panel h-full">
        <?php include 'views/risks.php'; ?>
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

    <section id="sbom" class="panel h-full">
        <?php include 'views/sbom.php'; ?>
    </section>

    <section id="iso14001" class="panel h-full">
        <?php include 'views/iso14001.php'; ?>
    </section>

</main>

<!-- Globales Projekt Modal -->
<div id="newProjectModal" class="fixed inset-0 hidden items-center justify-center bg-slate-900/60 p-4 z-50">
    <form method="POST" action="../api/web_create_project.php"
        class="w-full max-w-md space-y-4 -lg bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-blue-900">Neues Projekt erstellen</h2>
        <label class="block text-sm font-semibold text-slate-700">Projektname
            <input name="name" required
                class="mt-1 w-full border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
        </label>
        <label class="block text-sm font-semibold text-slate-700">Beschreibung
            <textarea name="description" rows="3"
                class="mt-1 w-full border p-2 font-normal focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"></textarea>
        </label>
        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')"
                class="border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
            <button type="submit"
                class="bg-blue-900 px-4 py-2 text-white hover:bg-blue-800 font-medium transition shadow">Projekt
                anlegen</button>
        </div>
    </form>
</div>

<!-- Projektwechsel Bestätigungs-Modal -->
<div id="projectSwitchModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white -xl shadow-2xl border border-slate-200 w-full max-w-md p-6 transform transition-all">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 -full bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Projekt wechseln?</h3>
                <p class="text-xs text-slate-500">Du bist im Begriff, den Arbeitsbereich zu wechseln.</p>
            </div>
        </div>

        <p class="text-sm text-slate-600 mb-6">
            Möchtest du das Projekt <span id="modalProjectName" class="font-bold text-blue-900"></span> öffnen?
            Nicht gespeicherte Änderungen gehen dabei verloren.
        </p>

        <div class="flex justify-end space-x-3">
            <button id="modalCancelBtn" type="button"
                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 -lg hover:bg-slate-50 transition">
                Abbrechen
            </button>
            <button id="modalConfirmBtn" type="button"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 -lg hover:bg-blue-700 transition shadow-sm">
                Ja, Projekt öffnen
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script type="module" src="js/app.js"></script>
<script>
    document.querySelectorAll('.tab').forEach(button => {
        button.addEventListener('click', event => {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove(
                    'active',
                    'border-blue-900',
                    'text-blue-900',
                    'font-bold'
                );

                tab.classList.add(
                    'border-transparent',
                    'text-slate-600',
                    'font-semibold'
                );
            });

            const clickedTab = event.currentTarget;

            clickedTab.classList.add(
                'active',
                'border-blue-900',
                'text-blue-900',
                'font-bold'
            );

            clickedTab.classList.remove(
                'border-transparent',
                'text-slate-600',
                'font-semibold'
            );

            const targetPanel = clickedTab.dataset.panel;

            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('show');
            });

            document
                .getElementById(targetPanel)
                ?.classList.add('show');
        });
    });
</script>
</body>

</html>