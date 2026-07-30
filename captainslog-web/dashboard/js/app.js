import { setCurrentProjectId, setCurrentRequirements, currentProjectId } from './state.js';
import { fetchProjects, fetchRequirements } from './api.js';
import { drawSidebar, populateParentChildDropdowns } from './tree.js';
import { openNewReqModal, renderAttributes, saveRequirement, renderHistoryView } from './modals.js';

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