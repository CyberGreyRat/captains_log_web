// dashboard/js/app.js

import {
    setCurrentProjectId,
    currentProjectId
} from './state.js';

import { fetchProjects } from './api.js';

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
    loadHistory,
    initHistoryEvents
} from './history.js';
import { loadDashboard } from './dashboard.js';
import { loadSBOM } from './sbom.js';

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

import { initReportsEvents } from './reports.js';

import {
    loadAttachments,
    initAttachmentEvents
} from './attachments.js';

import { loadTestManagement, initTestManagementEvents } from './testmanagement.js';






document.addEventListener('DOMContentLoaded', async () => {
    try {
        initializeModuleEvents();
        initializeTabNavigation();

        await initializeProjectDropdown();

        initializeProjectSwitch();
        initHistoryEvents();
        initReportsEvents();
        initTestManagementEvents();


        if (currentProjectId) {
            const projectSelect =
                document.getElementById('projectSelect');

            if (projectSelect) {
                projectSelect.value =
                    String(currentProjectId);
            }

            await loadAllProjectData();
        } else {
            clearProjectViews();
        }
    } catch (error) {
        console.error(
            'Kritischer Fehler beim Start der Anwendung:',
            error
        );

        hideLoadingOverlay();
    }
});


function initializeModuleEvents() {
    const initializers = [
        initRequirementEvents,
        initStakeholderEvents,
        initUseCaseEvents,
        initUserStoryEvents,
        initRiskEvents,
        initProjectPlanEvents,
        initIsoEvents,
        initIssueEvents,
        initAssetEvents,
        initGoalEvents,
        initProjectTeamEvents,
        initReportsEvents,
        initAttachmentEvents,
        initTestManagementEvents
    ];

    initializers.forEach(initializer => {
        try {
            initializer();
        } catch (error) {
            console.error(
                `Fehler beim Initialisieren von ${initializer.name}:`,
                error
            );
        }
    });
}


async function loadAllProjectData() {
    if (!currentProjectId) {
        clearProjectViews();
        return;
    }

    const modules = [
        ['Projektteam', loadProjectTeam],
        ['Dashboard', loadDashboard],
        ['Anforderungen', loadRequirements],
        ['Assets', loadAssets],
        ['Ziele', loadGoals],
        ['Projektplan', loadProjectPlan],
        ['Issues', loadIssues],
        ['Risiken', loadRisks],
        ['Stakeholder', loadStakeholders],
        ['Use Cases', loadUseCases],
        ['User Stories', loadUserStories],
        ['Historie', loadHistory],
        ['SBOM', loadSBOM],
        ['ISO 14001', loadIsoData],
        ['Anhänge', loadAttachments],
        ['Test Management', loadTestManagement]
    ];


    const results = await Promise.allSettled(
        modules.map(([, loader]) => loader())
    );

    results.forEach((result, index) => {
        if (result.status === 'rejected') {
            console.error(
                `Fehler beim Laden von ${modules[index][0]}:`,
                result.reason
            );
        }
    });
}


async function initializeProjectDropdown() {
    const select =
        document.getElementById('projectSelect');

    if (!select) {
        console.error(
            'Projekt-Dropdown #projectSelect wurde nicht gefunden.'
        );

        return;
    }

    try {
        const projects =
            await fetchProjects();

        select.innerHTML = `
            <option value="">
                -- Projekt wählen --
            </option>
        `;

        projects.forEach(project => {
            const option =
                document.createElement('option');

            option.value =
                String(project.id);

            option.textContent =
                project.name;

            select.appendChild(option);
        });

        if (currentProjectId) {
            select.value =
                String(currentProjectId);
        }
    } catch (error) {
        console.error(
            'Fehler beim Laden der Projektliste:',
            error
        );
    }
}


function initializeProjectSwitch() {
    const select =
        document.getElementById('projectSelect');

    if (!select) {
        return;
    }

    const modal =
        document.getElementById('projectSwitchModal');

    const projectName =
        document.getElementById('modalProjectName');

    const confirmButton =
        document.getElementById('modalConfirmBtn');

    const cancelButton =
        document.getElementById('modalCancelBtn');

    if (
        !modal ||
        !projectName ||
        !confirmButton ||
        !cancelButton
    ) {
        select.addEventListener(
            'change',
            event => {
                switchProject(
                    event.currentTarget.value
                );
            }
        );

        return;
    }

    let pendingProjectId = '';

    select.addEventListener(
        'change',
        event => {
            const selectedOption =
                select.options[select.selectedIndex];

            pendingProjectId =
                event.currentTarget.value;

            select.value =
                currentProjectId
                    ? String(currentProjectId)
                    : '';

            if (!pendingProjectId) {
                return;
            }

            projectName.textContent =
                `„${selectedOption?.textContent?.trim() || pendingProjectId}“`;

            openFlexModal(modal);
        }
    );

    confirmButton.addEventListener(
        'click',
        async () => {
            const selectedProjectId =
                pendingProjectId;

            pendingProjectId = '';

            closeFlexModal(modal);

            if (selectedProjectId) {
                await switchProject(
                    selectedProjectId
                );
            }
        }
    );

    cancelButton.addEventListener(
        'click',
        () => {
            pendingProjectId = '';

            select.value =
                currentProjectId
                    ? String(currentProjectId)
                    : '';

            closeFlexModal(modal);
        }
    );

    modal.addEventListener(
        'click',
        event => {
            if (event.target !== modal) {
                return;
            }

            pendingProjectId = '';

            select.value =
                currentProjectId
                    ? String(currentProjectId)
                    : '';

            closeFlexModal(modal);
        }
    );
}


async function switchProject(projectId) {
    const select =
        document.getElementById('projectSelect');

    if (!projectId) {
        setCurrentProjectId('');

        if (select) {
            select.value = '';
        }

        clearProjectViews();
        return;
    }

    showLoadingOverlay();

    try {
        setCurrentProjectId(projectId);

        if (select) {
            select.value =
                String(projectId);
        }

        await Promise.all([
            loadAllProjectData(),

            new Promise(resolve => {
                setTimeout(resolve, 500);
            })
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


function initializeTabNavigation() {
    const panelLoaders = {
        projectteam: loadProjectTeam,
        dashboard: loadDashboard,
        requirements: loadRequirements,
        assets: loadAssets,
        goals: loadGoals,
        projectplan: loadProjectPlan,
        issues: loadIssues,
        risks: loadRisks,
        stakeholders: loadStakeholders,
        usecases: loadUseCases,
        userstories: loadUserStories,
        history: loadHistory,
        sbom: loadSBOM,
        iso14001: loadIsoData,
        attachments: loadAttachments,
        testmanagement: loadTestManagement
    };

    document
        .querySelectorAll('.tab')
        .forEach(button => {
            button.addEventListener(
                'click',
                async event => {
                    const clickedTab =
                        event.currentTarget;

                    const panelName =
                        clickedTab.dataset.panel;

                    if (!panelName) {
                        return;
                    }

                    const targetPanel =
                        document.getElementById(panelName);

                    if (!targetPanel) {
                        console.error(
                            `Panel mit ID "${panelName}" wurde nicht gefunden.`
                        );

                        return;
                    }

                    document
                        .querySelectorAll('.tab')
                        .forEach(tab => {
                            tab.classList.remove(
                                'active',
                                'border-blue-900',
                                'bg-blue-50',
                                'text-blue-900',
                                'font-bold'
                            );

                            tab.classList.add(
                                'border-transparent',
                                'text-slate-600',
                                'font-semibold'
                            );
                        });

                    clickedTab.classList.add(
                        'active',
                        'border-blue-900',
                        'bg-blue-50',
                        'text-blue-900',
                        'font-bold'
                    );

                    clickedTab.classList.remove(
                        'border-transparent',
                        'text-slate-600',
                        'font-semibold'
                    );

                    document
                        .querySelectorAll('.panel')
                        .forEach(panel => {
                            panel.classList.remove('show');
                        });

                    targetPanel.classList.add('show');

                    try {
                        const loader =
                            panelLoaders[panelName];

                        if (loader) {
                            await loader();
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


function openFlexModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}


function closeFlexModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}


function showLoadingOverlay() {
    const loader =
        document.getElementById('globalLoader');

    if (loader) {
        loader.classList.remove('hidden');
        loader.classList.add('flex');
    }
}


function hideLoadingOverlay() {
    const loader =
        document.getElementById('globalLoader');

    if (loader) {
        loader.classList.add('hidden');
        loader.classList.remove('flex');
    }
}


function setTableMessage(
    elementId,
    columnCount,
    message
) {
    const element =
        document.getElementById(elementId);

    if (!element) {
        return;
    }

    element.innerHTML = `
        <tr>
            <td
                colspan="${columnCount}"
                class="cl-empty-state">
                ${message}
            </td>
        </tr>
    `;
}


function clearProjectViews() {
    const message =
        'Bitte zuerst ein Projekt auswählen.';

    const items =
        document.getElementById('items');

    const detail =
        document.getElementById('detail');

    const goals =
        document.getElementById('goalCardContainer');

    const history =
        document.getElementById('historyContainer');

    const sbom =
        document.getElementById('sbomContainer');

    if (items) {
        items.innerHTML = `
            <div class="cl-empty-state">
                ${message}
            </div>
        `;
    }

    if (detail) {
        detail.innerHTML = `
            <div class="flex h-full items-center justify-center italic text-slate-400">
                Anforderung auswählen
            </div>
        `;
    }

    if (goals) {
        goals.innerHTML = `
            <div class="cl-empty-state col-span-full">
                ${message}
            </div>
        `;
    }

    if (history) {
        history.innerHTML = `
            <div class="cl-empty-state">
                ${message}
            </div>
        `;
    }

    if (sbom) {
        sbom.innerHTML = `
            <div class="cl-empty-state">
                ${message}
            </div>
        `;
    }

    setTableMessage(
        'projectTeamTableBody',
        7,
        message
    );

    setTableMessage(
        'assetTableBody',
        6,
        message
    );

    setTableMessage(
        'issueTableBody',
        8,
        message
    );

    setTableMessage(
        'taskTableBody',
        7,
        message
    );

    setTableMessage(
        'riskTableBody',
        12,
        message
    );

    setTableMessage(
        'stakeholderTableBody',
        7,
        message
    );

    setTableMessage(
        'useCaseTableBody',
        5,
        message
    );

    setTableMessage(
        'userStoryTableBody',
        6,
        message
    );

    setTableMessage(
        'isoTableBody',
        5,
        message
    );
}
