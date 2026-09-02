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
    let dialog = document.getElementById('ctDialog');

    if (dialog) {
        return dialog;
    }

    dialog = document.createElement('dialog');
    dialog.id = 'ctDialog';
    dialog.className = 'dlg ct-dialog';
    dialog.innerHTML = `
        <form method="dialog">
            <h3 id="ctTitle">Kriterium zuordnen</h3>
            <div id="ctBody"></div>
            <div class="dlg-actions">
                <button type="button" id="ctClose">Abbrechen</button>
            </div>
        </form>
    `;

    document.body.appendChild(dialog);
    dialog.querySelector('#ctClose').addEventListener('click', () => dialog.close());

    return dialog;
}

function statusSymbol(status) {
    return ({
        passed: '✓',
        failed: '×',
        covered: '◐',
        outdated: '!',
        blocked: '■',
        open: '◇'
    })[status] || '◇';
}

async function loadContext() {
    const documentId = getCurrentDocumentId();

    if (!documentId) {
        return;
    }

    if (loadingPromise) {
        return loadingPromise;
    }

    loadingPromise = api(
        `${API_URL}?action=context&document_id=${encodeURIComponent(documentId)}`
    );

    try {
        context = await loadingPromise;
        decorateCriteria();
    } finally {
        loadingPromise = null;
    }
}

function decorateCriteria() {
    if (!context) {
        return;
    }

    document
        .querySelectorAll('#requirementsEditor .ds-acceptance-row')
        .forEach(row => {
            const criterionId = row.dataset.id;
            const criterion = context.criteria?.find(item => item.id === criterionId);

            if (!criterion) {
                return;
            }

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

            const mapping = context.mappings?.find(
                item => item.child_criterion_id === criterionId
            );

            tools.innerHTML = `
                <button
                    type="button"
                    class="ct-map"
                    data-criterion="${escapeHtml(criterionId)}"
                    title="Parent-Kriterium zuordnen"
                    contenteditable="false"
                >↥</button>
                <button
                    type="button"
                    class="ct-evidence"
                    data-criterion="${escapeHtml(criterionId)}"
                    title="Testnachweis zuordnen"
                    contenteditable="false"
                >✓</button>
                ${mapping ? '<span class="ct-linked" title="Parent-Kriterium verknüpft">●</span>' : ''}
            `;
        });
}

function openMappingDialog(criterionId) {
    if (!context) {
        return;
    }

    const dialog = getDialog();
    const options = [];

    for (const parent of context.parents || []) {
        for (const criterion of parent.criteria || []) {
            options.push(`
                <option value="${escapeHtml(parent.id)}|${escapeHtml(criterion.id)}">
                    ${escapeHtml(parent.requirement_key)} · ${escapeHtml(criterion.text)}
                </option>
            `);
        }
    }

    document.getElementById('ctTitle').textContent = 'Parent-Kriterium zuordnen';
    document.getElementById('ctBody').innerHTML = options.length
        ? `
            <label class="field">
                <span>Erfüllt</span>
                <select id="ctParent">${options.join('')}</select>
            </label>
            <label class="field">
                <span>Regel</span>
                <select id="ctRule">
                    <option value="ALL">Alle zugeordneten Children</option>
                    <option value="ANY">Mindestens ein Child</option>
                </select>
            </label>
            <button type="button" id="ctSaveMap">Zuordnung speichern</button>
        `
        : '<p>Das Dokument besitzt keinen direkten Parent mit Akzeptanzkriterien.</p>';

    dialog.showModal();

    document.getElementById('ctSaveMap')?.addEventListener('click', async () => {
        try {
            const [parentDocumentId, parentCriterionId] =
                document.getElementById('ctParent').value.split('|');

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
    if (!context) {
        return;
    }

    const dialog = getDialog();
    const tests = (context.test_links || []).filter(test => test.result !== 'not_run');

    document.getElementById('ctTitle').textContent = 'Testnachweis zuordnen';
    document.getElementById('ctBody').innerHTML = tests.length
        ? `
            <label class="field">
                <span>Nachweis</span>
                <select id="ctTest">
                    ${tests.map(test => `
                        <option value="${escapeHtml(test.id)}">
                            ${escapeHtml(test.label)} · ${escapeHtml(test.result)}
                        </option>
                    `).join('')}
                </select>
            </label>
            <button type="button" id="ctSaveEvidence">Nachweis übernehmen</button>
        `
        : '<p>Zuerst unter Verifikation einen ausgeführten Test Run mit Ergebnis verknüpfen.</p>';

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

    if (!mapButton && !evidenceButton) {
        return;
    }

    // Editor.js captures mouse events inside editable blocks.
    // Handle the action during capture and stop Editor.js from swallowing it.
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
    if (initialized) {
        return;
    }

    initialized = true;

    // Capture phase is essential for controls injected into Editor.js blocks.
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

        observer.observe(editorRoot, {
            childList: true,
            subtree: true
        });
    }

    window.setTimeout(() => {
        loadContext().catch(error => console.error(error));
    }, 600);
}
