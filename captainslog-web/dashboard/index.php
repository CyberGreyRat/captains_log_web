<?php
// dashboard/index.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/header.php';
?>

<!-- Globaler Loader -->
<div id="globalLoader"
    class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="flex w-full max-w-sm flex-col items-center rounded-lg border border-slate-300 bg-white p-8 shadow-2xl">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-[#0d3158]"></div>
        <p class="mt-5 text-sm font-extrabold uppercase tracking-[0.16em] text-[#0d3158]">
            Synchronisiere Daten...
        </p>
    </div>
</div>

<!-- Benachrichtigungen -->
<div id="notificationArea" class="fixed right-4 top-4 z-[9999] w-[min(92vw,520px)] space-y-3" aria-live="assertive"
    aria-atomic="true"></div>

<!-- Navigation - Volle Breite, engere Tabs -->
<nav class="shrink-0 border-b border-slate-300 bg-white shadow-sm">
    <div class="flex w-full overflow-x-auto px-2">
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="projectteam">Projektteam</button>
        <button type="button"
            class="tab active border-b-2 border-blue-900 bg-blue-50 px-3 py-2.5 text-sm font-bold whitespace-nowrap text-blue-900 transition-colors"
            data-panel="dashboard">Dashboard</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="requirements">Anforderungen</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="testmanagement">Test Management</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="assets">Assets</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="goals">Ziele</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="projectplan">Projektplan</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="issues">Issues</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="risks">Risiken</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="stakeholders">Stakeholder</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="usecases">Use Cases</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="userstories">User Stories</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="history">Historie</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="sbom">SBOM</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="iso14001">ISO 14001</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="attachments">📎 Anhänge</button>
        <button type="button"
            class="tab border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="reports">Reports</button>
    </div>
</nav>

<!-- Hauptinhalt - Volle Breite, minimales Padding (8px) -->
<main class="flex min-h-0 w-full flex-1 overflow-hidden px-8 py-4">
    <section id="projectteam" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/project_team.php'; ?>
    </section>
    <section id="dashboard" class="panel show h-full min-h-0 w-full"><?php include __DIR__ . '/views/dashboard.php'; ?>
    </section>
    <section id="requirements" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/requirements.php'; ?>
    </section>
    <section id="testmanagement" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/testmanagement.php'; ?></section>
    <section id="assets" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/assets.php'; ?></section>
    <section id="goals" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/goals.php'; ?></section>
    <section id="projectplan" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/project_plan.php'; ?>
    </section>
    <section id="issues" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/issues.php'; ?></section>
    <section id="risks" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/risks.php'; ?></section>
    <section id="stakeholders" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/stakeholders.php'; ?>
    </section>
    <section id="usecases" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/usecases.php'; ?>
    </section>
    <section id="userstories" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/userstories.php'; ?>
    </section>
    <section id="history" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/history.php'; ?></section>
    <section id="sbom" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/sbom.php'; ?></section>
    <section id="iso14001" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/iso14001.php'; ?>
    </section>
    <section id="attachments" class="panel h-full min-h-0 w-full"><?php require __DIR__ . '/views/attachments.php'; ?>
    </section>
    <section id="reports" class="panel h-full min-h-0 w-full"><?php include __DIR__ . '/views/reports.php'; ?></section>
</main>

<!-- Neues Projekt -->
<div id="newProjectModal"
    class="fixed inset-0 z-[300] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <form action="../api/web_create_project.php" method="POST"
        class="cl-modal max-w-md w-full bg-white rounded shadow-2xl overflow-hidden">
        <div class="cl-modal-header bg-[#eef2f6] border-b border-slate-300 p-4 flex justify-between items-start">
            <div>
                <p class="cl-panel-eyebrow text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Projektverwaltung</p>
                <h2 class="cl-modal-title text-lg font-extrabold text-[#0d3158]">Neues Projekt erstellen</h2>
            </div>
            <button type="button" data-close-modal="newProjectModal"
                class="text-slate-500 hover:text-slate-800 transition">✖</button>
        </div>
        <div class="cl-modal-body p-6 space-y-4">
            <label class="block text-sm font-bold text-slate-700">Projektname
                <input name="name" type="text" required maxlength="255" autocomplete="off"
                    placeholder="Name des neuen Projekts"
                    class="w-full mt-1 border border-slate-300 p-2.5 rounded outline-none focus:border-[#0d3158]">
            </label>
            <label class="block text-sm font-bold text-slate-700">Beschreibung
                <textarea name="description" rows="4" placeholder="Kurze Beschreibung von Ziel und Inhalt"
                    class="w-full mt-1 border border-slate-300 p-2.5 rounded outline-none focus:border-[#0d3158]"></textarea>
            </label>
        </div>
        <div class="cl-modal-footer bg-slate-50 border-t border-slate-300 p-4 flex justify-end gap-3">
            <button type="button" data-close-modal="newProjectModal"
                class="border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase rounded hover:bg-slate-100">Abbrechen</button>
            <button type="submit"
                class="bg-[#0d3158] text-white px-4 py-2 text-xs font-bold uppercase rounded hover:bg-blue-900">Projekt
                anlegen</button>
        </div>
    </form>
</div>

<!-- Projektwechsel bestätigen -->
<div id="projectSwitchModal"
    class="fixed inset-0 z-[310] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="max-w-md w-full bg-white rounded shadow-2xl overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-200 flex items-center gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Arbeitsbereich wechseln</p>
                <h3 class="text-lg font-extrabold text-[#0d3158]">Projekt öffnen?</h3>
            </div>
        </div>
        <div class="p-6">
            <p class="text-sm leading-6 text-slate-700">Möchtest du das Projekt <span id="modalProjectName"
                    class="font-bold text-[#0d3158]"></span> öffnen?</p>
            <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">Nicht
                gespeicherte Änderungen können beim Wechsel verloren gehen.</div>
        </div>
        <div class="bg-slate-50 border-t border-slate-200 p-4 flex justify-end gap-3">
            <button id="modalCancelBtn" type="button"
                class="border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase rounded hover:bg-slate-100">Abbrechen</button>
            <button id="modalConfirmBtn" type="button"
                class="bg-[#0d3158] text-white px-4 py-2 text-xs font-bold uppercase rounded hover:bg-blue-900">Projekt
                öffnen</button>
        </div>
    </div>
</div>

<script>
    window.showLoader = function () {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.classList.add('flex');
        }
    };

    window.hideLoader = function () {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            loader.classList.add('hidden');
            loader.classList.remove('flex');
        }
    };

    window.openNewProjectModal = function () {
        const modal = document.getElementById('newProjectModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    document
        .querySelectorAll('[data-close-modal]')
        .forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.dataset.closeModal;
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });

    document
        .getElementById('newProjectModal')
        ?.addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
            }
        });
</script>
<script type="module" src="js/app.js"></script>
</body>

</html>