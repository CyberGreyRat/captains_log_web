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

        <div class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-blue-950"></div>

        <p class="mt-5 text-sm font-extrabold uppercase tracking-[0.16em] text-blue-950">
            Synchronisiere Daten...
        </p>
    </div>
</div>


<!-- Benachrichtigungen -->
<div id="notificationArea" class="fixed right-4 top-4 z-[9999] w-[min(92vw,520px)] space-y-3" aria-live="assertive"
    aria-atomic="true">
</div>


<!-- Navigation -->
<nav class="shrink-0 border-b border-slate-300 bg-white shadow-sm">

    <div class="mx-auto flex max-w-screen-2xl overflow-x-auto px-6">

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="projectteam">
            Projektteam
        </button>

        <button type="button"
            class="tab active border-b-2 border-blue-900 bg-blue-50 px-4 py-3 font-bold text-blue-900 transition-colors"
            data-panel="dashboard">
            Dashboard
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="requirements">
            Anforderungen
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="assets">
            Assets
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="goals">
            Ziele
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="projectplan">
            Projektplan
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="issues">
            Issues
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="risks">
            Risiken
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="stakeholders">
            Stakeholder
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="usecases">
            Use Cases
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="userstories">
            User Stories
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="history">
            Historie
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="sbom">
            SBOM
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="iso14001">
            ISO 14001
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="attachments">
            📎 Anhänge
        </button>

        <button type="button"
            class="tab border-b-2 border-transparent px-4 py-3 font-semibold text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-950"
            data-panel="reports">
            Reports
        </button>

    </div>
</nav>


<!-- Hauptinhalt -->
<main class="mx-auto flex min-h-0 w-full max-w-screen-2xl flex-1 overflow-hidden p-6">

    <section id="projectteam" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/project_team.php'; ?>
    </section>

    <section id="dashboard" class="panel show h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/dashboard.php'; ?>
    </section>

    <section id="requirements" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/requirements.php'; ?>
    </section>

    <section id="assets" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/assets.php'; ?>
    </section>

    <section id="goals" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/goals.php'; ?>
    </section>

    <section id="projectplan" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/project_plan.php'; ?>
    </section>

    <section id="issues" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/issues.php'; ?>
    </section>

    <section id="risks" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/risks.php'; ?>
    </section>

    <section id="stakeholders" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/stakeholders.php'; ?>
    </section>

    <section id="usecases" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/usecases.php'; ?>
    </section>

    <section id="userstories" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/userstories.php'; ?>
    </section>

    <section id="history" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/history.php'; ?>
    </section>

    <section id="sbom" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/sbom.php'; ?>
    </section>

    <section id="iso14001" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/iso14001.php'; ?>
    </section>

    <section id="attachments" class="panel">
        <?php require __DIR__ . '/views/attachments.php'; ?>
    </section>

    <section id="reports" class="panel h-full min-h-0 w-full">
        <?php include __DIR__ . '/views/reports.php'; ?>
    </section>

</main>


<!-- Neues Projekt -->
<div id="newProjectModal"
    class="fixed inset-0 z-[300] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

    <!-- HIER IST DER REPARIERTE FORM-TAG INKLUSIVE STYLING -->
    <form action="../api/web_create_project.php" method="POST" class="cl-modal max-w-md">

        <div class="cl-modal-header">
            <div>
                <p class="cl-panel-eyebrow">
                    Projektverwaltung
                </p>
                <h2 class="cl-modal-title">
                    Neues Projekt erstellen
                </h2>
            </div>
            <button type="button" data-close-modal="newProjectModal"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" aria-label="Fenster schließen">
                ✕
            </button>
        </div>

        <div class="cl-modal-body space-y-5">
            <label class="cl-label">
                Projektname
                <input name="name" type="text" required maxlength="255" autocomplete="off"
                    placeholder="Name des neuen Projekts" class="cl-input text-base font-semibold">
            </label>

            <label class="cl-label">
                Beschreibung
                <textarea name="description" rows="4" placeholder="Kurze Beschreibung von Ziel und Inhalt"
                    class="cl-textarea"></textarea>
            </label>
        </div>

        <div class="cl-modal-footer">
            <button type="button" data-close-modal="newProjectModal" class="cl-button cl-button-secondary">
                Abbrechen
            </button>
            <button type="submit" class="cl-button cl-button-primary">
                Projekt anlegen
            </button>
        </div>
    </form>
</div>


<!-- Projektwechsel bestätigen -->
<div id="projectSwitchModal"
    class="fixed inset-0 z-[310] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

    <div class="cl-modal max-w-md">

        <div class="cl-modal-header">

            <div class="flex items-center gap-3">

                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z">
                        </path>
                    </svg>
                </div>

                <div>
                    <p class="cl-panel-eyebrow">
                        Arbeitsbereich wechseln
                    </p>

                    <h3 class="cl-modal-title">
                        Projekt öffnen?
                    </h3>
                </div>
            </div>
        </div>

        <div class="cl-modal-body">

            <p class="text-sm leading-6 text-slate-700">
                Möchtest du das Projekt

                <span id="modalProjectName" class="font-bold text-blue-950">
                </span>

                öffnen?
            </p>

            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                Nicht gespeicherte Änderungen können beim Wechsel verloren gehen.
            </div>
        </div>

        <div class="cl-modal-footer">

            <button id="modalCancelBtn" type="button" class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button id="modalConfirmBtn" type="button" class="cl-button cl-button-primary">
                Projekt öffnen
            </button>
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