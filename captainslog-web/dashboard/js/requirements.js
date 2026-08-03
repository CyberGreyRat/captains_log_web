// dashboard/js/requirements.js
import { currentProjectId } from './state.js';

let loadedRequirements = [];

// Hilfsfunktion: Checkbox-Suche (Parents/Children)
window.filterCheckboxes = function (inputId, listId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const items = document.getElementById(listId).querySelectorAll('.checkbox-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(filter) ? 'flex' : 'none';
    });
};

// Hilfsfunktion: Beziehungen rendern (Parents/Children)
function populateRelationshipCheckboxes(currentReqId = null, selectedParents = [], selectedChildren = []) {
    const parentList = document.getElementById('parentsCheckboxList');
    const childList = document.getElementById('childrenCheckboxList');

    if (!parentList || !childList) return;

    parentList.innerHTML = '';
    childList.innerHTML = '';

    loadedRequirements.forEach(req => {
        if (req.id == currentReqId) return;

        const isParent = selectedParents.includes(req.req_key) ? 'checked' : '';
        const isChild = selectedChildren.includes(req.req_key) ? 'checked' : '';
        const labelStr = `${req.req_key} - ${req.title}`;

        parentList.innerHTML += `
            <div class="checkbox-item flex items-center gap-2 p-1 hover:bg-slate-50">
                <input type="checkbox" id="parent_${req.req_key}" value="${req.req_key}" class="req-parent-cb w-4 h-4 rounded text-blue-900 focus:ring-blue-500" ${isParent}>
                <label for="parent_${req.req_key}" class="cursor-pointer truncate w-full text-slate-700" title="${labelStr}">${labelStr}</label>
            </div>
        `;

        childList.innerHTML += `
            <div class="checkbox-item flex items-center gap-2 p-1 hover:bg-slate-50">
                <input type="checkbox" id="child_${req.req_key}" value="${req.req_key}" class="req-child-cb w-4 h-4 rounded text-blue-900 focus:ring-blue-500" ${isChild}>
                <label for="child_${req.req_key}" class="cursor-pointer truncate w-full text-slate-700" title="${labelStr}">${labelStr}</label>
            </div>
        `;
    });
}

// Hilfsfunktion: Stakeholder für das Dropdown laden
async function loadStakeholdersForDropdown(selectedValue = '') {
    if (!currentProjectId) return;
    const res = await fetch(`../api/get_stakeholders.php?project_id=${currentProjectId}`);
    const data = await res.json();
    const sel = document.getElementById('source_contact');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Kein Stakeholder --</option>';
    if (data.success && data.stakeholders) {
        data.stakeholders.forEach(s => {
            const selected = s.id == selectedValue ? 'selected' : '';
            sel.innerHTML += `<option value="${s.id}" ${selected}>${s.name} (${s.role || 'Stakeholder'})</option>`;
        });
    }
}

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

            const children = loadedRequirements.filter(r => r.parsedParents.includes(req.req_key));
            const icon = children.length > 0 ? `<span class="text-slate-400 mr-1">▼</span>` : `<span class="mr-3"></span>`;

            btn.innerHTML = `
                <div class="flex items-center truncate">
                    ${icon}
                    <span class="font-mono font-bold text-blue-950 mr-1">${req.req_key}</span>
                    <span class="text-slate-700 truncate">${req.title}</span>
                </div>
                <span class="text-[9px] bg-slate-200 text-slate-600 px-1 py-0.5 rounded font-mono shrink-0">${req.review_status || req.status}</span>
            `;
            btn.onclick = () => showRequirementDetail(req);
            listContainer.appendChild(btn);

            children.forEach(child => renderNode(child, level + 1));
        }

        const roots = loadedRequirements.filter(req =>
            req.parsedParents.length === 0 ||
            !req.parsedParents.some(pk => loadedRequirements.find(r => r.req_key === pk))
        );

        roots.forEach(root => renderNode(root, 0));

        loadedRequirements.forEach(req => {
            if (!rendered.has(req.req_key)) renderNode(req, 0);
        });

    } catch (e) {
        console.error("Fehler beim Laden der Anforderungen:", e);
    }
}

window.showRequirementDetailById = function (id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (req) showRequirementDetail(req);
};

window.triggerVerify = function (reqId, idx, text, checkbox) {
    if (!checkbox.checked) return;
    checkbox.checked = false;

    document.getElementById('verify_req_id').value = reqId;
    document.getElementById('verify_crit_idx').value = idx;
    document.getElementById('verify_crit_text').textContent = text;
    document.getElementById('verify_note').value = '';

    document.getElementById('verifyModal').classList.remove('hidden');
};

function showRequirementDetail(req) {
    const detail = document.getElementById('detail');

    let attrs = {};
    try { attrs = JSON.parse(req.attributes || '{}'); } catch (e) { }
    const states = attrs.criteria_states || {};

    let criteriaHtml = '<span class="italic text-slate-400">Keine Akzeptanzkriterien definiert.</span>';
    if (req.acceptance_criteria) {
        const lines = req.acceptance_criteria.split('\n');
        criteriaHtml = '<ul class="space-y-3 mt-2">';
        lines.forEach((line, idx) => {
            const cleanLine = line.replace(/^-\s*/, '');
            if (cleanLine.trim() !== '') {
                const state = states[idx];
                const isChecked = state && state.checked ? 'checked disabled' : '';
                const infoBadge = state ? `<div class="mt-1 text-[11px] bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-1 rounded">✅ <b>Geprüft von ${state.by}</b> am ${state.date}<br><span class="italic">"${state.note}"</span></div>` : '';

                criteriaHtml += `
                    <li class="flex items-start gap-3 text-sm text-slate-700 bg-white p-2 rounded border">
                        <input type="checkbox" id="crit_${req.id}_${idx}" class="mt-1 w-4 h-4 rounded text-blue-900 focus:ring-blue-500 cursor-pointer" ${isChecked} onchange="window.triggerVerify(${req.id}, ${idx}, '${cleanLine.replace(/'/g, "\\'")}', this)">
                        <div class="flex flex-col w-full">
                            <label for="crit_${req.id}_${idx}" class="cursor-pointer font-medium leading-tight">${cleanLine}</label>
                            ${infoBadge}
                        </div>
                    </li>
                `;
            }
        });
        criteriaHtml += '</ul>';
    }

    const parentLinks = req.parsedParents.map(pk => `<span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded text-[10px] font-mono">${pk}</span>`).join(' ') || '-';
    let childKeys = [];
    try { childKeys = JSON.parse(req.children || '[]'); } catch (e) { }
    const childLinks = childKeys.map(ck => `<span class="bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded text-[10px] font-mono">${ck}</span>`).join(' ') || '-';

    detail.innerHTML = `
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-mono text-xs font-bold bg-slate-100 px-2 py-1 rounded text-blue-900 border">${req.type}</span>
                    <span class="font-mono text-sm text-blue-900 font-bold ml-2">${req.req_key}</span>
                    <h2 class="text-2xl font-bold text-slate-900 mt-1">${req.title}</h2>
                </div>
                <button onclick="window.editRequirement(${req.id})" class="bg-blue-900 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-blue-800 shadow">Bearbeiten</button>
            </div>
            <div class="flex gap-4 mt-3 text-xs text-slate-500 font-medium flex-wrap">
                <div>👤 Quelle: <strong class="text-slate-700">${req.source_contact || 'Nicht angegeben'}</strong></div>
                <div>⏱️ Aufwand: <strong class="text-slate-700">${req.effort || 'Offen'}</strong></div>
                <div>📌 Status: <span class="bg-blue-50 text-blue-900 border border-blue-200 px-2 py-0.5 rounded font-bold">${req.review_status || 'Neu'}</span></div>
            </div>
            <div class="flex gap-4 mt-3 pt-3 border-t text-xs text-slate-500 font-medium">
                <div>Erfüllt (Parents): ${parentLinks}</div>
                <div>Wird erfüllt durch (Children): ${childLinks}</div>
            </div>
        </div>
                
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Beschreibung</h3>
        <p class="text-sm text-slate-800 whitespace-pre-wrap mb-6">${req.description || '<span class="italic text-slate-400">Keine Beschreibung</span>'}</p>                  

        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Begründung (Rationale)</h3>
        <div class="bg-slate-50 border p-3 rounded text-sm text-slate-700 whitespace-pre-wrap mb-6">${req.rationale || '-'}</div>

        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Akzeptanzkriterien & Prüfungen</h3>
        <div class="bg-slate-50 border p-4 rounded">${criteriaHtml}</div>
    `;
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
            loadStakeholdersForDropdown();

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

    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
        verifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                req_id: document.getElementById('verify_req_id').value,
                criterion_idx: document.getElementById('verify_crit_idx').value,
                note: document.getElementById('verify_note').value
            };

            try {
                const res = await fetch('../api/verify_criterion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    document.getElementById('verifyModal').classList.add('hidden');
                    await loadRequirements();
                    window.showRequirementDetailById(payload.req_id);
                } else {
                    alert("Fehler beim Prüfen: " + data.error);
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