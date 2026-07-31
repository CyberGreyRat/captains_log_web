// dashboard/js/app.js

import { setCurrentProjectId, currentProjectId } from './state.js';
import { fetchProjects } from './api.js';
import { loadHistory } from './history.js';

// Unsere 4 glasklaren, neuen Module:
import { loadRequirements, initRequirementEvents } from './requirements.js';
import { loadStakeholders, initStakeholderEvents } from './stakeholders.js';
import { loadUseCases, initUseCaseEvents } from './usecases.js';
import { loadUserStories, initUserStoryEvents } from './userstories.js';

document.addEventListener("DOMContentLoaded", async () => {
    
    await initProjectsDropdown();

    // Event-Listener aller Module aktivieren
    initRequirementEvents();
    initStakeholderEvents();
    initUseCaseEvents();
    initUserStoryEvents();

    // Projektwechsel abfangen
    document.getElementById('projectSelect').addEventListener('change', async (e) => {
        setCurrentProjectId(e.target.value);
        
        if (currentProjectId) {
            // Alle Ansichten mit Daten füllen
            loadRequirements();
            loadStakeholders();
            loadUseCases();
            loadUserStories();
        } else {
            // Leeren, falls "Projekt wählen" geklickt wird
            document.getElementById('items').innerHTML = '<div class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>';
            document.getElementById('detail').innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Anforderung auswählen</div>';
        }
    });

});

async function initProjectsDropdown() {
    const projects = await fetchProjects();
    const select = document.getElementById('projectSelect');
    if (!select) return;
    select.innerHTML = '<option value="">-- Projekt wählen --</option>';
    projects.forEach(p => {
        select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
    });
}

// Im Tab-Event-Listener:
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const panelId = e.target.dataset.panel;
        if (panelId === 'history') {
            loadHistory();
        }
    });
});