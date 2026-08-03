// dashboard/js/requirements.js
import { currentProjectId } from './state.js';

let loadedRequirements = [];

export async function loadRequirements() {
    if (!currentProjectId) return;

    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();

        const listContainer = document.getElementById('items');

        if (!data.success || data.requirements.length === 0) {
            listContainer.innerHTML = '<div class="p-4 text-sm text-slate-500 italic">Noch keine Anforderungen vorhanden.</div>';
            loadedRequirements = [];
            document.getElementById('detail').innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Anforderung auswählen</div>';
            return;
        }

        loadedRequirements = data.requirements;

        // Parents für den Baum parsen
        loadedRequirements.forEach(req => {
            let p = req.parents;
            if (typeof p === 'string') {
                try { p = JSON.parse(p); } catch (e) { p = []; }
            }
            req.parsedParents = Array.isArray(p) ? p : [];
        });

        listContainer.innerHTML = '';
        const rendered = new Set();

        function renderNode(req, level) {
            if (rendered.has(req.req_key)) return;
            rendered.add(req.req_key);

            const btn = document.createElement('button');
            const indentRem = level * 1.2;
            const bgClass = level > 0 ? 'bg-slate-50/60' : 'bg-white';

            btn.className = `w-full text-left p-2.5 border-b border-slate-100 hover:bg-blue-50 transition focus:bg-blue-100 flex items-center justify-between text-xs ${bgClass}`;
            btn.style.paddingLeft = `calc(0.75rem + ${indentRem}rem)`;

            btn.innerHTML = `
                <div>
                    <span class="font-mono font-bold text-blue-950 mr-1">${req.req_key}</span>
                    <span class="text-slate-700 truncate">${req.title}</span>
                </div>
                <span class="text-[9px] bg-slate-200 text-slate-600 px-1 py-0.5 rounded font-mono">${req.status}</span>
            `;
            btn.onclick = () => showRequirementDetail(req);
            listContainer.appendChild(btn);

            // Kinder finden und rekursiv einrücken
            const children = loadedRequirements.filter(r => r.parsedParents.includes(req.req_key));
            children.forEach(child => renderNode(child, level + 1));
        }

        // Wurzel-Elemente (Roots) ermitteln
        const roots = loadedRequirements.filter(req =>
            req.parsedParents.length === 0 ||
            !req.parsedParents.some(pk => loadedRequirements.find(r => r.req_key === pk))
        );

        roots.forEach(root => renderNode(root, 0));

        // Falls Reste übrig sind
        loadedRequirements.forEach(req => {
            if (!rendered.has(req.req_key)) renderNode(req, 0);
        });

    } catch (e) {
        console.error("Fehler beim Laden der Anforderungen:", e);
    }
}

function showRequirementDetail(req) {
    const detail = document.getElementById('detail');

    // Akzeptanzkriterien in Checkboxen parsen (Zeile für Zeile)
    let criteriaHtml = '<span class="italic text-slate-400">Keine Akzeptanzkriterien definiert.</span>';
    if (req.acceptance_criteria) {
        const lines = req.acceptance_criteria.split('\n');
        criteriaHtml = '<ul class="space-y-2 mt-2">';
        lines.forEach((line, idx) => {
            const cleanLine = line.replace(/^-\s*/, '');
            if (cleanLine.trim() !== '') {
                criteriaHtml += `
                    <li class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" id="crit_${req.id}_${idx}" class="w-4 h-4 rounded text-blue-900 focus:ring-blue-500">
                        <label for="crit_${req.id}_${idx}" class="cursor-pointer">${cleanLine}</label>
                    </li>
                `;
            }
        });
        criteriaHtml += '</ul>';
    }

    detail.innerHTML = `
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-mono text-xs font-bold bg-slate-100 px-2 py-1 rounded text-blue-900 border">${req.type}</span>
                    <span class="font-mono text-sm text-blue-900 font-bold ml-2">${req.req_key}</span>
                    <h2 class="text-2xl font-bold text-slate-900 mt-1">${req.title}</h2>
                </div>
                <button onclick="window.editRequirement(${req.id})" class="bg-blue-900 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-blue-800">Bearbeiten</button>
            </div>
            <div class="flex gap-4 mt-3 text-xs text-slate-500 font-medium">
                <div>👤 Quelle: <strong class="text-slate-700">${req.source_contact || 'Nicht angegeben'}</strong></div>
                <div>⏱️ Aufwand: <strong class="text-slate-700">${req.effort || 'Offen'}</strong></div>
                <div>📌 Status: <span class="bg-blue-50 text-blue-900 border border-blue-200 px-2 py-0.5 rounded font-bold">${req.review_status || 'Neu'}</span></div>
            </div>
        </div>
                 
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Beschreibung</h3>
        <p class="text-sm text-slate-800 whitespace-pre-wrap mb-6">${req.description || '<span class="italic text-slate-400">Keine Beschreibung</span>'}</p>                  

        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Begründung (Rationale)</h3>
        <div class="bg-slate-50 border p-3 rounded text-sm text-slate-700 whitespace-pre-wrap mb-6">${req.rationale || '-'}</div>

        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Akzeptanzkriterien</h3>
        <div class="bg-slate-50 border p-4 rounded">${criteriaHtml}</div>
    `;
}

// Füge diese Hilfsfunktion irgendwo oben in der Datei ein:
async function loadStakeholdersForDropdown(selectedValue = '') {
    if (!currentProjectId) return;
    const res = await fetch(`../api/get_stakeholders.php?project_id=${currentProjectId}`);
    const data = await res.json();
    const sel = document.getElementById('source_contact');
    sel.innerHTML = '<option value="">-- Kein Stakeholder --</option>';
    if (data.success && data.stakeholders) {
        data.stakeholders.forEach(s => {
            const selected = s.id == selectedValue ? 'selected' : '';
            sel.innerHTML += `<option value="${s.id}" ${selected}>${s.name} (${s.role || 'Stakeholder'})</option>`;
        });
    }
}

export function initRequirementEvents() {
    const newBtn = document.getElementById('new');
    if (newBtn) {
        newBtn.addEventListener('click', () => {
            if (!currentProjectId) { alert("Projekt wählen!"); return; }
            document.getElementById('reqForm').reset();
            document.getElementById('reqForm').dataset.editId = '';
            document.getElementById('reqHeading').textContent = 'Neues Ziel / Anforderung';
            document.getElementById('review_status').value = 'Neu';

            populateRelationshipCheckboxes();
            loadStakeholdersForDropdown(); // Stakeholder laden

            document.getElementById('reqModal').classList.remove('hidden');
        });
    }

    const form = document.getElementById('reqForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const selectedParents = Array.from(document.querySelectorAll('.req-parent-cb:checked')).map(cb => cb.value);
            const selectedChildren = Array.from(document.querySelectorAll('.req-child-cb:checked')).map(cb => cb.value);

            const payload = {
                id: document.getElementById('reqForm').dataset.editId,
                project_id: currentProjectId,
                type: document.getElementById('type').value,
                title: document.getElementById('title').value,
                description: document.getElementById('text').value,
                rationale: document.getElementById('rationale').value,
                source_contact: document.getElementById('source_contact').value,
                effort: document.getElementById('effort').value,
                acceptance_criteria: document.getElementById('acceptance_criteria').value,
                review_status: document.getElementById('review_status').value,
                parents: selectedParents,
                children: selectedChildren
            };

            try {
                const res = await fetch('../api/set_requirements.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    document.getElementById('reqModal').classList.add('hidden');
                    loadRequirements();
                } else {
                    alert("Fehler: " + data.error);
                }
            } catch (err) {
                console.error("API Error:", err);
            }
        });
    }
}

window.editRequirement = function (id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (!req) return;
    document.getElementById('reqForm').dataset.editId = req.id;
    document.getElementById('type').value = req.type;
    document.getElementById('title').value = req.title;
    document.getElementById('text').value = req.description || '';
    document.getElementById('rationale').value = req.rationale || '';

    document.getElementById('effort').value = req.effort || '';
    document.getElementById('acceptance_criteria').value = req.acceptance_criteria || '';
    document.getElementById('review_status').value = req.review_status || 'Neu';

    // Stakeholder Dropdown mit dem korrekten Wert vorausfüllen
    loadStakeholdersForDropdown(req.source_contact);

    let parentKeys = [];
    let childKeys = [];
    try { parentKeys = JSON.parse(req.parents || '[]'); } catch (e) { }
    try { childKeys = JSON.parse(req.children || '[]'); } catch (e) { }

    populateRelationshipCheckboxes(req.id, parentKeys, childKeys);

    document.getElementById('parentSearch').value = '';
    document.getElementById('childSearch').value = '';

    document.getElementById('reqHeading').textContent = 'Eintrag bearbeiten (' + req.req_key + ')';
    document.getElementById('reqModal').classList.remove('hidden');
};

window.editRequirement = function (id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (!req) return;

    document.getElementById('reqForm').dataset.editId = req.id;
    document.getElementById('type').value = req.type;
    document.getElementById('title').value = req.title;
    document.getElementById('text').value = req.description || '';
    document.getElementById('rationale').value = req.rationale || '';

    // NEUE FELDER LADEN
    document.getElementById('source_contact').value = req.source_contact || '';
    document.getElementById('effort').value = req.effort || '';
    document.getElementById('acceptance_criteria').value = req.acceptance_criteria || '';
    document.getElementById('review_status').value = req.review_status || 'Neu';

    document.getElementById('reqHeading').textContent = 'Anforderung bearbeiten (' + req.req_key + ')';
    document.getElementById('reqModal').classList.remove('hidden');
};

window.editRequirement = function (id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (!req) return;

    document.getElementById('reqForm').dataset.editId = req.id;
    document.getElementById('type').value = req.type;
    document.getElementById('title').value = req.title;
    document.getElementById('text').value = req.description;
    document.getElementById('rationale').value = req.rationale || '';

    document.getElementById('reqHeading').textContent = 'Anforderung bearbeiten (' + req.req_key + ')';
    document.getElementById('reqModal').classList.remove('hidden');
};