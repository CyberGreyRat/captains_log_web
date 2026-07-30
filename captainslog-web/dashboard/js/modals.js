import { currentRequirements, editingReqId, setEditingReqId, currentProjectId } from './state.js';
import { ATTRIBUTE_SCHEMAS } from './schemas.js';
import { sendReqApi, fetchRequirements, fetchHistory, fetchReqHistory } from './api.js';
import { drawSidebar, populateParentChildDropdowns } from './tree.js';

export async function showDetail(req) {
    const detailDiv = document.getElementById('detail');

    const renderLinks = (links, title, color) => {
        if (typeof links === 'string') {
            try { links = JSON.parse(links); } catch (e) { links = []; }
        }
        if (!Array.isArray(links) || links.length === 0) return '';
        const badges = links.map(k => `<span class="rounded bg-${color}-100 px-2 py-0.5 text-[10px] font-bold text-${color}-800 border border-${color}-200">${k}</span>`).join('');
        return `<div class="mt-3 flex items-center gap-2"><span class="text-xs font-bold text-slate-500 uppercase">${title}:</span> ${badges}</div>`;
    };

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
                    Anforderung bearbeiten
                </button>
            </div>
        </div>
        <section class="mt-5">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-900">Aktuelle Anforderung</h3>
            <p class="mt-2 leading-7 text-slate-800">${req.description || req.text || 'Kein Text'}</p>
            <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Begründung</div>
                <p class="mt-1 text-sm text-slate-700">${req.rationale || '<span class="italic text-slate-400">Keine Begründung hinterlegt</span>'}</p>
            </div>
        </section>
        <section id="reqHistorySection" class="mt-8 border-t border-slate-200 pt-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-3">Änderungshistorie (Frühere Versionen)</h3>
            <div class="text-xs text-slate-400">Lade Historie...</div>
        </section>
    `;

    document.getElementById('editReqBtn').onclick = () => startEdit(req.id);

    // Historie für diese spezifische Anforderung im Hintergrund laden
    const history = await fetchReqHistory(req.id);
    const historySection = document.getElementById('reqHistorySection');

    if (!historySection) return;

    if (history.length === 0) {
        historySection.innerHTML = `
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-3">Änderungshistorie (Frühere Versionen)</h3>
            <p class="text-xs text-slate-400 italic">Bisher keine Änderungen an dieser Anforderung vorgenommen.</p>
        `;
    } else {
        let histHtml = `
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-3">Änderungshistorie (${history.length} frühere Versionen)</h3>
            <div class="space-y-3">
        `;

        history.forEach((h, index) => {
            const versionNum = history.length - index;
            // Hostname absichern (falls leer)
            const hostDisplay = h.hostname ? `Host: ${h.hostname}` : '';
            
            histHtml += `
                <div class="rounded-md border border-slate-200 bg-slate-50/80 p-3 text-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-1 font-mono">
                        <span class="font-bold text-slate-700">Version v${versionNum} (Stand vom ${h.modified_at})</span>
                        <span>User: ${h.modified_by_user || 'ID ' + h.modified_by} | ${hostDisplay}</span>
                    </div>
                    <div class="font-semibold text-slate-800 mt-1">Titel: <span class="font-normal">${h.title}</span></div>
                    <div class="text-slate-600 mt-1"><strong>Beschreibung:</strong> ${h.description || 'Kein Text'}</div>
                    <div class="text-slate-500 mt-1"><strong>Begründung:</strong> ${h.rationale || 'Keine'}</div>
                </div>
            `;
        });

        histHtml += `</div>`;
        historySection.innerHTML = histHtml;
    }
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
            return `<label class="text-sm font-semibold">${label}<select data-attr="${key}" class="mt-1 w-full rounded border p-2 font-normal"><option value="A" ${val === 'A' ? 'selected' : ''}>A</option><option value="B" ${val === 'B' ? 'selected' : ''}>B</option><option value="C" ${val === 'C' ? 'selected' : ''}>C</option></select></label>`;
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

    const getSelectValues = select => Array.from(select.selectedOptions).map(opt => opt.value);

    const payload = {
        project_id: currentProjectId,
        type: document.getElementById('type').value,
        title: document.getElementById('title').value,
        text: document.getElementById('text').value,
        rationale: document.getElementById('rationale').value,
        attributes: attrs,
        parents: getSelectValues(document.getElementById('parents')),
        children: getSelectValues(document.getElementById('children'))
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
            const reqs = await fetchRequirements();
            import('./state.js').then(s => s.setCurrentRequirements(reqs));
            drawSidebar();
            populateParentChildDropdowns();
        } else {
            alert("Fehler: " + result.error);
        }
    } catch (err) {
        console.error(err);
        alert("Netzwerkfehler beim Speichern.");
    } finally {
        btn.disabled = false;
        btn.textContent = 'Speichern';
    }
}

export async function renderHistoryView() {
    const history = await fetchHistory();
    const detailDiv = document.getElementById('detail');
    if (!detailDiv) return;

    let html = `<h2 class="text-xl font-bold text-slate-900 mb-4">Änderungshistorie (Archiv)</h2>`;
    if (history.length === 0) {
        html += `<p class="text-sm text-slate-500 italic">Keine Historie für dieses Projekt vorhanden.</p>`;
    } else {
        html += `<div class="space-y-4">`;
        history.forEach(h => {
            html += `
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 opacity-75">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span class="font-mono font-bold text-slate-700">${h.req_key} (Version vom ${h.modified_at})</span>
                        <span>Geändert von ID: ${h.modified_by_user || h.modified_by}</span>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base">${h.title}</h3>
                    <p class="text-sm text-slate-600 mt-1">${h.description || ''}</p>
                    <div class="mt-2 text-[11px] text-amber-800 bg-amber-50 px-2 py-1 rounded inline-block">Archivierte Alt-Version</div>
                </div>
            `;
        });
        html += `</div>`;
    }
    detailDiv.innerHTML = html;
}