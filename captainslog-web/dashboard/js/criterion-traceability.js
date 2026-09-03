// dashboard/js/criterion-traceability.js
const API_URL = '../api/criterion_traceability.php';
let initialized = false;
let context = null;
let observer = null;
let loadingPromise = null;

const escapeHtml = value => String(value ?? '').replace(
    /[&<>"']/g,
    character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[character]
);

function getCurrentDocumentId() {
    return document.querySelector('#v4Tree .node.active')?.dataset.doc || null;
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {})
        }
    });
    const text = await response.text();
    let result;
    try {
        result = text ? JSON.parse(text) : null;
    } catch {
        throw new Error(`API liefert kein JSON (${response.status}): ${text.slice(0, 180)}`);
    }
    if (!response.ok || !result?.success) {
        throw new Error(result?.error || `HTTP ${response.status}`);
    }
    return result;
}

function getDialog() {
    let overlay = document.getElementById('ctDialogOverlay');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.id = 'ctDialogOverlay';
    overlay.className = 'cl-modal-overlay hidden fixed inset-0 z-[350] items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm';
    overlay.innerHTML = `
        <form id="ctDialogForm" class="cl-modal mx-auto flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded bg-white shadow-2xl">
            <div class="cl-modal-header shrink-0 flex items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">
                <div>
                    <p class="cl-panel-eyebrow mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Zuweisung</p>
                    <h2 id="ctTitle" class="cl-modal-title text-xl font-extrabold text-blue-950">Kriterium zuordnen</h2>
                </div>
                <button type="button" id="ctCloseIcon" class="cl-button cl-button-secondary min-h-0 px-2.5 py-2 bg-white">✖</button>
            </div>
            
            <div id="ctBody" class="cl-modal-body min-h-0 flex-1 overflow-y-auto p-6 space-y-4"></div>
            
            <div class="cl-modal-footer flex shrink-0 justify-end gap-3 border-t border-slate-300 bg-slate-100 p-4" id="ctFooterActions">
                <button type="button" id="ctCloseBtn" class="cl-button cl-button-secondary bg-white px-5 py-2 text-xs font-bold uppercase">Abbrechen</button>
            </div>
        </form>
    `;

    document.body.appendChild(overlay);

    const closeIt = () => { overlay.classList.add('hidden'); overlay.classList.remove('flex'); };
    overlay.querySelector('#ctCloseIcon').addEventListener('click', closeIt);
    overlay.querySelector('#ctCloseBtn').addEventListener('click', closeIt);

    // Überschreibe native Methoden
    overlay.showModal = () => { overlay.classList.remove('hidden'); overlay.classList.add('flex'); };
    overlay.close = closeIt;

    return overlay;
}

function statusSymbol(status) {
    return ({
        passed: '✓',
        failed: '×',
        covered: '◐',
        outdated: '!',
        blocked: '⊘',
        open: '◇'
    })[status] || '◇';
}

async function loadContext() {
    const documentId = getCurrentDocumentId();
    if (!documentId) return;
    if (loadingPromise) return loadingPromise;

    loadingPromise = api(`${API_URL}?action=context&document_id=${encodeURIComponent(documentId)}`);
    try {
        context = await loadingPromise;
        decorateCriteria();
    } finally {
        loadingPromise = null;
    }
}

function decorateCriteria() {
    if (!context) return;

    document.querySelectorAll('#requirementsEditor .ds-acceptance-row').forEach(row => {
        const criterionId = row.dataset.id;
        const criterion = context.criteria?.find(item => item.id === criterionId);
        
        if (!criterion) return;

        const state = row.querySelector('.ds-acceptance-state');
        if (state) {
            state.className = `ds-acceptance-state is-${criterion.status}`;
            state.textContent = statusSymbol(criterion.status);
            state.title = criterion.reason || criterion.status;
        }

        let tools = row.querySelector('.ct-tools');
        if (!tools) {
            tools = document.createElement('span');
            tools.className = 'ct-tools';
            tools.contentEditable = 'false';
            row.appendChild(tools);
        }

        const mapping = context.mappings?.find(item => item.child_criterion_id === criterionId);

        tools.innerHTML = `
            <button type="button" class="ct-map" data-criterion="${escapeHtml(criterionId)}" title="Parent-Kriterium zuordnen" contenteditable="false">↥</button>
            <button type="button" class="ct-evidence" data-criterion="${escapeHtml(criterionId)}" title="Testnachweis zuordnen" contenteditable="false">✓</button>
            ${mapping ? '<span class="ct-linked" title="Parent-Kriterium verknüpft">🔗</span>' : ''}
        `;
    });
}

function openMappingDialog(criterionId) {
    if (!context) return;
    const dialog = getDialog();
    const options = [];

    for (const parent of context.parents || []) {
        for (const criterion of parent.criteria || []) {
            options.push(`
                <option value="${escapeHtml(parent.id)}|${escapeHtml(criterion.id)}">
                    ${escapeHtml(parent.requirement_key)} • ${escapeHtml(criterion.text)}
                </option>
            `);
        }
    }

    document.getElementById('ctTitle').textContent = 'Parent-Kriterium zuordnen';
    document.getElementById('ctBody').innerHTML = options.length
        ? `
            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Regeln und Parent</legend>
                <div class="cl-fieldset-body space-y-5">
                    <label class="cl-label block text-sm font-bold text-slate-700">Erfüllt
                        <select id="ctParent" class="cl-select mt-1 w-full border border-slate-300 bg-white p-2.5 outline-none focus:border-blue-950">${options.join('')}</select>
                    </label>
                    <label class="cl-label block text-sm font-bold text-slate-700">Regel
                        <select id="ctRule" class="cl-select mt-1 w-full border border-slate-300 bg-white p-2.5 outline-none focus:border-blue-950">
                            <option value="ALL">Alle zugeordneten Children</option>
                            <option value="ANY">Mindestens ein Child</option>
                        </select>
                    </label>
                </div>
            </fieldset>
        `
        : '<p class="text-sm text-slate-500 italic">Das Dokument besitzt keinen direkten Parent mit Akzeptanzkriterien.</p>';

    document.getElementById('ctFooterActions').innerHTML = `
        <button type="button" id="ctCloseBtn" class="cl-button cl-button-secondary bg-white px-5 py-2 text-xs font-bold uppercase">Abbrechen</button>
        ${options.length ? '<button type="button" id="ctSaveMap" class="cl-button cl-button-primary bg-blue-950 px-6 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">Zuordnung speichern</button>' : ''}
    `;
    document.getElementById('ctCloseBtn').addEventListener('click', () => dialog.close());

    dialog.showModal();

    document.getElementById('ctSaveMap')?.addEventListener('click', async () => {
        try {
            const [parentDocumentId, parentCriterionId] = document.getElementById('ctParent').value.split('|');
            await api(`${API_URL}?action=map`, {
                method: 'POST',
                body: JSON.stringify({
                    document_id: getCurrentDocumentId(),
                    child_criterion_id: criterionId,
                    parent_document_id: parentDocumentId,
                    parent_criterion_id: parentCriterionId,
                    aggregation_rule: document.getElementById('ctRule').value
                })
            });
            dialog.close();
            await loadContext();
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    }, { once: true });
}

function openEvidenceDialog(criterionId) {
    if (!context) return;
    const dialog = getDialog();
    const tests = (context.test_links || []).filter(test => test.result !== 'not_run');

    document.getElementById('ctTitle').textContent = 'Testnachweis zuordnen';
    document.getElementById('ctBody').innerHTML = tests.length
        ? `
            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Testauswahl</legend>
                <div class="cl-fieldset-body">
                    <label class="cl-label block text-sm font-bold text-slate-700">Nachweis
                        <select id="ctTest" class="cl-select mt-1 w-full border border-slate-300 bg-white p-2.5 outline-none focus:border-blue-950">
                            ${tests.map(test => `<option value="${escapeHtml(test.id)}">${escapeHtml(test.label)} • ${escapeHtml(test.result)}</option>`).join('')}
                        </select>
                    </label>
                </div>
            </fieldset>
        `
        : '<p class="text-sm text-slate-500 italic">Zuerst unter Verifikation einen ausgeführten Test Run mit Ergebnis verknüpfen.</p>';

    document.getElementById('ctFooterActions').innerHTML = `
        <button type="button" id="ctCloseBtn" class="cl-button cl-button-secondary bg-white px-5 py-2 text-xs font-bold uppercase">Abbrechen</button>
        ${tests.length ? '<button type="button" id="ctSaveEvidence" class="cl-button cl-button-primary bg-blue-950 px-6 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">Nachweis übernehmen</button>' : ''}
    `;
    document.getElementById('ctCloseBtn').addEventListener('click', () => dialog.close());

    dialog.showModal();

    document.getElementById('ctSaveEvidence')?.addEventListener('click', async () => {
        try {
            await api(`${API_URL}?action=evidence`, {
                method: 'POST',
                body: JSON.stringify({
                    document_id: getCurrentDocumentId(),
                    criterion_id: criterionId,
                    document_test_link_id: document.getElementById('ctTest').value
                })
            });
            dialog.close();
            await loadContext();
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    }, { once: true });
}

function handleToolPointerDown(event) {
    const mapButton = event.target.closest('.ct-map');
    const evidenceButton = event.target.closest('.ct-evidence');
    if (!mapButton && !evidenceButton) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    if (mapButton) {
        openMappingDialog(mapButton.dataset.criterion);
    } else {
        openEvidenceDialog(evidenceButton.dataset.criterion);
    }
}

function handleDocumentSelection(event) {
    if (event.target.closest('[data-doc]')) {
        window.setTimeout(() => {
            loadContext().catch(error => console.error(error));
        }, 500);
    }
}

export function initCriterionTraceability() {
    if (initialized) return;
    initialized = true;
    
    document.addEventListener('pointerdown', handleToolPointerDown, true);
    document.addEventListener('mousedown', handleToolPointerDown, true);
    document.addEventListener('click', handleDocumentSelection);

    const editorRoot = document.getElementById('requirementsEditor');
    if (editorRoot) {
        observer = new MutationObserver(() => {
            if (document.querySelector('#requirementsEditor .ds-acceptance-row')) {
                window.clearTimeout(initCriterionTraceability.decorateTimer);
                initCriterionTraceability.decorateTimer = window.setTimeout(() => {
                    loadContext().catch(error => console.error(error));
                }, 150);
            }
        });
        observer.observe(editorRoot, { childList: true, subtree: true });
    }

    window.setTimeout(() => {
        loadContext().catch(error => console.error(error));
    }, 600);
}