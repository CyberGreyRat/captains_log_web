import { setCurrentProjectId, setCurrentRequirements, currentProjectId } from './state.js';
import { fetchProjects, fetchRequirements } from './api.js';
import { drawSidebar, populateParentChildDropdowns } from './tree.js';
import { openNewReqModal, renderAttributes, saveRequirement, renderHistoryView } from './modals.js';
import { drawEcosystem } from './ecosystem.js';

document.addEventListener("DOMContentLoaded", async () => {
    // Projekte in Dropdown laden beim Start
    await initProjectsDropdown();

    // Event Listener für Projekt-Wechsel
    document.getElementById('projectSelect').addEventListener('change', async (e) => {
        setCurrentProjectId(e.target.value);
        if (currentProjectId) {
            const reqs = await fetchRequirements();
            setCurrentRequirements(reqs);
            drawSidebar();
            populateParentChildDropdowns();
        } else {
            document.getElementById('items').innerHTML = '<div class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>';
            document.getElementById('detail').innerHTML = 'Anforderung auswählen';
        }
    });

    // Typ-Änderung im Formular
    document.getElementById('type').addEventListener('change', (e) => {
        renderAttributes(e.target.value, {});
    });

    // Modals & Buttons verknüpfen
    document.getElementById('new').addEventListener('click', openNewReqModal);
    document.getElementById('cancelReq').addEventListener('click', () => {
        document.getElementById('reqModal').classList.add('hidden');
    });
    document.getElementById('reqForm').addEventListener('submit', saveRequirement);

    // Historie Tab verknüpfen
    document.querySelectorAll('button, a, div').forEach(el => {
        if (el.textContent.trim() === 'Historie') {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                renderHistoryView();
            });
        }
    });
});

async function initProjectsDropdown() {
    const projects = await fetchProjects();
    const select = document.getElementById('projectSelect');
    if (!select) return;

    select.innerHTML = '<option value="">-- Projekt wählen --</option>';
    projects.fEach = projects.forEach(p => {
        select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
    });
}

// Event-Listener für das globale Modal
document.getElementById('new').addEventListener('click', openNewReqModal);
document.getElementById('reqForm').addEventListener('submit', saveRequirement);

// TAB-STEUERUNG (NEU)
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        // Optik der Tabs umschalten
        document.querySelectorAll('.tab').forEach(t => {
            t.classList.remove('active', 'text-blue-900');
            t.classList.add('border-transparent', 'text-slate-600');
        });
        e.target.classList.add('active', 'text-blue-900');
        e.target.classList.remove('border-transparent', 'text-slate-600');
        
        // Panels umschalten
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
        const panelId = e.target.dataset.panel;
        document.getElementById(panelId).classList.add('show');

        // Dynamische Inhalte laden, je nachdem welcher Tab offen ist
        if (!currentProjectId) return;

        if (panelId === 'stakeholders') {
            drawEcosystem(); // Zeichnet das Board mit den Pfeilen neu
        } else if (panelId === 'history') {
            await renderHistoryView(); // Lädt die gesamte Projekt-Historie
        }
    });
});