// dashboard/js/app.js
import { setCurrentProjectId, currentProjectId } from './state.js';
import { fetchProjects } from './api.js';
import { loadRequirements, initRequirementEvents } from './requirements.js';
import { loadStakeholders, initStakeholderEvents } from './stakeholders.js';
import { loadUseCases, initUseCaseEvents } from './usecases.js';
import { loadUserStories, initUserStoryEvents } from './userstories.js';
import { loadHistory } from './history.js';
import { loadDashboard } from './dashboard.js';

document.addEventListener("DOMContentLoaded", async () => {
    try {
        await initProjectsDropdown();
        
        initRequirementEvents();
        initStakeholderEvents();
        initUseCaseEvents();
        initUserStoryEvents();

        const projectSelect = document.getElementById('projectSelect');
        if (projectSelect) {
            projectSelect.addEventListener('change', async (e) => {
                setCurrentProjectId(e.target.value);
                
                if (currentProjectId) {
                    loadRequirements();
                    loadStakeholders();
                    loadUseCases();
                    loadUserStories();
                    loadDashboard();
                } else {
                    const items = document.getElementById('items');
                    if (items) items.innerHTML = '<div class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>';
                    const detail = document.getElementById('detail');
                    if (detail) detail.innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Anforderung auswählen</div>';
                }
            });
        }

        document.querySelectorAll('.tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (e.target.dataset.panel === 'history') {
                    loadHistory();
                }
                if (e.target.dataset.panel === 'dashboard') {
                    loadDashboard();
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