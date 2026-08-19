// dashboard/js/app.js

import {
    setCurrentProjectId,
    currentProjectId
} from './state.js';

import {
    fetchProjects
} from './api.js';

import {
    loadRequirements,
    initRequirementEvents
} from './requirements.js';

import {
    loadStakeholders,
    initStakeholderEvents
} from './stakeholders.js';

import {
    loadUseCases,
    initUseCaseEvents
} from './usecases.js';

import {
    loadUserStories,
    initUserStoryEvents
} from './userstories.js';

import {
    loadHistory
} from './history.js';

import {
    loadDashboard
} from './dashboard.js';

import {
    loadSBOM
} from './sbom.js';

import {
    loadRisks,
    initRiskEvents
} from './risks.js';

import {
    loadProjectPlan,
    initProjectPlanEvents
} from './project_plan.js';

import {
    loadIsoData,
    initIsoEvents
} from './iso14001.js';

import {
    loadIssues,
    initIssueEvents
} from './issues.js';

import {
    loadAssets,
    initAssetEvents
} from './assets.js';

import {
    loadGoals,
    initGoalEvents
} from './goals.js';

import {
    loadProjectTeam,
    initProjectTeamEvents
} from './project_team.js';


document.addEventListener('DOMContentLoaded', async () => {
    try {
        /*
         * Zuerst alle Event-Handler registrieren.
         *
         * Dadurch reagieren die Schaltflächen auch dann,
         * wenn beim Seitenstart noch kein Projekt gewählt ist.
         */
        initAllModuleEvents();

        /*
         * Projektliste in das obere Dropdown laden.
         */
        await initProjectsDropdown();

        /*
         * Projektwechsel vorbereiten.
         */
        initProjectSwitch();

        /*
         * Daten beim Klick auf einen Reiter aktualisieren.
         */
        initTabLoadEvents();

        /*
         * Zuletzt verwendetes Projekt wiederherstellen.
         */
        if (currentProjectId) {
            const projectSelect =
                document.getElementById('projectSelect');

            if (projectSelect) {
                projectSelect.value = currentProjectId;
            }

            await loadAllProjectData();
        }
    } catch (error) {
        console.error(
            'Kritischer Fehler beim Start der Anwendung:',
            error
        );

        hideLoadingOverlay();
    }
});


/**
 * Initialisiert alle Ereignisse genau einmal.
 */
function initAllModuleEvents() {
    initRequirementEvents();
    initStakeholderEvents();
    initUseCaseEvents();
    initUserStoryEvents();
    initRiskEvents();
    initProjectPlanEvents();
    initIsoEvents();
    initIssueEvents();
    initAssetEvents();
    initGoalEvents();
    initProjectTeamEvents();
}


/**
 * Lädt sämtliche Daten des ausgewählten Projekts.
 *
 * Promise.allSettled verhindert, dass ein einzelnes fehlerhaftes
 * Modul das Laden aller anderen Reiter abbricht.
 */
async function loadAllProjectData() {
    if (!currentProjectId) {
        clearProjectViews();
        return;
    }

    const results = await Promise.allSettled([
        loadProjectTeam(),
        loadDashboard(),
        loadRequirements(),
        loadAssets(),
        loadGoals(),
        loadProjectPlan(),
        loadIssues(),
        loadRisks(),
        loadStakeholders(),
        loadUseCases(),
        loadUserStories(),
        loadSBOM(),
        loadIsoData()
    ]);

    /*
     * Einzelne Ladefehler in der Konsole ausgeben,
     * ohne die gesamte Anwendung zu stoppen.
     */
    const moduleNames = [
        'Projektteam',
        'Dashboard',
        'Anforderungen',
        'Assets',
        'Ziele',
        'Projektplan',
        'Issues',
        'Risiken',
        'Stakeholder',
        'Use Cases',
        'User Stories',
        'SBOM',
        'ISO 14001'
    ];

    results.forEach((result, index) => {
        if (result.status === 'rejected') {
            console.error(
                `Fehler beim Laden von ${moduleNames[index]}:`,
                result.reason
            );
        }
    });
}


/**
 * Initialisiert das Projekt-Dropdown.
 */
async function initProjectsDropdown() {
    try {
        const projects = await fetchProjects();

        const select =
            document.getElementById('projectSelect');

        if (!select) {
            console.error(
                'Projekt-Dropdown #projectSelect wurde nicht gefunden.'
            );

            return;
        }

        select.innerHTML = `
            <option value="">
                -- Projekt wählen --
            </option>
        `;

        projects.forEach(project => {
            const option =
                document.createElement('option');

            option.value = project.id;
            option.textContent = project.name;

            select.appendChild(option);
        });

        /*
         * Zuletzt ausgewähltes Projekt wieder markieren.
         */
        if (currentProjectId) {
            select.value = currentProjectId;
        }
    } catch (error) {
        console.error(
            'Fehler beim Füllen des Projekt-Dropdowns:',
            error
        );
    }
}


/**
 * Initialisiert den bestätigten Projektwechsel.
 */
function initProjectSwitch() {
    const projectSelect =
        document.getElementById('projectSelect');

    const modal =
        document.getElementById('projectSwitchModal');

    const modalProjectName =
        document.getElementById('modalProjectName');

    const confirmButton =
        document.getElementById('modalConfirmBtn');

    const cancelButton =
        document.getElementById('modalCancelBtn');

    /*
     * Ohne Projekt-Dropdown ist kein Projektwechsel möglich.
     */
    if (!projectSelect) {
        console.error(
            'Projektwechsel: #projectSelect wurde nicht gefunden.'
        );

        return;
    }

    /*
     * Fallback, falls das Bestätigungsfenster nicht existiert.
     * In diesem Fall wird das Projekt direkt gewechselt.
     */
    if (
        !modal ||
        !modalProjectName ||
        !confirmButton ||
        !cancelButton
    ) {
        console.warn(
            'Projektwechsel-Modal ist unvollständig. ' +
            'Projekt wird ohne Bestätigung gewechselt.'
        );

        projectSelect.addEventListener(
            'change',
            async event => {
                const selectedProjectId =
                    event.currentTarget.value;

                await switchProject(selectedProjectId);
            }
        );

        return;
    }

    let pendingProjectId = null;
    let previousProjectId = currentProjectId || '';

    projectSelect.addEventListener(
        'change',
        event => {
            const select =
                event.currentTarget;

            const selectedOption =
                select.options[select.selectedIndex];

            pendingProjectId =
                select.value;

            /*
             * Auswahl bis zur Bestätigung zurücksetzen.
             */
            select.value =
                currentProjectId || previousProjectId || '';

            if (!pendingProjectId) {
                return;
            }

            const projectName =
                selectedOption?.textContent?.trim() ||
                pendingProjectId;

            modalProjectName.textContent =
                `"${projectName}"`;

            modal.classList.remove('hidden');
        }
    );

    confirmButton.addEventListener(
        'click',
        async () => {
            if (!pendingProjectId) {
                modal.classList.add('hidden');
                return;
            }

            const selectedProjectId =
                pendingProjectId;

            modal.classList.add('hidden');

            /*
             * Wert vor dem asynchronen Ladevorgang sichern.
             */
            pendingProjectId = null;

            await switchProject(selectedProjectId);

            previousProjectId =
                currentProjectId || '';
        }
    );

    cancelButton.addEventListener(
        'click',
        () => {
            pendingProjectId = null;

            projectSelect.value =
                currentProjectId || previousProjectId || '';

            modal.classList.add('hidden');
        }
    );

    /*
     * Klick auf den dunklen Hintergrund bricht ebenfalls ab.
     */
    modal.addEventListener(
        'click',
        event => {
            if (event.target !== modal) {
                return;
            }

            pendingProjectId = null;

            projectSelect.value =
                currentProjectId || previousProjectId || '';

            modal.classList.add('hidden');
        }
    );
}


/**
 * Wechselt das aktive Projekt und lädt alle Daten.
 */
async function switchProject(projectId) {
    const projectSelect =
        document.getElementById('projectSelect');

    if (!projectId) {
        setCurrentProjectId('');

        if (projectSelect) {
            projectSelect.value = '';
        }

        clearProjectViews();
        return;
    }

    showLoadingOverlay();

    try {
        /*
         * Die ID zuerst im zentralen State setzen.
         *
         * Der Import currentProjectId ist eine Live-Bindung und
         * enthält danach den neuen Wert.
         */
        setCurrentProjectId(projectId);

        if (projectSelect) {
            projectSelect.value = projectId;
        }

        /*
         * Ladeanzeige mindestens kurz sichtbar halten.
         */
        const minimumWait =
            new Promise(resolve => {
                setTimeout(resolve, 500);
            });

        await Promise.all([
            loadAllProjectData(),
            minimumWait
        ]);
    } catch (error) {
        console.error(
            'Fehler beim Wechseln des Projekts:',
            error
        );
    } finally {
        hideLoadingOverlay();
    }
}


/**
 * Lädt beim Öffnen eines Reiters dessen Daten erneut.
 */
function initTabLoadEvents() {
    document
        .querySelectorAll('.tab')
        .forEach(button => {
            button.addEventListener(
                'click',
                async event => {
                    /*
                     * currentTarget verwenden.
                     *
                     * target kann ein SVG oder ein span innerhalb
                     * der Schaltfläche sein.
                     */
                    const panelName =
                        event.currentTarget.dataset.panel;

                    if (!panelName) {
                        return;
                    }

                    try {
                        switch (panelName) {
                            case 'projectteam':
                                await loadProjectTeam();
                                break;

                            case 'dashboard':
                                await loadDashboard();
                                break;

                            case 'requirements':
                                await loadRequirements();
                                break;

                            case 'assets':
                                await loadAssets();
                                break;

                            case 'goals':
                                await loadGoals();
                                break;

                            case 'projectplan':
                                await loadProjectPlan();
                                break;

                            case 'issues':
                                await loadIssues();
                                break;

                            case 'risks':
                                await loadRisks();
                                break;

                            case 'stakeholders':
                                await loadStakeholders();
                                break;

                            case 'usecases':
                                await loadUseCases();
                                break;

                            case 'userstories':
                                await loadUserStories();
                                break;

                            case 'history':
                                await loadHistory();
                                break;

                            case 'sbom':
                                await loadSBOM();
                                break;

                            /*
                             * In index.php heißt das Panel:
                             * data-panel="iso14001"
                             */
                            case 'iso14001':
                                await loadIsoData();
                                break;

                            default:
                                break;
                        }
                    } catch (error) {
                        console.error(
                            `Fehler beim Laden des Panels "${panelName}":`,
                            error
                        );
                    }
                }
            );
        });
}


/**
 * Zeigt den Ladebildschirm.
 */
function showLoadingOverlay() {
    const loadingOverlay =
        document.getElementById('loadingOverlay');

    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }

    /*
     * Falls der zweite globale Loader verwendet wird.
     */
    if (typeof window.showLoader === 'function') {
        window.showLoader();
    }
}


/**
 * Versteckt den Ladebildschirm.
 */
function hideLoadingOverlay() {
    const loadingOverlay =
        document.getElementById('loadingOverlay');

    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
    }

    if (typeof window.hideLoader === 'function') {
        window.hideLoader();
    }
}


/**
 * Setzt Bereiche auf den Zustand ohne Projektauswahl zurück.
 */
function clearProjectViews() {
    const items =
        document.getElementById('items');

    const detail =
        document.getElementById('detail');

    const projectTeamBody =
        document.getElementById('projectTeamTableBody');

    const assetTableBody =
        document.getElementById('assetTableBody');

    const goalContainer =
        document.getElementById('goalCardContainer');

    const issueTableBody =
        document.getElementById('issueTableBody');

    const taskTableBody =
        document.getElementById('taskTableBody');

    if (items) {
        items.innerHTML = `
            <div class="p-4 text-sm text-slate-500">
                Bitte wähle oben ein Projekt aus.
            </div>
        `;
    }

    if (detail) {
        detail.innerHTML = `
            <div class="flex h-full items-center justify-center text-slate-400 italic">
                Anforderung auswählen
            </div>
        `;
    }

    if (projectTeamBody) {
        projectTeamBody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="p-8 text-center italic text-slate-400">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;
    }

    if (assetTableBody) {
        assetTableBody.innerHTML = `
            <tr>
                <td
                    colspan="6"
                    class="p-8 text-center italic text-slate-400">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;
    }

    if (goalContainer) {
        goalContainer.innerHTML = `
            <div class="col-span-full border border-slate-300 bg-white p-8 text-center italic text-slate-400">
                Bitte zuerst ein Projekt auswählen.
            </div>
        `;
    }

    if (issueTableBody) {
        issueTableBody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="p-8 text-center italic text-slate-400">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;
    }

    if (taskTableBody) {
        taskTableBody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="p-8 text-center italic text-slate-400">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;
    }
}