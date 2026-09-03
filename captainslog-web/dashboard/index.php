<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/header.php'; ?>
<div id="globalLoader" class="cl-overlay hidden">
    <div class="cl-loading"><strong>Synchronisiere Daten...</strong><i></i></div>
</div>
<div id="notificationArea" class="cl-notifications"></div>
<nav class="cl-nav" aria-label="Hauptnavigation">
    <div class="cl-nav-inner">
        <button class="tab active" data-panel="dashboard">Dashboard</button><button class="tab"
            data-panel="requirements">Anforderungen</button><button class="tab"
            data-panel="risks">Risiken</button><button class="tab" data-panel="testmanagement">Tests</button><button
            class="tab" data-panel="projectplan">Projektplan</button>
        <details class="cl-nav-menu">
            <summary>Engineering</summary>
            <div><button class="tab" data-panel="assets">Assets</button><button class="tab"
                    data-panel="goals">Ziele</button><button class="tab"
                    data-panel="stakeholders">Stakeholder</button><button class="tab" data-panel="usecases">Use
                    Cases</button><button class="tab" data-panel="userstories">User Stories</button><button class="tab"
                    data-panel="sbom">SBOM</button></div>
        </details>
        <details class="cl-nav-menu">
            <summary>Nachweise</summary>
            <div><button class="tab" data-panel="history">Historie</button><button class="tab" data-panel="iso14001">ISO
                    14001</button><button class="tab" data-panel="attachments">Anhänge</button><button class="tab"
                    data-panel="reports">Reports</button></div>
        </details>
        <details class="cl-nav-menu">
            <summary>Verwaltung</summary>
            <div><button class="tab" data-panel="projectteam">Projektteam</button><button type="button"
                    data-open-project>Neues Projekt</button><?php if (($_SESSION['role'] ?? 'viewer') === 'admin'): ?><a
                        href="admin_users.php">Nutzerverwaltung</a><?php endif; ?></div>
        </details>
    </div>
</nav>
<main class="cl-main">
    <section id="projectteam" class="panel"><?php include __DIR__ . '/views/project_team.php'; ?></section>
    <section id="dashboard" class="panel show"><?php include __DIR__ . '/views/dashboard.php'; ?></section>
    <section id="requirements" class="panel"><?php include __DIR__ . '/views/requirements.php'; ?></section>
    <section id="testmanagement" class="panel"><?php include __DIR__ . '/views/testmanagement.php'; ?></section>
    <section id="assets" class="panel"><?php include __DIR__ . '/views/assets.php'; ?></section>
    <section id="goals" class="panel"><?php include __DIR__ . '/views/goals.php'; ?></section>
    <section id="projectplan" class="panel"><?php include __DIR__ . '/views/project_plan.php'; ?></section>
    <section id="issues" class="panel"><?php include __DIR__ . '/views/issues.php'; ?></section>
    <section id="risks" class="panel"><?php include __DIR__ . '/views/risks.php'; ?></section>
    <section id="stakeholders" class="panel"><?php include __DIR__ . '/views/stakeholders.php'; ?></section>
    <section id="usecases" class="panel"><?php include __DIR__ . '/views/usecases.php'; ?></section>
    <section id="userstories" class="panel"><?php include __DIR__ . '/views/userstories.php'; ?></section>
    <section id="history" class="panel"><?php include __DIR__ . '/views/history.php'; ?></section>
    <section id="sbom" class="panel"><?php include __DIR__ . '/views/sbom.php'; ?></section>
    <section id="iso14001" class="panel"><?php include __DIR__ . '/views/iso14001.php'; ?></section>
    <section id="attachments" class="panel"><?php include __DIR__ . '/views/attachments.php'; ?></section>
    <section id="reports" class="panel"><?php include __DIR__ . '/views/reports.php'; ?></section>
</main>
<dialog id="newProjectModal" class="cl-dialog">
    <form id="newProjectForm">
        <header>
            <h3>Neues Projekt</h3><button type="button" data-close-dialog="newProjectModal">×</button>
        </header><label>Projektname<input id="newProjectName" name="name" required></label><label>Beschreibung<textarea
                id="newProjectDescription" name="description"></textarea></label>
        <footer><button type="button" data-close-dialog="newProjectModal">Abbrechen</button><button type="submit"
                class="primary">Projekt anlegen</button></footer>
    </form>
</dialog>
<div id="projectSwitchModal" class="cl-modal hidden">
    <div>
        <h3>Projekt öffnen?</h3>
        <p>Möchtest du das Projekt <strong id="modalProjectName"></strong> öffnen?</p>
        <p>Nicht gespeicherte Änderungen können beim Wechsel verloren gehen.</p>
        <footer><button id="modalCancelBtn">Abbrechen</button><button id="modalConfirmBtn" class="primary">Projekt
                öffnen</button></footer>
    </div>
</div>
<script type="module" src="js/app.js?v=20260902-12"></script>
<script type="module" src="js/layout.js?v=20260902-1"></script>
</body>

</html>