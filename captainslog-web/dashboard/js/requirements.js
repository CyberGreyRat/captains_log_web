// dashboard/js/requirements.js
import { currentProjectId } from './state.js';
import { renderHistory } from './history.js';

let loadedRequirements = [];
let globalStakeholders = [];

// NEU: Globale Variable für die aktiven Checkbox-Filter
window.activeTypeFilters = []; 

// Wird vom HTML-Filter-Bereich aus aufgerufen
window.updateTypeFilters = function() {
    const container = document.getElementById('reqFilterCheckboxes');
    if (!container) return;

    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    
    // Holt sich alle angehakten Values in ein Array (z.B. ['SRS', 'TC', 'GOAL'])
    window.activeTypeFilters = Array.from(checkboxes)
                                    .filter(cb => cb.checked)
                                    .map(cb => cb.value);
    
    window.renderTreeList();
};

window.selectAllFilters = function(state) {
    const container = document.getElementById('reqFilterCheckboxes');
    if (!container) return;
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = state);
    window.updateTypeFilters();
}

window.filterCheckboxes = function (inputId, listId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    if (!input || !list) return;
    const filter = input.value.toLowerCase();
    const items = list.querySelectorAll('.checkbox-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(filter) ? 'flex' : 'none';
    });
};

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
                <input type="checkbox" id="parent_${req.req_key}" value="${req.req_key}" class="req-parent-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isParent}>
                <label for="parent_${req.req_key}" class="cursor-pointer truncate w-full text-slate-700" title="${labelStr}">${labelStr}</label>
            </div>
        `;
        childList.innerHTML += `
            <div class="checkbox-item flex items-center gap-2 p-1 hover:bg-slate-50">
                <input type="checkbox" id="child_${req.req_key}" value="${req.req_key}" class="req-child-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChild}>
                <label for="child_${req.req_key}" class="cursor-pointer truncate w-full text-slate-700" title="${labelStr}">${labelStr}</label>
            </div>
        `;
    });
}

// CIA Automatik
window.autoSelectCIA = function (strideValue) {
    document.querySelectorAll('.cia-cb').forEach(cb => { cb.checked = false; });
    const map = {
        'Spoofing': 'Authentizität',
        'Tampering': 'Integrität',
        'Repudiation': 'Zurechenbarkeit',
        'Information Disclosure': 'Vertraulichkeit',
        'Denial of Service': 'Verfügbarkeit',
        'Elevation of Privilege': 'Autorisierung'
    };
    const targetGoal = map[strideValue];
    if (targetGoal) {
        document.querySelectorAll('.cia-cb').forEach(cb => {
            if (cb.value === targetGoal) cb.checked = true;
        });
    }
};

window.handleTypeChange = function (loadedAttrs = {}) {
    const typeDropdown = document.getElementById('type');
    const container = document.getElementById('dynamicAttributes');
    const fields = document.getElementById('attributeFields');
    if (!typeDropdown || !container || !fields) return;
    const type = typeDropdown.value;

    const criteriaContainer = document.getElementById('criteria_container');
    const needsCriteria = ['USR', 'SYS', 'SEC', 'SRS', 'HRS' ,'SWC'];
    if (criteriaContainer) {
        if (needsCriteria.includes(type)) {
            criteriaContainer.classList.remove('hidden');
        } else {
            criteriaContainer.classList.add('hidden');
            if (document.getElementById('acceptance_criteria')) {
                document.getElementById('acceptance_criteria').value = '';
            }
        }
    }

    if (type === 'AST') {
        fields.innerHTML = `
            <div class="col-span-1">
                <label class="block text-sm font-semibold text-slate-700">Kategorie des Assets
                    <select id="attr_asset_type" class="mt-1 w-full border p-2 font-normal outline-none bg-white">
                        <option value="">-- Wählen --</option>
                        <optgroup label="Digital & IT">
                            <option value="Daten / Informationen" ${loadedAttrs.asset_type === 'Daten / Informationen' ? 'selected' : ''}>Daten / PII / Passwörter</option>
                            <option value="Geheimnis / Key" ${loadedAttrs.asset_type === 'Geheimnis / Key' ? 'selected' : ''}>Zertifikate / Krypto-Keys</option>
                            <option value="Code / Firmware" ${loadedAttrs.asset_type === 'Code / Firmware' ? 'selected' : ''}>Code / Firmware / OS</option>
                            <option value="Service / Funktion" ${loadedAttrs.asset_type === 'Service / Funktion' ? 'selected' : ''}>Service / API / Update-Dienst</option>
                        </optgroup>
                        <optgroup label="Cyber-Physical (Hardware & Mechanik)">
                            <option value="Elektronik / PCB" ${loadedAttrs.asset_type === 'Elektronik / PCB' ? 'selected' : ''}>Elektronik / PCB / Controller</option>
                            <option value="Physisches Gehäuse / Mechanik" ${loadedAttrs.asset_type === 'Physisches Gehäuse / Mechanik' ? 'selected' : ''}>Physisches Gehäuse / Mechanik (z.B. Chassis, Schlösser)</option>
                            <option value="Schnittstelle / HMI" ${loadedAttrs.asset_type === 'Schnittstelle / HMI' ? 'selected' : ''}>Mensch-Maschine-Schnittstelle (z.B. Display, Taster)</option>
                            <option value="Infrastruktur / Befestigung" ${loadedAttrs.asset_type === 'Infrastruktur / Befestigung' ? 'selected' : ''}>Infrastruktur / Befestigung (z.B. Mast, Fundament)</option>
                        </optgroup>
                    </select>
                </label>
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-semibold text-slate-700">Physischer Zugang (Exposition)
                    <select id="attr_asset_exposure" class="mt-1 w-full border p-2 font-normal outline-none bg-white">
                        <option value="">-- Wählen --</option>
                        <option value="Öffentlich zugänglich (Public)" ${loadedAttrs.asset_exposure === 'Öffentlich zugänglich (Public)' ? 'selected' : ''}>Öffentlich zugänglich (Public Space, unbeaufsichtigt)</option>
                        <option value="Eingeschränkter Zugang (Restricted)" ${loadedAttrs.asset_exposure === 'Eingeschränkter Zugang (Restricted)' ? 'selected' : ''}>Eingeschränkter Zugang (z.B. Büro, Bahnhofspersonal)</option>
                        <option value="Streng gesichert (Secure)" ${loadedAttrs.asset_exposure === 'Streng gesichert (Secure)' ? 'selected' : ''}>Streng gesichert (z.B. verschlossener Serverraum)</option>
                        <option value="Isoliert im Gehäuse (Internal)" ${loadedAttrs.asset_exposure === 'Isoliert im Gehäuse (Internal)' ? 'selected' : ''}>Isoliert im Gehäuse (Nur nach Aufbrechen erreichbar)</option>
                    </select>
                </label>
            </div>
        `;
        container.classList.remove('hidden');
    } else if (type === 'RISK') {
        fields.innerHTML = `
            <label class="block text-sm font-semibold text-slate-700">Wahrscheinlichkeit
                <select id="attr_prob" class="mt-1 w-full border p-2 font-normal outline-none bg-white">
                    <option value="">-- Wählen --</option>
                    <option value="Häufig" ${loadedAttrs.initial_probability === 'Häufig' ? 'selected' : ''}>Häufig</option>
                    <option value="Gelegentlich" ${loadedAttrs.initial_probability === 'Gelegentlich' ? 'selected' : ''}>Gelegentlich</option>
                    <option value="Selten" ${loadedAttrs.initial_probability === 'Selten' ? 'selected' : ''}>Selten</option>
                    <option value="Unwahrscheinlich" ${loadedAttrs.initial_probability === 'Unwahrscheinlich' ? 'selected' : ''}>Unwahrscheinlich</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Schadensausmaß
                <select id="attr_sev" class="mt-1 w-full border p-2 font-normal outline-none bg-white">
                    <option value="">-- Wählen --</option>
                    <option value="Kritisch" ${loadedAttrs.initial_severity === 'Kritisch' ? 'selected' : ''}>Kritisch</option>
                    <option value="Marginal" ${loadedAttrs.initial_severity === 'Marginal' ? 'selected' : ''}>Marginal</option>
                    <option value="Vernachlässigbar" ${loadedAttrs.initial_severity === 'Vernachlässigbar' ? 'selected' : ''}>Vernachlässigbar</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Gefahr / Bedrohung
                <input id="attr_hazard" type="text" value="${loadedAttrs.hazard || ''}" placeholder="Was ist die Bedrohung?" class="mt-1 w-full border p-2 font-normal outline-none bg-white">
            </label>
        `;
        container.classList.remove('hidden');
    } else if (type === 'SEC') {
        const ciaArray = loadedAttrs.cia ? loadedAttrs.cia.split(', ') : [];
        const isChecked = (val) => ciaArray.includes(val) ? 'checked' : '';
        fields.innerHTML = `
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Schutzziele (Erweiterte CIA-Triade)</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 bg-white p-2 border">
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Vertraulichkeit" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Vertraulichkeit')}> Vertraulichkeit</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Integrität" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Integrität')}> Integrität</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Verfügbarkeit" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Verfügbarkeit')}> Verfügbarkeit</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Authentizität" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Authentizität')}> Authentizität</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Zurechenbarkeit" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Zurechenbarkeit')}> Zurechenbarkeit</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" value="Autorisierung" class="cia-cb w-4 h-4 text-blue-900 focus:ring-blue-500" ${isChecked('Autorisierung')}> Autorisierung</label>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">STRIDE Kategorie</label>
                <select id="attr_stride" onchange="window.autoSelectCIA(this.value)" class="w-full border p-2 font-normal outline-none bg-white">
                    <option value="">-- Wählen --</option>
                    <option value="Spoofing" ${loadedAttrs.stride === 'Spoofing' ? 'selected' : ''}>Spoofing (Identitätstäuschung)</option>
                    <option value="Tampering" ${loadedAttrs.stride === 'Tampering' ? 'selected' : ''}>Tampering (Datenmanipulation)</option>
                    <option value="Repudiation" ${loadedAttrs.stride === 'Repudiation' ? 'selected' : ''}>Repudiation (Verleugnung von Aktionen)</option>
                    <option value="Information Disclosure" ${loadedAttrs.stride === 'Information Disclosure' ? 'selected' : ''}>Information Disclosure (Informationspreisgabe)</option>
                    <option value="Denial of Service" ${loadedAttrs.stride === 'Denial of Service' ? 'selected' : ''}>Denial of Service (Dienstverweigerung)</option>
                    <option value="Elevation of Privilege" ${loadedAttrs.stride === 'Elevation of Privilege' ? 'selected' : ''}>Elevation of Privilege (Rechteausweitung)</option>
                </select>
            </div>
        `;
        container.classList.remove('hidden');
    } else {
        fields.innerHTML = '';
        container.classList.add('hidden');
    }
};

async function loadStakeholdersForDropdown(selectedValue = '') {
    if (!currentProjectId) return;
    try {
        const res = await fetch(`../api/get_stakeholders.php?project_id=${currentProjectId}`);
        const data = await res.json();
        const sel = document.getElementById('source_contact');
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Niemand zugewiesen --</option>';
        if (data.success && data.stakeholders) {
            globalStakeholders = data.stakeholders;
            data.stakeholders.forEach(s => {
                const selected = s.id == selectedValue ? 'selected' : '';
                sel.innerHTML += `<option value="${s.id}" ${selected}>${s.name} (${s.role || 'Stakeholder'})</option>`;
            });
        }
    } catch (e) {
        console.error("Stakeholder konnten nicht geladen werden:", e);
    }
}

function getStakeholderName(id) {
    if (!id) return 'Nicht angegeben';
    const s = globalStakeholders.find(x => x.id == id);
    return s ? s.name : id;
}

export async function loadRequirements() {
    if (!currentProjectId) return;
    if (globalStakeholders.length === 0) await loadStakeholdersForDropdown();
    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        if (!data.success || data.requirements.length === 0) {
            loadedRequirements = [];
            window.renderTreeList();
            const detail = document.getElementById('detail');
            if (detail) detail.innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Noch keine Elemente vorhanden.</div>';
            return;
        }
        loadedRequirements = data.requirements;
        loadedRequirements.forEach(req => {
            let p = req.parents;
            if (typeof p === 'string') { try { p = JSON.parse(p); } catch (e) { p = []; } }
            req.parsedParents = Array.isArray(p) ? p : [];
        });
        
        // Neu: Initial die Checkboxen auslesen, bevor das erste Mal gerendert wird
        window.updateTypeFilters(); 
        
    } catch (e) {
        console.error("Fehler beim Laden:", e);
    } finally {
        if(typeof window.hideLoader === 'function') window.hideLoader();
    }
}

window.renderTreeList = function () {
    const listContainer = document.getElementById('items');
    if (!listContainer) return;

    if (loadedRequirements.length === 0) {
        listContainer.innerHTML = '<div class="p-4 text-sm text-slate-500 italic">Keine Elemente vorhanden.</div>';
        return;
    }
    
    listContainer.innerHTML = '';
    
    // Wir holen uns nun alle Elemente, deren Typ in den Checkboxen aktiviert ist
    let targetReqs = [];
    if (window.activeTypeFilters.length === 0) {
        // Nichts angehakt = Nichts anzeigen
        listContainer.innerHTML = '<div class="p-4 text-sm text-slate-500 italic text-center">Bitte Filter oben auswählen.</div>';
        return;
    } else {
        // Nur die Typen in der Checkbox-Liste zulassen
        targetReqs = loadedRequirements.filter(r => window.activeTypeFilters.includes(r.type));
    }

    if (targetReqs.length === 0) {
        listContainer.innerHTML = '<div class="p-4 text-sm text-slate-500 italic">Keine Einträge für diese Filter vorhanden.</div>';
        return;
    }

    // Baum-Struktur Rendern (mit Parent-Rettung)
    const rendered = new Set();
    
    // Parent-Rettung: Wenn ein Kind sichtbar sein soll, müssen wir seine Parents zwingend in unsere targetReqs aufnehmen (damit der Baum nicht reißt)
    let needsParentCheck = true;
    while(needsParentCheck) {
        needsParentCheck = false;
        const currentTargetKeys = targetReqs.map(r => r.req_key);
        
        targetReqs.forEach(req => {
            req.parsedParents.forEach(parentId => {
                if(!currentTargetKeys.includes(parentId)) {
                    const missingParent = loadedRequirements.find(r => r.req_key === parentId);
                    if (missingParent) {
                        targetReqs.push(missingParent);
                        needsParentCheck = true; // Nochmal durchlaufen, falls dieser Parent wieder Parents hat
                    }
                }
            });
        });
    }

    // Ab hier rendern wir den sauberen, geretteten Baum
    function renderNode(req, level) {
        if (rendered.has(req.req_key)) return;
        rendered.add(req.req_key);
        
        const btn = document.createElement('button');
        const indentRem = level * 1.2;
        const bgClass = level > 0 ? 'bg-slate-50/60' : 'bg-white';
        
        btn.className = `w-full text-left p-2.5 border-b border-slate-100 hover:bg-blue-50 transition focus:bg-blue-100 flex items-center justify-between text-xs ${bgClass}`;
        btn.style.paddingLeft = `calc(0.75rem + ${indentRem}rem)`;
        
        const children = targetReqs.filter(r => r.parsedParents.includes(req.req_key));
        const icon = children.length > 0 ? `<span class="text-slate-400 mr-1">▶</span>` : `<span class="mr-3"></span>`;
        
        btn.innerHTML = `
            <div class="flex items-center truncate">
                ${icon}
                <span class="font-mono font-bold text-blue-950 mr-1">${req.req_key}</span>
                <span class="text-slate-700 truncate">${req.title}</span>
            </div>
            <span class="text-[9px] bg-slate-200 text-slate-600 px-1 py-0.5 font-mono shrink-0 rounded">${req.review_status || req.status || 'Neu'}</span>
        `;
        btn.onclick = () => showRequirementDetail(req);
        listContainer.appendChild(btn);
        
        children.forEach(child => renderNode(child, level + 1));
    }
    
    // Wurzelelemente finden (Die keine Parents innerhalb der sichtbaren Liste haben)
    const roots = targetReqs.filter(req => 
        req.parsedParents.length === 0 || 
        !req.parsedParents.some(pk => targetReqs.find(r => r.req_key === pk))
    );
    
    roots.forEach(root => renderNode(root, 0));
    
    // Sicherheitsnetz für verwaiste Elemente
    targetReqs.forEach(req => { if (!rendered.has(req.req_key)) renderNode(req, 0); });
};

window.showRequirementDetailById = function (id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (req) showRequirementDetail(req);
};

window.triggerVerify = function (reqId, idx, checkbox) {
    if (!checkbox.checked) return;
    checkbox.checked = false;
    const textNode = checkbox.nextElementSibling.querySelector('label').textContent;
    document.getElementById('verify_req_id').value = reqId;
    document.getElementById('verify_crit_idx').value = idx;
    document.getElementById('verify_crit_text').textContent = textNode;
    document.getElementById('verify_note').value = '';
    document.getElementById('verifyModal').classList.remove('hidden');
};

function showRequirementDetail(req) {
    const detail = document.getElementById('detail');
    if (!detail) return;

    let attrs = {};
    try { attrs = JSON.parse(req.attributes || '{}'); } catch (e) { }
    const states = attrs.criteria_states || {};

    let criteriaHtml = '<span class="italic text-slate-400">Keine Kriterien definiert.</span>';
    if (req.acceptance_criteria) {
        const lines = req.acceptance_criteria.split('\n');
        criteriaHtml = '<ul class="space-y-3 mt-2">';
        lines.forEach((line, idx) => {
            const cleanLine = line.replace(/^-\s*/, '');
            if (cleanLine.trim() !== '') {
                const state = states[idx];
                const isChecked = state && state.checked ? 'checked disabled' : '';
                const infoBadge = state ? `<div class="mt-1 text-[11px] bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-1 "> <b>Geprüft von ${state.by}</b> am ${state.date}<br><span class="italic">"${state.note}"</span></div>` : '';

                criteriaHtml += `
                    <li class="flex items-start gap-3 text-sm text-slate-700 bg-white p-2 border">
                        <input type="checkbox" id="crit_${req.id}_${idx}" class="mt-1 w-4 h-4 text-blue-900 focus:ring-blue-500 cursor-pointer" ${isChecked} onchange="window.triggerVerify(${req.id}, ${idx}, this)">
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

    const parentLinks = req.parsedParents.map(pk => `<span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 text-[10px] font-mono">${pk}</span>`).join(' ') || '-';

    let childKeys = [];
    try { childKeys = JSON.parse(req.children || '[]'); } catch (e) { }
    const childLinks = childKeys.map(ck => `<span class="bg-emerald-100 text-emerald-800 px-1.5 py-0.5 text-[10px] font-mono">${ck}</span>`).join(' ') || '-';

    let dynamicAttrHtml = '';
    if (req.type === 'AST') {
        dynamicAttrHtml = `
            <div class="flex gap-4 mt-3 pt-3 border-t text-xs text-emerald-700 font-medium bg-emerald-50 p-2 border border-emerald-100">
                <div> Asset-Kategorie: <b>${attrs.asset_type || '-'}</b></div>
                <div> Exposition: <b>${attrs.asset_exposure || '-'}</b></div>
            </div>
        `;
    } else if (req.type === 'RISK') {
        dynamicAttrHtml = `
            <div class="flex gap-4 mt-3 pt-3 border-t text-xs text-red-700 font-medium bg-red-50 p-2 border border-red-100">
                <div> Wahrscheinlichkeit: <b>${attrs.initial_probability || '-'}</b></div>
                <div> Schaden: <b>${attrs.initial_severity || '-'}</b></div>
                <div> Gefahr: <b>${attrs.hazard || '-'}</b></div>
            </div>
        `;
    } else if (req.type === 'SEC') {
        dynamicAttrHtml = `
            <div class="flex gap-4 mt-3 pt-3 border-t text-xs text-indigo-700 font-medium bg-indigo-50 p-2 border border-indigo-100">
                <div> Schutzziele: <b>${attrs.cia || '-'}</b></div>
                <div> STRIDE: <b>${attrs.stride || '-'}</b></div>
            </div>
        `;
    }

    const stakeholderName = getStakeholderName(req.source_contact);

    detail.innerHTML = `
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-mono text-xs font-bold bg-slate-100 px-2 py-1 text-blue-900 border">${req.type}</span>
                    <span class="font-mono text-sm text-blue-900 font-bold ml-2">${req.req_key}</span>
                    <h2 class="text-2xl font-bold text-slate-900 mt-1">${req.title}</h2>
                </div>
                <button onclick="window.editRequirement(${req.id})" class="bg-blue-900 text-white text-xs px-3 py-1.5 font-bold hover:bg-blue-800 shadow">Bearbeiten</button>
            </div>
            <div class="flex gap-4 mt-3 text-xs text-slate-500 font-medium flex-wrap">
                <div> Quelle: <strong class="text-slate-700">${stakeholderName}</strong></div>
                <div> Aufwand: <strong class="text-slate-700">${req.effort || 'Offen'}</strong></div>
                <div> Status: <span class="bg-blue-50 text-blue-900 border border-blue-200 px-2 py-0.5 font-bold">${req.review_status || 'Neu'}</span></div>
            </div>
            ${dynamicAttrHtml}
            <div class="flex gap-4 mt-3 pt-3 border-t text-xs text-slate-500 font-medium">
                <div>Erfüllt (Parents): ${parentLinks}</div>
                <div>Wird erfüllt durch (Children): ${childLinks}</div>
            </div>
        </div>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Beschreibung</h3>
        <p class="text-sm text-slate-800 whitespace-pre-wrap mb-6">${req.description || '<span class="italic text-slate-400">Keine Beschreibung</span>'}</p>
        
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Begründung (Rationale)</h3>
        <div class="bg-slate-50 border p-3 text-sm text-slate-700 whitespace-pre-wrap mb-6">${req.rationale || '-'}</div>
        
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Prüfungen & Kriterien</h3>
        <div class="bg-slate-50 border p-4 ">${criteriaHtml}</div>
    `;

    renderHistory(req.req_key);
}

export function initRequirementEvents() {
    
    // NEU: Event Listener für die Checkboxen aktivieren
    const filterContainer = document.getElementById('reqFilterCheckboxes');
    if (filterContainer) {
        filterContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', () => window.updateTypeFilters());
        });
    }

    const typeDropdown = document.getElementById('type');
    if (typeDropdown) {
        typeDropdown.addEventListener('change', () => window.handleTypeChange());
    }

    const newBtn = document.getElementById('new');
    if (newBtn) {
        newBtn.addEventListener('click', () => {
            if (!currentProjectId) { alert("Projekt wählen!"); return; }
            document.getElementById('reqForm').reset();
            document.getElementById('reqForm').dataset.editId = '';
            document.getElementById('reqHeading').textContent = 'Neues Element anlegen';

            const revStat = document.getElementById('review_status');
            if (revStat) revStat.value = 'Neu';

            window.handleTypeChange();
            populateRelationshipCheckboxes();
            loadStakeholdersForDropdown();

            const modal = document.getElementById('reqModal');
            if (modal) modal.classList.remove('hidden');
        });
    }

    const form = document.getElementById('reqForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const typeValue = document.getElementById('type') ? document.getElementById('type').value : '';

            const needsCriteria = ['USR', 'SYS', 'SEC', 'SRS', 'HRS', 'SWC'];
            if (needsCriteria.includes(typeValue)) {
                const criteriaText = document.getElementById('acceptance_criteria') ? document.getElementById('acceptance_criteria').value.trim() : '';
                if (!criteriaText) {
                    alert(`Fehler: Anforderungen vom Typ ${typeValue} benötigen zwingend Akzeptanzkriterien!`);
                    return;
                }
            }

            const selectedParents = Array.from(document.querySelectorAll('.req-parent-cb:checked')).map(cb => cb.value);
            const selectedChildren = Array.from(document.querySelectorAll('.req-child-cb:checked')).map(cb => cb.value);

            let dynamicAttrs = {};
            if (typeValue === 'AST') {
                dynamicAttrs.asset_type = document.getElementById('attr_asset_type') ? document.getElementById('attr_asset_type').value : '';
                dynamicAttrs.asset_exposure = document.getElementById('attr_asset_exposure') ? document.getElementById('attr_asset_exposure').value : '';
            } else if (typeValue === 'RISK') {
                dynamicAttrs.initial_probability = document.getElementById('attr_prob') ? document.getElementById('attr_prob').value : '';
                dynamicAttrs.initial_severity = document.getElementById('attr_sev') ? document.getElementById('attr_sev').value : '';
                dynamicAttrs.hazard = document.getElementById('attr_hazard') ? document.getElementById('attr_hazard').value : '';
            } else if (typeValue === 'SEC') {
                const checkedCIA = Array.from(document.querySelectorAll('.cia-cb:checked')).map(cb => cb.value);
                dynamicAttrs.cia = checkedCIA.join(', ');
                dynamicAttrs.stride = document.getElementById('attr_stride') ? document.getElementById('attr_stride').value : '';
            }

            const payload = {
                id: document.getElementById('reqForm').dataset.editId,
                project_id: currentProjectId,
                type: typeValue,
                title: document.getElementById('title') ? document.getElementById('title').value : '',
                description: document.getElementById('text') ? document.getElementById('text').value : '',
                rationale: document.getElementById('rationale') ? document.getElementById('rationale').value : '',
                source_contact: document.getElementById('source_contact') ? document.getElementById('source_contact').value : '',
                effort: document.getElementById('effort') ? document.getElementById('effort').value : '',
                acceptance_criteria: document.getElementById('acceptance_criteria') ? document.getElementById('acceptance_criteria').value : '',
                review_status: document.getElementById('review_status') ? document.getElementById('review_status').value : '',
                parents: selectedParents,
                children: selectedChildren,
                attributes: dynamicAttrs
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
                    await loadRequirements();
                    const updatedId = payload.id || data.id;
                    if (updatedId) {
                        window.showRequirementDetailById(updatedId);
                    }
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
    if (document.getElementById('type')) document.getElementById('type').value = req.type;
    if (document.getElementById('title')) document.getElementById('title').value = req.title;
    if (document.getElementById('text')) document.getElementById('text').value = req.description || '';
    if (document.getElementById('rationale')) document.getElementById('rationale').value = req.rationale || '';
    if (document.getElementById('effort')) document.getElementById('effort').value = req.effort || '';
    if (document.getElementById('acceptance_criteria')) document.getElementById('acceptance_criteria').value = req.acceptance_criteria || '';
    if (document.getElementById('review_status')) document.getElementById('review_status').value = req.review_status || 'Neu';

    let attrs = {};
    try { attrs = JSON.parse(req.attributes || '{}'); } catch (e) { }
    window.handleTypeChange(attrs);

    loadStakeholdersForDropdown(req.source_contact);

    let parentKeys = []; let childKeys = [];
    try { parentKeys = JSON.parse(req.parents || '[]'); } catch (e) { }
    try { childKeys = JSON.parse(req.children || '[]'); } catch (e) { }

    populateRelationshipCheckboxes(req.id, parentKeys, childKeys);
    if (document.getElementById('parentSearch')) document.getElementById('parentSearch').value = '';
    if (document.getElementById('childSearch')) document.getElementById('childSearch').value = '';

    const heading = document.getElementById('reqHeading');
    if (heading) heading.textContent = 'Eintrag bearbeiten (' + req.req_key + ')';

    const modal = document.getElementById('reqModal');
    if (modal) modal.classList.remove('hidden');
};