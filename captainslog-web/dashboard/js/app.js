// dashboard/js/app.js
import { setCurrentProjectId, currentProjectId } from './state.js';
import { fetchProjects } from './api.js';
import { loadRequirements, initRequirementEvents } from './requirements.js';
import { loadStakeholders, initStakeholderEvents } from './stakeholders.js';
import { loadUseCases, initUseCaseEvents } from './usecases.js';
import { loadUserStories, initUserStoryEvents } from './userstories.js';
import { loadHistory } from './history.js';
import { loadDashboard } from './dashboard.js';
import { loadSBOM } from './sbom.js';
import { loadRisks, initRiskEvents } from './risks.js';

document.addEventListener("DOMContentLoaded", async () => {
    try {

        await initProjectsDropdown();

            if (currentProjectId) {
            const projectSelect = document.getElementById('projectSelect');
            if (projectSelect) projectSelect.value = currentProjectId;
            
            // Lade direkt alle Daten des gemerkten Projekts
            loadRequirements();
            loadStakeholders();
            loadUseCases();
            loadUserStories();
            loadDashboard();
            loadSBOM();
            loadRisks();
        }

        initRequirementEvents();
        initStakeholderEvents();
        initUseCaseEvents();
        initUserStoryEvents();

        const projectSelect = document.getElementById('projectSelect');
        const modal = document.getElementById('projectSwitchModal');
        const modalProjectName = document.getElementById('modalProjectName');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');

        let pendingProjectId = null;

        if (projectSelect && modal) {
            projectSelect.addEventListener('change', (e) => {
                const selectedOption = projectSelect.options[projectSelect.selectedIndex];

                const projectName = selectedOption ? selectedOption.text : '';

                pendingProjectId = e.target.value;

                e.target.value = currentProjectId || "";

                if (!pendingProjectId) return;

                modalProjectName.textContent = `"${projectName}"`;

                modal.classList.remove('hidden');
            });

           // Klick auf "Ja, Projekt öffnen" im Modal
            confirmBtn.onclick = async () => {
                modal.classList.add('hidden');

                if (!pendingProjectId) return;

                // 1. TRIGGER: Ladebalken einblenden
                document.getElementById('loadingOverlay').classList.remove('hidden');

                setCurrentProjectId(pendingProjectId);
                projectSelect.value = pendingProjectId;

                if (currentProjectId) {
                    // 2. Mindestens 500ms warten + Daten laden
                    const minWait = new Promise(resolve => setTimeout(resolve, 500));
                    
                    const loadData = async () => {
                        await loadRequirements();
                        await loadStakeholders();
                        await loadUseCases();
                        await loadUserStories();
                        await loadDashboard();
                        await loadSBOM();
                        await loadRisks();
                    };

                    // Wartet, bis SOWOHL die 500ms um sind, ALS AUCH die Daten geladen wurden
                    await Promise.all([loadData(), minWait]);
                } else {
                    const items = document.getElementById('items');
                    if (items) items.innerHTML = '<div class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>';
                    const detail = document.getElementById('detail');
                    if (detail) detail.innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Anforderung auswählen</div>';
                }
                
                // 3. TRIGGER: Ladebalken wieder ausblenden
                document.getElementById('loadingOverlay').classList.add('hidden');
                
                pendingProjectId = null;
            };
        }

        document.querySelectorAll('.tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (e.target.dataset.panel === 'history') {
                    loadHistory();
                }
                if (e.target.dataset.panel === 'dashboard') {
                    loadDashboard();
                }
                if (e.target.dataset.panel === 'sbom') {
                    loadSBOM();
                }
                if (e.target.dataset.panel === 'risks') {
                    loadRisks();
                }
            });
        });
    } catch (error) {
        console.error("Kritischer Fehler beim Start der Anwendung:", error);
    }
});

async function initProjectsDropdown() {
    try {
        const projects = await fetchProjects();
        const select = document.getElementById('projectSelect');
        if (!select) return;
        select.innerHTML = '<option value="">-- Projekt wählen --</option>';
        projects.forEach(p => {
            select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    } catch (e) {
        console.error("Fehler beim Füllen des Projekt-Dropdowns:", e);
    }
}