import { currentProjectId } from './state.js';

let reportData = { tasks: [], issues: [], settings: {} };
const byId = id => document.getElementById(id);
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

const contentOptions = {
    status_report: [
        ['summary', 'Fortschrittszusammenfassung', true],
        ['description', 'Aufgabenbeschreibung', true],
        ['progress', 'Fortschrittsbalken', true],
        ['checklist', 'Unteraufgaben / Checkliste', true],
        ['requirements', 'Verknüpfte Anforderungen', true],
        ['issues', 'Verknüpfte Issues', true],
        ['assignee', 'Zuständigkeit', true],
        ['dates', 'Zeitraum', true],
        ['effort', 'Interner Aufwand', false],
        ['internal_details', 'Interne Detailinformationen', false]
    ],
    issue_report: [
        ['description', 'Problembeschreibung', true],
        ['status', 'Status', true],
        ['priority', 'Priorität', true],
        ['category', 'Kategorie', true],
        ['assignee', 'Verantwortlicher', true],
        ['dates', 'Termine', true],
        ['customer_communication', 'Kundenkommunikation', true],
        ['resolution', 'Lösung / Abschluss', true],
        ['internal_response', 'Interne Bearbeitung', false],
        ['comments', 'Kommentare', false],
        ['requirements', 'Verknüpfte Anforderungen', true],
        ['tasks', 'Verknüpfte Aufgaben', true],
        ['source', 'Quelleninformationen', false]
    ]
};

export function initReportsEvents() {
    const form = byId('formExport');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    byId('btnReportLayout')?.addEventListener('click', openLayout);
    document.querySelectorAll('[data-report-close]').forEach(button => button.addEventListener('click', () => byId('modalExportConfig').classList.add('hidden')));
    document.querySelectorAll('[data-layout-close]').forEach(button => button.addEventListener('click', () => byId('modalReportLayout').classList.add('hidden')));
    byId('reportSelectAll')?.addEventListener('click', () => document.querySelectorAll('.report-entity').forEach(input => input.checked = true));
    byId('reportSelectNone')?.addEventListener('click', () => document.querySelectorAll('.report-entity').forEach(input => input.checked = false));
    byId('formReportLayout')?.addEventListener('submit', saveLayout);
    form.addEventListener('submit', exportReport);
}

async function loadOptions() {
    if (!currentProjectId) throw new Error('Bitte zuerst ein Projekt auswählen.');
    const response = await fetch(`../api/get_report_options.php?project_id=${encodeURIComponent(currentProjectId)}`);
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'Reportdaten konnten nicht geladen werden.');
    reportData = data;
}

window.openExportConfig = async reportType => {
    try {
        await loadOptions();
        byId('export_type').value = reportType;
        byId('exportTitle').textContent = {
            specification: 'Pflichtenheft exportieren',
            status_report: 'Aufgaben-Protokoll exportieren',
            issue_report: 'Issue-Report exportieren'
        }[reportType];

        const isSpecification = reportType === 'specification';
        byId('reportSelectionBox').classList.toggle('hidden', isSpecification);
        byId('reportContentBox').classList.toggle('hidden', isSpecification);
        byId('specificationMeta').classList.toggle('hidden', !isSpecification);
        byId('export_status').closest('label').classList.toggle('hidden', isSpecification);

        const docxOption = byId('export_format').querySelector('option[value="docx"]');
        docxOption.disabled = !isSpecification;
        if (!isSpecification) byId('export_format').value = 'pdf';

        if (!isSpecification) {
            const entities = reportType === 'status_report' ? reportData.tasks : reportData.issues;
            byId('reportSelectionTitle').textContent = reportType === 'status_report' ? 'Aufgaben auswählen' : 'Issues auswählen';
            byId('reportEntitySelection').innerHTML = entities.map(item => `
                <label class="flex items-start gap-3 rounded-md border border-slate-200 p-3">
                    <input class="report-entity mt-1" type="checkbox" value="${item.id}" checked>
                    <span>
                        <strong class="block text-blue-950">${escapeHtml(item.wbs_code || item.issue_key)} · ${escapeHtml(item.title)}</strong>
                        <small class="text-slate-500">${escapeHtml(item.category || item.status || '')}</small>
                    </span>
                </label>`).join('');
            byId('reportContentOptions').innerHTML = contentOptions[reportType].map(([key, label, checked]) => `
                <label class="flex items-center gap-2 text-sm">
                    <input class="report-content" type="checkbox" value="${key}" ${checked ? 'checked' : ''}> ${escapeHtml(label)}
                </label>`).join('');
        }

        byId('modalExportConfig').classList.remove('hidden');
    } catch (error) {
        alert(error.message);
    }
};

async function openLayout() {
    try {
        await loadOptions();
        const settings = reportData.settings || {};
        byId('reportHeader').value = settings.header_text || '{company} | {project} | {report}';
        byId('reportFooter').value = settings.footer_text || '{classification} | {date} | Seite {page} von {pages}';
        byId('reportCompany').value = settings.company_name || 'EPSa - Elektronik & Präzisionsbau Saalfeld GmbH';
        byId('reportClassification').value = settings.classification || 'Vertraulich';
        byId('reportColor').value = settings.accent_color || '#1f4e79';
        byId('reportLogoState').textContent = settings.logo_path ? `Logo gespeichert: ${settings.logo_path}` : 'Kein eigenes Logo gespeichert.';
        byId('modalReportLayout').classList.remove('hidden');
    } catch (error) {
        alert(error.message);
    }
}

async function saveLayout(event) {
    event.preventDefault();
    try {
        let response = await fetch('../api/save_report_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: currentProjectId,
                header_text: byId('reportHeader').value,
                footer_text: byId('reportFooter').value,
                company_name: byId('reportCompany').value,
                classification: byId('reportClassification').value,
                accent_color: byId('reportColor').value
            })
        });
        let result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'Speichern fehlgeschlagen.');

        const logo = byId('reportLogo').files[0];
        if (logo) {
            const formData = new FormData();
            formData.append('project_id', currentProjectId);
            formData.append('logo', logo);
            response = await fetch('../api/upload_report_logo.php', { method: 'POST', body: formData });
            result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.error || 'Logo-Upload fehlgeschlagen.');
        }

        byId('modalReportLayout').classList.add('hidden');
        alert('Berichtslayout gespeichert. Es gilt für alle Berichte.');
    } catch (error) {
        alert(error.message);
    }
}

async function exportReport(event) {
    event.preventDefault();
    const type = byId('export_type').value;
    const payload = {
        project_id: currentProjectId,
        type,
        format: byId('export_format').value,
        version: byId('export_version').value,
        status: byId('export_status').value,
        author: byId('export_author').value,
        customer: byId('export_customer').value,
        manager: byId('export_manager').value,
        selected_ids: [...document.querySelectorAll('.report-entity:checked')].map(input => Number(input.value)),
        content: [...document.querySelectorAll('.report-content:checked')].map(input => input.value)
    };

    if (type !== 'specification' && !payload.selected_ids.length) return alert('Bitte mindestens einen Eintrag auswählen.');

    const button = byId('exportSubmitButton');
    const oldText = button.textContent;
    button.disabled = true;
    button.textContent = 'Dokument wird erstellt...';

    try {
        const response = await fetch('../api/export_generator.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (!response.ok) throw new Error(await response.text());
        const contentType = response.headers.get('Content-Type') || '';

        if (!contentType.includes('application/pdf')) {
            const errorText = await response.text();

            throw new Error(
                errorText || 'Der Server hat keine PDF-Datei zurückgegeben.'
            );
        }

        const blob = await response.blob();

        if (blob.size < 1000) {
            throw new Error(
                `Die erzeugte PDF-Datei ist ungewöhnlich klein (${blob.size} Byte).`
            );
        }
        const disposition = response.headers.get('Content-Disposition') || '';
        const filename = disposition.match(/filename="([^"]+)"/i)?.[1] || `Captain_Log_Export.${payload.format}`;
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        byId('modalExportConfig').classList.add('hidden');
    } catch (error) {
        alert('Exportfehler: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = oldText;
    }
}