import { currentRequirements, editingReqId, setEditingReqId, currentProjectId, setCurrentRequirements } from './state.js';
import { ATTRIBUTE_SCHEMAS } from './schemas.js';
import { sendReqApi, fetchRequirements, fetchHistory, fetchReqHistory } from './api.js';
import { drawSidebar, populateParentChildDropdowns } from './tree.js';


export async function showDetail(req) {
    const detailDiv = document.getElementById('detail');

    const renderLinks = (links, title, color) => {
        if (typeof links === 'string') { try { links = JSON.parse(links); } catch (e) { links = []; } }
        if (!Array.isArray(links) || links.length === 0) return '';
        const badges = links.map(k => `<span class="rounded bg-${color}-100 px-2 py-0.5 text-[10px] font-bold text-${color}-800 border border-${color}-200">${k}</span>`).join('');
        return `<div class="mt-3 flex items-center gap-2"><span class="text-xs font-bold text-slate-500 uppercase">${title}:</span> ${badges}</div>`;
    };

    // Titel und Label je nach Typ anpassen
    let mainHeading = "Aktuelle Anforderung";
    let descHeading = "Beschreibung";
    if (req.type === 'STK') { mainHeading = "Stakeholder Details"; descHeading = "Notizen / Hintergrund"; }
    else if (req.type === 'US') { mainHeading = "User Story"; }
    else if (req.type === 'UC') { mainHeading = "Use Case"; }

    // Dynamische Attribute (E-Mail, Rolle, etc.) verarbeiten und rendern
    let attrs = req.attributes || {};
    if (typeof attrs === 'string') { try { attrs = JSON.parse(attrs); } catch (e) { attrs = {}; } }

    let attrHtml = '';
    const schema = ATTRIBUTE_SCHEMAS[req.type] || [];
    if (schema.length > 0) {
        attrHtml = `<div class="mt-4 grid gap-3 md:grid-cols-2">`;
        schema.forEach(([key, label, kind]) => {
            const val = attrs[key] || '<span class="text-slate-400 italic">Keine Angabe</span>';
            attrHtml += `
                <div class="rounded-md border border-slate-200 bg-slate-50/50 p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">${label}</div>
                    <div class="mt-1 text-sm font-medium text-slate-800">${val}</div>
                </div>`;
        });
        attrHtml += `</div>`;
    }

    detailDiv.innerHTML = `
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <b class="font-mono text-sm text-blue-900">${req.req_key}</b>
                        <span class="rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-semibold">${req.type}</span>
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">${req.status || 'open'}</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">${req.title}</h2>
                </div>
                <button id="editReqBtn" class="rounded bg-blue-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-800 transition-colors">
                    Eintrag bearbeiten
                </button>
            </div>
            ${renderLinks(req.parents, 'Verknüpft mit / Erfüllt', 'blue')}
            ${renderLinks(req.children, 'Wird erfüllt durch', 'emerald')}
        </div>
        <section class="mt-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-900">${mainHeading}</h3>
            ${attrHtml}
            <div class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500">${descHeading}</div>
            <p class="mt-1 leading-7 text-slate-800">${req.description || req.text || 'Kein Text'}</p>
            
            ${req.rationale ? `
            <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Begründung (Rationale)</div>
                <p class="mt-1 text-sm text-slate-700">${req.rationale}</p>
            </div>` : ''}
        </section>
        <section id="reqHistorySection" class="mt-8 border-t border-slate-200 pt-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-3">Änderungshistorie</h3>
            <div class="text-xs text-slate-400">Lade Historie...</div>
        </section>
    `;

    document.getElementById('editReqBtn').onclick = () => startEdit(req.id);

    // Historie für DIESE EINE Anforderung laden
    const history = await fetchReqHistory(req.id);
    const historySection = document.getElementById('reqHistorySection');
    if (!historySection) return;

    if (history.length === 0) {
        historySection.innerHTML = `<p class="text-xs text-slate-400 italic">Bisher keine Änderungen vorgenommen.</p>`;
    } else {
        let histHtml = `<div class="space-y-3">`;
        history.forEach((h, index) => {
            const versionNum = history.length - index;
            const isCreation = h.action === 'CREATE';
            const badgeClass = isCreation ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800';
            const badgeText = isCreation ? 'Erstellung' : 'Archivierte Alt-Version';

            histHtml += `
                <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs">
                    <div class="flex justify-between text-slate-500 font-mono mb-1">
                        <span class="font-bold text-slate-700">v${versionNum} (${h.modified_at})</span>
                        <span>User: <strong>${h.modified_by_user || h.modified_by}</strong></span>
                    </div>
                    <div class="font-semibold text-slate-800">Titel: <span class="font-normal">${h.title}</span></div>
                    <div class="mt-2 text-[11px] ${badgeClass} px-2 py-0.5 rounded inline-block border">${badgeText}</div>
                </div>`;
        });
        histHtml += `</div>`;
        historySection.innerHTML = histHtml;
    }
}

export async function renderHistoryView() {
    const history = await fetchHistory();
    // WICHTIG: Das globale Historien-Panel füllen, nicht die Detail-Ansicht!
    const container = document.getElementById('historyContainer');
    if (!container) return;

    let html = `<h2 class="text-xl font-bold text-slate-900 mb-4">Änderungshistorie (Gesamtes Projekt)</h2>`;

    if (!history || history.length === 0) {
        html += `<p class="text-sm text-slate-500 italic">Keine Historie für dieses Projekt vorhanden.</p>`;
    } else {
        html += `<div class="space-y-4">`;
        history.forEach(h => {
            const userName = h.modified_by_user || `ID ${h.modified_by}`;
            const isCreation = h.action === 'CREATE';
            const badgeClass = isCreation ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200';
            const badgeText = isCreation ? 'Neu angelegt' : 'Änderung';

            html += `
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-1 font-mono">
                        <span class="font-bold text-slate-700">${h.req_key} (${h.modified_at})</span>
                        <span>User: <strong>${userName}</strong></span>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mt-1">${h.title}</h3>
                    <p class="text-slate-600 mt-1"><strong>Beschreibung:</strong> ${h.description || ''}</p>
                    <div class="mt-2 text-[11px] ${badgeClass} px-2 py-0.5 rounded inline-block border">${badgeText}</div>
                </div>
            `;
        });
        html += `</div>`;
    }
    container.innerHTML = html;
}

export function startEdit(reqId) {
    if (!confirm("Wollen Sie wirklich die Anforderung ändern? Die alte Version wird archiviert.")) {
        return;
    }

    const req = currentRequirements.find(r => r.id == reqId);
    if (!req) return;

    setEditingReqId(req.id);

    const modal = document.getElementById('reqModal');
    if (modal) modal.classList.remove('hidden');

    document.getElementById('reqHeading').textContent = 'Anforderung bearbeiten (' + req.req_key + ')';
    document.getElementById('title').value = req.title || '';
    document.getElementById('type').value = req.type || 'USR';
    document.getElementById('text').value = req.description || req.text || '';
    document.getElementById('rationale').value = req.rationale || '';

    const statusEl = document.getElementById('status');
    if (statusEl) statusEl.value = req.status || 'open';

    // JSON parsen falls nötig
    let parents = req.parents;
    if (typeof parents === 'string') { try { parents = JSON.parse(parents); } catch (e) { parents = []; } }
    let children = req.children;
    if (typeof children === 'string') { try { children = JSON.parse(children); } catch (e) { children = []; } }

    populateParentChildDropdowns(parents || [], children || []);
    renderAttributes(req.type, req.attributes || {});
}

export function openNewReqModal() {
    if (!currentProjectId) {
        alert("Bitte wähle zuerst ein Projekt aus dem Dropdown aus!");
        return;
    }
    setEditingReqId(null);
    document.getElementById('reqForm').reset();
    document.getElementById('reqHeading').textContent = 'Neue Anforderung';

    // Suchfelder leeren falls vorhanden
    if (document.getElementById('parentSearch')) document.getElementById('parentSearch').value = '';
    if (document.getElementById('childSearch')) document.getElementById('childSearch').value = '';

    populateParentChildDropdowns([], []);
    renderAttributes(document.getElementById('type').value, {});
    document.getElementById('reqModal').classList.remove('hidden');
}

export function renderAttributes(type, values = {}) {
    const fields = ATTRIBUTE_SCHEMAS[type] || [];
    const dynContainer = document.getElementById('dynamicAttributes');
    const fieldContainer = document.getElementById('attributeFields');

    if (fields.length === 0) {
        dynContainer.classList.add('hidden');
        fieldContainer.innerHTML = '';
        return;
    }

    dynContainer.classList.remove('hidden');
    fieldContainer.innerHTML = fields.map(([key, label, kind]) => {
        const val = values[key] || '';

        if (kind === 'textarea') {
            return `<label class="text-sm font-semibold md:col-span-2">${label}<textarea data-attr="${key}" rows="2" class="mt-1 w-full rounded border p-2 font-normal">${val}</textarea></label>`;
        }

        if (kind === 'class') {
            // Spezifische Dropdowns je nach Attribut-Key
            if (key === 'verdict') {
                return `<label class="text-sm font-semibold">${label}<select data-attr="${key}" class="mt-1 w-full rounded border p-2 font-normal">
                    <option value="PASS" ${val === 'PASS' ? 'selected' : ''}>PASS</option>
                    <option value="FAIL" ${val === 'FAIL' ? 'selected' : ''}>FAIL</option>
                    <option value="INCONCLUSIVE" ${val === 'INCONCLUSIVE' ? 'selected' : ''}>Inconclusive</option>
                </select></label>`;
            }
            if (key === 'safety_relevant') {
                return `<label class="text-sm font-semibold">${label}<select data-attr="${key}" class="mt-1 w-full rounded border p-2 font-normal">
                    <option value="Yes" ${val === 'Yes' ? 'selected' : ''}>Ja</option>
                    <option value="No" ${val === 'No' ? 'selected' : ''}>Nein</option>
                </select></label>`;
            }
            if (key === 'influence') {
                return `<label class="text-sm font-semibold">${label}<select data-attr="${key}" class="mt-1 w-full rounded border p-2 font-normal">
                    <option value="Hoch" ${val === 'Hoch' ? 'selected' : ''}>Hoch (Entscheider / Kernanwender)</option>
                    <option value="Mittel" ${val === 'Mittel' ? 'selected' : ''}>Mittel (Beratend / Indirekt betroffen)</option>
                    <option value="Gering" ${val === 'Gering' ? 'selected' : ''}>Gering (Nur zu informieren)</option>
                </select></label>`;
            }
            // Standard Sicherheitsklassen (ASIL / Priorität)
            return `<label class="text-sm font-semibold">${label}<select data-attr="${key}" class="mt-1 w-full rounded border p-2 font-normal">
                <option value="QM" ${val === 'QM' ? 'selected' : ''}>QM</option>
                <option value="ASIL A" ${val === 'ASIL A' ? 'selected' : ''}>ASIL A</option>
                <option value="ASIL B" ${val === 'ASIL B' ? 'selected' : ''}>ASIL B</option>
                <option value="ASIL C" ${val === 'ASIL C' ? 'selected' : ''}>ASIL C</option>
                <option value="ASIL D" ${val === 'ASIL D' ? 'selected' : ''}>ASIL D</option>
            </select></label>`;
        }

        return `<label class="text-sm font-semibold">${label}<input data-attr="${key}" value="${val}" class="mt-1 w-full rounded border p-2 font-normal"></label>`;
    }).join('');
}

export async function saveRequirement(e) {
    e.preventDefault();
    const btn = e.submitter;
    btn.disabled = true;
    btn.textContent = 'Speichert...';

    const attrs = {};
    document.querySelectorAll('[data-attr]').forEach(el => {
        attrs[el.dataset.attr] = el.value;
    });

    const getCheckedValues = containerId => {
        const container = document.getElementById(containerId);
        if (!container) return [];
        return Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
            .map(cb => cb.value);
    };

    const payload = {
        project_id: currentProjectId,
        type: document.getElementById('type').value,
        title: document.getElementById('title').value,
        text: document.getElementById('text').value,
        rationale: document.getElementById('rationale').value,
        attributes: attrs,
        parents: getCheckedValues('parentsCheckboxList'),
        children: getCheckedValues('childrenCheckboxList')
    };

    let apiUrl = '../api/web_create_req.php';
    if (editingReqId !== null && editingReqId !== undefined) {
        payload.id = editingReqId;
        apiUrl = '../api/web_update_req.php';
    }

    try {
        const result = await sendReqApi(apiUrl, payload);
        if (result.success) {
            document.getElementById('reqModal').classList.add('hidden');
            setEditingReqId(null);

            const freshReqs = await fetchRequirements();
            setCurrentRequirements(freshReqs);

            drawSidebar();
            populateParentChildDropdowns();

            if (editingReqId && result.id) {
                const updatedReq = freshReqs.find(r => r.id == result.id);
                if (updatedReq) showDetail(updatedReq);
            }
        } else {
            alert("Fehler beim Speichern: " + (result.error || 'Unbekannter Fehler'));
        }
    } catch (err) {
        console.error(err);
        alert("Netzwerkfehler beim Speichern.");
    } finally {
        btn.disabled = false;
        btn.textContent = 'Speichern';
    }
}






