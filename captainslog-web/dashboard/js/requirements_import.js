import { currentProjectId } from './state.js';

let sourceRows = [];
let sourceHeaders = [];
let sourceName = 'Import';

const byId = id => document.getElementById(id);
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[character]));

const targetOptions = [
    ['', 'Nicht zuordnen'],
    ['req_key', 'Key'],
    ['type', 'Typ'],
    ['title', 'Titel'],
    ['description', 'Beschreibung'],
    ['title_and_description', 'Titel und Beschreibung'],
    ['rationale', 'Begründung'],
    ['source_contact', 'Quelle / Kontakt'],
    ['effort', 'Aufwand'],
    ['acceptance_criteria', 'Akzeptanzkriterien'],
    ['review_status', 'Prüfstatus']
];

function init() {
    const newButton = byId('new');
    if (!newButton || byId('btnImportRequirements')) return;

    newButton.insertAdjacentHTML(
        'afterend',
        '<button id="btnImportRequirements" type="button" class="cl-button cl-button-secondary">Anforderungen importieren</button>'
    );

    document.body.insertAdjacentHTML('beforeend', `
        <div id="requirementsImportModal" class="cl-modal-overlay hidden z-[280]">
            <div class="cl-modal max-w-7xl">
                <div class="cl-modal-header">
                    <div><p class="cl-panel-eyebrow">Importdesigner</p><h2 class="cl-modal-title">Anforderungen importieren</h2></div>
                    <button id="riClose" type="button" class="cl-button cl-button-secondary">✕</button>
                </div>
                <div class="cl-modal-body">
                    <div class="grid gap-4 md:grid-cols-4">
                        <label class="cl-label">Quelle
                            <select id="riFormat" class="cl-select">
                                <option value="xlsx">Excel XLSX</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                                <option value="text">Text einfügen</option>
                            </select>
                        </label>
                        <label class="cl-label">Erkennungsmodus
                            <select id="riMode" class="cl-select">
                                <option value="bullet_list">Aufzählung mit -, •, * oder Nummern</option>
                                <option value="paragraphs">Ein Absatz je Eintrag</option>
                                <option value="table">Tabelle / feste Spalten</option>
                                <option value="delimited">Trennzeichen</option>
                                <option value="regex">Regulärer Ausdruck</option>
                                <option value="numbered_list">Nummerierte Liste mit Muster</option>
                            </select>
                        </label>
                        <label class="cl-label">Kopfzeile, 0-basiert<input id="riHeaderRow" type="number" min="0" value="0" class="cl-input"></label>
                        <label class="cl-label">Profil<select id="riProfile" class="cl-select"><option value="">Kein Profil</option></select></label>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-4">
                        <label id="riFileWrap" class="cl-label md:col-span-2">Datei<input id="riFile" type="file" accept=".xlsx,.csv,.pdf" class="cl-input"></label>
                        <label class="cl-label">Seite von<input id="riPageFrom" type="number" min="1" value="1" class="cl-input"></label>
                        <label class="cl-label">Seite bis<input id="riPageTo" type="number" min="1" value="9999" class="cl-input"></label>
                        <label class="cl-label">Trennzeichen<input id="riDelimiter" value="-" class="cl-input"></label>
                        <label class="cl-label md:col-span-2">Regex mit Gruppen<input id="riRegex" value="/(?m)^\\s*([^\\s]+)\\s+(.+)$/u" class="cl-input"></label>
                        <label class="cl-label">Spaltengrenzen<input id="riBoundaries" value="0,12,45,75" class="cl-input"></label>
                    </div>
                    <label id="riTextWrap" class="cl-label mt-4 hidden">Text einfügen
                        <textarea id="riText" rows="8" class="cl-textarea" placeholder="Aufzählung, Absätze, Tabelle oder getrennte Zeilen einfügen"></textarea>
                    </label>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="riAnalyze" type="button" class="cl-button cl-button-primary">Quelle analysieren</button>
                        <button id="riSaveProfile" type="button" class="cl-button cl-button-secondary">Profil speichern</button>
                        <button id="riDeleteProfile" type="button" class="cl-button cl-button-secondary">Profil löschen</button>
                    </div>
                    <div id="riWorkspace" class="mt-5"><div class="cl-empty-state">Quelle und Erkennungsmodus auswählen.</div></div>
                </div>
                <div class="cl-modal-footer">
                    <button id="riCancel" type="button" class="cl-button cl-button-secondary">Abbrechen</button>
                    <button id="riExecute" type="button" class="cl-button cl-button-primary" disabled>Geprüfte Zeilen importieren</button>
                </div>
            </div>
        </div>`);

    byId('btnImportRequirements').addEventListener('click', openModal);
    byId('riClose').addEventListener('click', closeModal);
    byId('riCancel').addEventListener('click', closeModal);
    byId('riFormat').addEventListener('change', updateSourceUi);
    byId('riAnalyze').addEventListener('click', analyze);
    byId('riExecute').addEventListener('click', executeImport);
    byId('riSaveProfile').addEventListener('click', saveProfile);
    byId('riDeleteProfile').addEventListener('click', deleteProfile);
    byId('riProfile').addEventListener('change', applyProfile);
    updateSourceUi();
}

function closeModal() {
    byId('requirementsImportModal').classList.add('hidden');
}

async function openModal() {
    if (!currentProjectId) return alert('Bitte zuerst ein Projekt auswählen.');
    byId('requirementsImportModal').classList.remove('hidden');
    await loadProfiles();
}

function updateSourceUi() {
    const isText = byId('riFormat').value === 'text';
    byId('riFileWrap').classList.toggle('hidden', isText);
    byId('riTextWrap').classList.toggle('hidden', !isText);
    if (isText && byId('riMode').value === 'table') byId('riMode').value = 'bullet_list';
}

function getConfiguration() {
    return {
        format: byId('riFormat').value,
        mode: byId('riMode').value,
        header_row: Number(byId('riHeaderRow').value || 0),
        page_from: Number(byId('riPageFrom').value || 1),
        page_to: Number(byId('riPageTo').value || 9999),
        delimiter: byId('riDelimiter').value,
        regex: byId('riRegex').value,
        start_pattern: byId('riRegex').value,
        boundaries: byId('riBoundaries').value
            .split(',')
            .map(value => Number(value.trim()))
            .filter(Number.isFinite)
    };
}

async function analyze() {
    const formData = new FormData();
    formData.append('config', JSON.stringify(getConfiguration()));
    formData.append('pasted_text', byId('riText').value);

    const file = byId('riFile').files[0];
    if (byId('riFormat').value !== 'text' && !file) return alert('Bitte eine Datei auswählen.');
    if (file) formData.append('file', file);

    byId('riWorkspace').innerHTML = '<div class="cl-empty-state">Quelle wird analysiert...</div>';

    try {
        const response = await fetch('../api/preview_requirements_import.php', {method: 'POST', body: formData});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'Analyse fehlgeschlagen.');

        sourceRows = result.rows || [];
        sourceHeaders = result.headers || [];
        sourceName = result.filename || 'Import';
        renderMapping(result);
    } catch (error) {
        byId('riWorkspace').innerHTML = `<div class="cl-empty-state text-red-600">${escapeHtml(error.message)}</div>`;
    }
}

function guessTarget(header, result) {
    const heading = String(header).toLowerCase();
    if (result.single_content_column && result.recommended_target) return result.recommended_target;
    if (/key|^id$|nummer|^nr/.test(heading)) return 'req_key';
    if (/typ|type/.test(heading)) return 'type';
    if (/titel|title|kurztext/.test(heading)) return 'title';
    if (/beschreibung|spezifikation|description|anforderung/.test(heading)) return 'description';
    if (/begründ|rationale/.test(heading)) return 'rationale';
    if (/quelle|kontakt|source/.test(heading)) return 'source_contact';
    if (/aufwand|effort/.test(heading)) return 'effort';
    if (/akzeptanz|kriter|acceptance/.test(heading)) return 'acceptance_criteria';
    if (/status|freigabe|review/.test(heading)) return 'review_status';
    return '';
}

function renderMapping(result) {
    const options = targetOptions.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');

    byId('riWorkspace').innerHTML = `
        <div class="rounded-md border border-blue-200 bg-blue-50 p-3">
            <strong>${escapeHtml(sourceName)}:</strong> ${result.row_count} Einträge erkannt. Die Zuordnung kann vor der Vorschau frei festgelegt werden.
        </div>
        <h3 class="mt-4 text-sm font-extrabold text-blue-950">Erkannte Inhalte zuordnen</h3>
        <div class="mt-2 grid gap-3 md:grid-cols-3 xl:grid-cols-5">
            ${sourceHeaders.map((header, index) => `
                <label class="cl-label">${escapeHtml(header)}
                    <select class="cl-select riMapping" data-index="${index}">${options}</select>
                </label>`).join('')}
        </div>
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
            Für Lastenheft-Aufzählungen meist <strong>Titel</strong> wählen. Für ausformulierte Pflichtenheft-Absätze meist <strong>Beschreibung</strong>. Mit <strong>Titel und Beschreibung</strong> wird derselbe Quelltext zunächst in beide Felder übernommen und kann unten getrennt bearbeitet werden.
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <label class="cl-label">Standardtyp<input id="riDefaultType" value="SYS" class="cl-input"></label>
            <label class="cl-label">Importmodus<select id="riImportMode" class="cl-select"><option value="skip">Dubletten überspringen</option><option value="update">Vorhandene aktualisieren</option></select></label>
            <button id="riBuildPreview" type="button" class="cl-button cl-button-secondary self-end">Bearbeitbare Vorschau erzeugen</button>
        </div>
        <div id="riEditablePreview" class="mt-5"></div>`;

    document.querySelectorAll('.riMapping').forEach((select, index) => {
        select.value = guessTarget(sourceHeaders[index] || '', result);
    });

    byId('riBuildPreview').addEventListener('click', buildEditablePreview);
}

function buildEditablePreview() {
    const mappings = [...document.querySelectorAll('.riMapping')]
        .map(select => ({target: select.value, index: Number(select.dataset.index)}))
        .filter(mapping => mapping.target !== '');

    if (!mappings.length) {
        return alert('Bitte mindestens einen erkannten Inhalt zuordnen, zum Beispiel Titel, Beschreibung oder Titel und Beschreibung.');
    }

    const defaultType = byId('riDefaultType').value.trim().toUpperCase() || 'SYS';

    const normalizedRows = sourceRows.map(row => {
        const item = {
            import: true,
            req_key: '',
            type: defaultType,
            title: '',
            description: '',
            rationale: '',
            source_contact: '',
            effort: '',
            acceptance_criteria: '',
            review_status: 'Neu'
        };

        mappings.forEach(mapping => {
            const value = String(row[mapping.index] ?? '').trim();
            if (mapping.target === 'title_and_description') {
                item.title = value;
                item.description = value;
            } else {
                item[mapping.target] = value;
            }
        });

        // Nur Beschreibung vorhanden: Titel als kurze Arbeitskopie erzeugen.
        if (!item.title && item.description) {
            item.title = item.description.length > 240
                ? item.description.slice(0, 237).trimEnd() + '...'
                : item.description;
        }

        return item;
    });

    byId('riEditablePreview').innerHTML = `
        <div class="mb-2 flex flex-wrap gap-2">
            <button id="riAll" type="button" class="cl-button cl-button-secondary">Alle</button>
            <button id="riNone" type="button" class="cl-button cl-button-secondary">Keine</button>
            <span class="self-center text-xs text-slate-500">${normalizedRows.length} bearbeitbare Zeilen</span>
        </div>
        <div class="max-h-[520px] overflow-auto rounded-md border border-slate-200">
            <table class="cl-table min-w-[1600px]">
                <thead><tr><th>Import</th><th>Key</th><th>Typ</th><th>Titel</th><th>Beschreibung</th><th>Akzeptanzkriterien</th><th>Quelle</th></tr></thead>
                <tbody>
                    ${normalizedRows.map((item, index) => `
                        <tr data-import-row="${index}">
                            <td><input class="riImport" type="checkbox" checked></td>
                            <td><input class="cl-input riKey" value="${escapeHtml(item.req_key)}"></td>
                            <td><input class="cl-input riType w-20" value="${escapeHtml(item.type)}"></td>
                            <td><textarea class="cl-textarea riTitle" rows="3">${escapeHtml(item.title)}</textarea></td>
                            <td><textarea class="cl-textarea riDescription" rows="4">${escapeHtml(item.description)}</textarea></td>
                            <td><textarea class="cl-textarea riCriteria" rows="4">${escapeHtml(item.acceptance_criteria)}</textarea></td>
                            <td><input class="cl-input riSource" value="${escapeHtml(item.source_contact)}"></td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>`;

    byId('riAll').addEventListener('click', () => document.querySelectorAll('.riImport').forEach(input => input.checked = true));
    byId('riNone').addEventListener('click', () => document.querySelectorAll('.riImport').forEach(input => input.checked = false));
    byId('riExecute').disabled = false;
}

function collectRows() {
    return [...document.querySelectorAll('tr[data-import-row]')].map(row => ({
        import: row.querySelector('.riImport').checked,
        req_key: row.querySelector('.riKey').value.trim(),
        type: row.querySelector('.riType').value.trim(),
        title: row.querySelector('.riTitle').value.trim(),
        description: row.querySelector('.riDescription').value.trim(),
        acceptance_criteria: row.querySelector('.riCriteria').value.trim(),
        source_contact: row.querySelector('.riSource').value.trim(),
        review_status: 'Neu'
    }));
}

async function executeImport() {
    const rows = collectRows();
    const selectedCount = rows.filter(row => row.import).length;
    if (!selectedCount) return alert('Keine Zeilen ausgewählt.');
    if (!confirm(`${selectedCount} geprüfte Anforderungen importieren?`)) return;

    byId('riExecute').disabled = true;

    try {
        const response = await fetch('../api/execute_requirements_import.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                project_id: currentProjectId,
                rows,
                import_mode: byId('riImportMode').value,
                source_name: byId('riFile').files[0]?.name || 'Eingefügter Text',
                source_format: byId('riFormat').value,
                extraction_mode: byId('riMode').value,
                profile_id: byId('riProfile').value || null
            })
        });

        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'Import fehlgeschlagen.');

        alert(`Import abgeschlossen: ${result.created} erstellt, ${result.updated} aktualisiert, ${result.skipped} übersprungen, ${result.failed} fehlerhaft.`);
        closeModal();
        window.location.reload();
    } catch (error) {
        alert(error.message);
        byId('riExecute').disabled = false;
    }
}

async function loadProfiles() {
    try {
        const response = await fetch(`../api/get_requirement_import_profiles.php?project_id=${encodeURIComponent(currentProjectId)}`);
        const result = await response.json();
        byId('riProfile').innerHTML = '<option value="">Kein Profil</option>' + (result.profiles || []).map(profile => `
            <option value="${profile.id}" data-profile="${encodeURIComponent(JSON.stringify(profile))}">${escapeHtml(profile.profile_name)}</option>`).join('');
    } catch (_) {}
}

async function saveProfile() {
    const name = prompt('Name des Importprofils:');
    if (!name) return;

    const response = await fetch('../api/save_requirement_import_profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            project_id: currentProjectId,
            profile_name: name,
            source_format: byId('riFormat').value,
            extraction_mode: byId('riMode').value,
            configuration: getConfiguration()
        })
    });

    const result = await response.json();
    if (!result.success) return alert(result.error);
    await loadProfiles();
    alert('Profil gespeichert.');
}

async function deleteProfile() {
    const id = Number(byId('riProfile').value || 0);
    if (!id || !confirm('Dieses Profil löschen?')) return;

    const response = await fetch('../api/delete_requirement_import_profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    });

    const result = await response.json();
    if (!result.success) return alert(result.error);
    await loadProfiles();
}

function applyProfile() {
    const option = byId('riProfile').selectedOptions[0];
    if (!option?.dataset.profile) return;
    const profile = JSON.parse(decodeURIComponent(option.dataset.profile));
    const configuration = profile.configuration || {};

    byId('riFormat').value = profile.source_format;
    byId('riMode').value = profile.extraction_mode;
    byId('riHeaderRow').value = configuration.header_row ?? 0;
    byId('riPageFrom').value = configuration.page_from ?? 1;
    byId('riPageTo').value = configuration.page_to ?? 9999;
    byId('riDelimiter').value = configuration.delimiter ?? '-';
    byId('riRegex').value = configuration.regex ?? configuration.start_pattern ?? '';
    byId('riBoundaries').value = (configuration.boundaries || [0, 12, 45, 75]).join(',');
    updateSourceUi();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
