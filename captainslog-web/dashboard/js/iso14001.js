// dashboard/js/iso14001.js
import { currentProjectId } from './state.js';

let loadedEnvs = [];

export async function loadIsoData() {
    if (!currentProjectId) {
        const tbody = document.getElementById('isoTableBody');
        if(tbody) tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Bitte wähle oben ein Projekt aus.</td></tr>';
        return;
    }

    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const tbody = document.getElementById('isoTableBody');
        if (!tbody || !data.success) return;

        // Wir filtern nur unsere Umweltaspekte (ENV) heraus
        loadedEnvs = data.requirements.filter(r => r.type === 'ENV');

        if (loadedEnvs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Noch keine Umweltaspekte in diesem Projekt definiert.</td></tr>';
            return;
        }

        let html = '';
        loadedEnvs.forEach(env => {
            let attrs = {};
            try { attrs = JSON.parse(env.attributes || '{}'); } catch(e){}
            
            const phase = attrs.phase || 'Produktion';
            const relevance = attrs.relevance || 'Mittel';
            
            // Farbige Badges je nach Priorität
            let relBadge = `<span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;
            if(relevance === 'Signifikant') relBadge = `<span class="bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;
            if(relevance === 'Gering') relBadge = `<span class="bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;

            const statusColor = env.review_status === 'Geprüft & Freigegeben' ? 'text-emerald-600 font-extrabold' : 'text-slate-500 font-semibold';

            html += `
                <tr class="hover:bg-emerald-50/50 transition cursor-pointer border-b border-slate-100 group" onclick="window.openIsoModal(${env.id})">
                    <td class="p-3 font-semibold text-slate-700 whitespace-nowrap">${phase}</td>
                    <td class="p-3 font-bold text-emerald-900">
                        <div class="text-[10px] font-mono text-emerald-600 mb-0.5">${env.req_key}</div>
                        ${env.title}
                    </td>
                    <td class="p-3">
                        <div class="text-xs text-slate-800 mb-1"><span class="font-bold text-red-800/70 uppercase tracking-wider text-[10px]">Wirkung:</span> ${env.description || '-'}</div>
                        <div class="text-xs text-slate-600"><span class="font-bold text-emerald-700/70 uppercase tracking-wider text-[10px]">Maßnahme:</span> ${env.rationale || '-'}</div>
                    </td>
                    <td class="p-3 text-center">${relBadge}</td>
                    <td class="p-3 text-center text-xs ${statusColor} group-hover:underline">${env.review_status || 'Neu'}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

    } catch (e) {
        console.error("Fehler beim Laden der ISO 14001 Daten:", e);
    }
}

export function initIsoEvents() {
    // 1. Speichern eines Umweltaspekts (Nutzt das bestehende Requirements-Backend!)
    const formEdit = document.getElementById('formIsoEdit');
    if (formEdit) {
        formEdit.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const payload = {
                id: document.getElementById('iso_id').value,
                project_id: currentProjectId,
                type: 'ENV', // Macht es automatisch zum Umweltaspekt
                title: document.getElementById('iso_title').value,
                description: document.getElementById('iso_impact').value,
                rationale: document.getElementById('iso_measure').value,
                attributes: {
                    phase: document.getElementById('iso_phase').value,
                    relevance: document.getElementById('iso_relevance').value
                }
            };

            const res = await fetch('../api/set_requirements.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            if (data.success) {
                document.getElementById('modalIsoEdit').classList.add('hidden');
                loadIsoData();
            } else {
                alert("Fehler: " + data.error);
            }
        });
    }

    // 2. Import aus altem Projekt
    const formImport = document.getElementById('formIsoImport');
    if (formImport) {
        formImport.addEventListener('submit', async (e) => {
            e.preventDefault();
            const sourceId = document.getElementById('iso_import_source').value;
            if(!sourceId) return;

            const res = await fetch('../api/import_env.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    source_project_id: sourceId,
                    target_project_id: currentProjectId
                })
            });
            
            const data = await res.json();
            if (data.success) {
                document.getElementById('modalIsoImport').classList.add('hidden');
                alert(`Perfekt! ${data.imported} Umweltaspekte wurden in dein aktuelles Projekt kopiert.`);
                loadIsoData();
            } else {
                alert("Import fehlgeschlagen: " + data.error);
            }
        });
    }
}

// Global: Modal für neuen/bestehenden Aspekt öffnen
window.openIsoModal = function(id = null) {
    if (!currentProjectId) { alert("Bitte wähle zuerst oben ein Projekt aus!"); return; }

    const form = document.getElementById('formIsoEdit');
    form.reset();
    document.getElementById('iso_id').value = '';

    if (id) {
        const env = loadedEnvs.find(r => r.id == id);
        if (env) {
            document.getElementById('iso_id').value = env.id;
            document.getElementById('iso_title').value = env.title;
            document.getElementById('iso_impact').value = env.description || '';
            document.getElementById('iso_measure').value = env.rationale || '';
            
            let attrs = {};
            try { attrs = JSON.parse(env.attributes || '{}'); } catch(e){}
            if(attrs.phase) document.getElementById('iso_phase').value = attrs.phase;
            if(attrs.relevance) document.getElementById('iso_relevance').value = attrs.relevance;
        }
    }
    document.getElementById('modalIsoEdit').classList.remove('hidden');
};

// Global: Import Modal öffnen und Projektliste laden
window.openIsoImportModal = async function() {
    if (!currentProjectId) { alert("Bitte wähle zuerst dein aktuelles Ziel-Projekt aus!"); return; }
    
    document.getElementById('modalIsoImport').classList.remove('hidden');
    
    try {
        const res = await fetch('../api/web_get_projects.php');
        const data = await res.json();
        const select = document.getElementById('iso_import_source');
        
        select.innerHTML = '<option value="">-- Quell-Projekt wählen --</option>';
        data.projects.forEach(p => {
            // Aktuelles Projekt aus der Liste filtern (macht keinen Sinn sich selbst zu kopieren)
            if (p.id !== currentProjectId) {
                select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
            }
        });
    } catch (e) {
        console.error("Fehler beim Laden der Projekte:", e);
    }
};