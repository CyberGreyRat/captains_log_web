// dashboard/js/iso14001.js
import { currentProjectId } from './state.js';

let loadedEnvs = [];

export async function loadIsoData() {
    const tbody = document.getElementById('isoTableBody');
    if (!tbody) return;

    if (!currentProjectId) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-400 font-bold bg-slate-50">Bitte wähle oben ein Projekt aus.</td></tr>';
        return;
    }

    // Lade-Indikator anzeigen
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="p-8 text-center text-slate-500 italic">
                <div class="inline-flex items-center gap-2">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Daten werden geladen...
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-red-500">Fehler beim Laden der Umweltdaten.</td></tr>`;
            return;
        }

        loadedEnvs = (data.requirements || []).filter(r => r.type === 'ENV');

        if (loadedEnvs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Noch keine Umweltaspekte in diesem Projekt definiert.</td></tr>';
            return;
        }

        const groupedEnvs = {};
        loadedEnvs.forEach(env => {
            let attrs = {};
            try { attrs = JSON.parse(env.attributes || '{}'); } catch(e){}
            const phase = attrs.phase || 'Sonstiges';
            if (!groupedEnvs[phase]) groupedEnvs[phase] = [];
            groupedEnvs[phase].push(env);
        });

        const phaseOrder = ['Entwurf', 'Entwicklung', 'Rohstoffe', 'Produktion', 'Lieferung', 'Installation/Wartung', 'Betrieb', 'EOL', 'Sonstiges'];
        Object.keys(groupedEnvs).forEach(p => {
            if (!phaseOrder.includes(p)) phaseOrder.push(p);
        });

        const iconEdit = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>`;
        const iconTrash = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;

        let html = '';
        phaseOrder.forEach(phase => {
            if (!groupedEnvs[phase] || groupedEnvs[phase].length === 0) return;

            html += `
                <tr class="bg-blue-900 text-white border-b border-blue-950">
                    <td colspan="5" class="px-4 py-1.5 text-xs font-bold uppercase tracking-widest shadow-inner">${phase}</td>
                </tr>
            `;

            groupedEnvs[phase].forEach(env => {
                let attrs = {};
                try { attrs = JSON.parse(env.attributes || '{}'); } catch(e){}
                const relevance = attrs.relevance || 'Mittel';
                
                let relBadge = `<span class="bg-amber-100 text-amber-800 border border-amber-200 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;
                if(relevance === 'Signifikant') relBadge = `<span class="bg-red-100 text-red-800 border border-red-200 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;
                if(relevance === 'Gering') relBadge = `<span class="bg-slate-100 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wider font-bold shadow-sm">${relevance}</span>`;

                const statusColor = env.review_status === 'Geprüft & Freigegeben' ? 'text-emerald-600 font-extrabold' : 'text-slate-500 font-semibold';

                html += `
                    <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                        <td class="p-2 align-top font-bold text-emerald-900">
                            <div class="text-[10px] font-mono text-emerald-600 mb-0.5 leading-none">${env.req_key}</div>
                            <div class="text-xs leading-snug">${env.title}</div>
                        </td>
                        <td class="p-2 align-top">
                            <div class="text-[11px] text-slate-700 leading-tight mb-1"><span class="font-bold text-red-800/70 uppercase tracking-wider text-[9px] mr-1">Wirkung:</span>${env.description || '-'}</div>
                            <div class="text-[11px] text-slate-600 leading-tight"><span class="font-bold text-emerald-700/70 uppercase tracking-wider text-[9px] mr-1">Maßnahme:</span>${env.rationale || '-'}</div>
                        </td>
                        <td class="p-2 text-center align-top pt-3">${relBadge}</td>
                        <td class="p-2 text-center text-xs ${statusColor} align-top pt-3">${env.review_status || 'Neu'}</td>
                        <td class="p-2 text-right align-top pt-2">
                            <div class="flex justify-end gap-1">
                                <button onclick="window.openIsoModal(${env.id})" class="text-slate-400 hover:text-emerald-600 transition p-1.5 hover:bg-emerald-100 rounded" title="Bearbeiten">${iconEdit}</button>
                                <button onclick="window.deleteIso(${env.id})" class="text-slate-400 hover:text-red-600 transition p-1.5 hover:bg-red-50 rounded" title="Löschen">${iconTrash}</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        });

        tbody.innerHTML = html;

    } catch (e) {
        console.error("Fehler beim Laden der ISO 14001 Daten:", e);
        tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500">Netzwerkfehler beim Laden.</td></tr>';
    }
}

export function initIsoEvents() {
    // Lädt die Excel-Vorlagen direkt beim Start ins Dropdown
    loadIsoTemplates();

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
            if (!sourceId) return;

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

// --- NEU: LÄDT DIE EXCEL-VORLAGEN UND FÜLLT DAS FORMULAR AUS ---
let isoTemplates = [];
async function loadIsoTemplates() {
    try {
        const res = await fetch('../api/get_iso_templates.php');
        const data = await res.json();

        if (data.success) {
            isoTemplates = data.templates;
            const select = document.getElementById('iso_template_selector');
            if (!select) return;

            // Gruppiere die Einträge nach Kategorie (z.B. Gehäuse, Leiterplatte)
            const grouped = {};
            isoTemplates.forEach(t => {
                if (!grouped[t.category]) grouped[t.category] = [];
                grouped[t.category].push(t);
            });

            // Fülle das Dropdown mit Optgroups
            for (const cat in grouped) {
                let optgroup = `<optgroup label="Kategorie: ${cat}">`;
                grouped[cat].forEach(t => {
                    optgroup += `<option value="${t.id}">[${t.phase}] ${t.title}</option>`;
                });
                optgroup += `</optgroup>`;
                select.innerHTML += optgroup;
            }

            // AUTO-FILL LOGIK: Wenn ein Element ausgewählt wird, füllt sich das Formular!
            select.addEventListener('change', (e) => {
                const tpl = isoTemplates.find(t => t.id == e.target.value);
                if (tpl) {
                    document.getElementById('iso_phase').value = tpl.phase || 'Produktion';
                    document.getElementById('iso_title').value = tpl.title || '';
                    document.getElementById('iso_measure').value = tpl.description || '';
                    document.getElementById('iso_impact').value = 'Mögliche Umweltauswirkung in dieser Phase minimieren.'; // Standardtext
                }
            });
        }
    } catch (e) {
        console.error("Fehler beim Laden der Templates:", e);
    }
}

// Global: Modal für neuen/bestehenden Aspekt öffnen
window.openIsoModal = function (id = null) {
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
            try { attrs = JSON.parse(env.attributes || '{}'); } catch (e) { }
            if (attrs.phase) document.getElementById('iso_phase').value = attrs.phase;
            if (attrs.relevance) document.getElementById('iso_relevance').value = attrs.relevance;
        }
    }
    document.getElementById('modalIsoEdit').classList.remove('hidden');
};

// Global: Import Modal öffnen und Projektliste laden
window.openIsoImportModal = async function () {
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

// Global: Umweltaspekt löschen
window.deleteIso = async function (id) {
    if (!confirm("Bist du sicher? Dieser Umweltaspekt wird unwiderruflich gelöscht.")) return;

    try {
        const res = await fetch('../api/delete_requirement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const data = await res.json();
        if (data.success) {
            loadIsoData(); // Tabelle neu laden
        } else {
            alert("Fehler beim Löschen: " + data.error);
        }
    } catch (e) {
        console.error("Löschen fehlgeschlagen:", e);
    }
};