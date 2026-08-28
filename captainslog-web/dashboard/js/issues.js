import { currentProjectId } from './state.js';

let issues = [];
let users = [];
let requirements = [];
let tasks = [];
let importRows = [];
let importMeta = {};

/**
 * Schützt dynamische Inhalte bei der HTML-Ausgabe.
 */
const esc = value =>
    String(value ?? '').replace(/[&<>'"]/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[character]));

/**
 * Deutsche Statusbezeichnungen.
 */
const statusLabels = {
    open: 'Offen',
    in_progress: 'In Bearbeitung',
    waiting_response: 'Wartet auf Antwort',
    ready_for_test: 'Testbereit',
    approved: 'Freigegeben',
    closed: 'Geschlossen',
    rejected: 'Abgelehnt'
};

/**
 * Deutsche Prioritätsbezeichnungen.
 */
const priorityLabels = {
    low: 'Niedrig',
    medium: 'Mittel',
    high: 'Hoch',
    critical: 'Kritisch'
};

/**
 * Deutsche Typbezeichnungen.
 */
const typeLabels = {
    bug: 'Fehler',
    change_request: 'Change Request',
    customer_feedback: 'Kundenrückmeldung',
    question: 'Frage',
    deviation: 'Abweichung',
    improvement: 'Verbesserung'
};



// =========================================================================
// INLINE-AUSWAHLLISTEN IM ISSUE-FORMULAR
// =========================================================================
function issueCsvValues(id) {
    return (document.getElementById(id)?.value || '').split(',').map(value => value.trim()).filter(Boolean);
}
function renderIssueRequirementList() {
    const list = document.getElementById('issueReqCheckboxList'); if (!list) return;
    const selected = issueCsvValues('issue_requirements');
    const query = (document.getElementById('issueReqSearch')?.value || '').trim().toLowerCase();
    const rows = requirements.filter(item => !query || [item.req_key,item.type,item.title].filter(Boolean).join(' ').toLowerCase().includes(query));
    list.innerHTML = rows.length ? rows.map(item => `
      <label class="flex cursor-pointer items-start gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0 hover:bg-indigo-50">
        <input type="checkbox" class="issue-req-cb mt-0.5 h-4 w-4" value="${Number(item.id)}" ${selected.includes(String(item.id))?'checked':''}>
        <span class="min-w-0 text-xs"><strong class="font-mono text-indigo-950">${esc(item.req_key)}</strong><span class="ml-2 text-[10px] font-bold uppercase text-slate-400">${esc(item.type||'')}</span><span class="mt-0.5 block truncate text-slate-700" title="${esc(item.title||'')}">${esc(item.title||'')}</span></span>
      </label>`).join('') : '<div class="p-4 text-sm italic text-slate-400">Keine passenden Anforderungen.</div>';
}
function renderIssueTaskList() {
    const list = document.getElementById('issueTaskCheckboxList'); if (!list) return;
    const selected = issueCsvValues('issue_tasks');
    const query = (document.getElementById('issueTaskSearch')?.value || '').trim().toLowerCase();
    const rows = tasks.filter(item => !query || [item.wbs_code,item.category,item.title].filter(Boolean).join(' ').toLowerCase().includes(query));
    list.innerHTML = rows.length ? rows.map(item => `
      <label class="flex cursor-pointer items-start gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0 hover:bg-sky-50">
        <input type="checkbox" class="issue-task-cb mt-0.5 h-4 w-4" value="${Number(item.id)}" ${selected.includes(String(item.id))?'checked':''}>
        <span class="min-w-0 text-xs"><strong class="font-mono text-sky-950">${esc(item.wbs_code||item.id)}</strong><span class="ml-2 text-[10px] font-bold uppercase text-slate-400">${esc(item.category||'')}</span><span class="mt-0.5 block truncate text-slate-700" title="${esc(item.title||'')}">${esc(item.title||'')}</span></span>
      </label>`).join('') : '<div class="p-4 text-sm italic text-slate-400">Keine passenden Aufgaben.</div>';
}
function syncIssueRequirementSelection() {
    document.getElementById('issue_requirements').value=[...document.querySelectorAll('#issueReqCheckboxList .issue-req-cb:checked')].map(cb=>cb.value).join(',');
}
function syncIssueTaskSelection() {
    document.getElementById('issue_tasks').value=[...document.querySelectorAll('#issueTaskCheckboxList .issue-task-cb:checked')].map(cb=>cb.value).join(',');
}

/**
 * Verwandelt GitHub-Markdown in schicke HTML Code-Blöcke.
 * Escaped den Text vorher automatisch zur Sicherheit.
 */
function formatIssueText(text) {
    if (!text) return '<span class="text-slate-400 italic">Keine Angabe</span>';

    // 1. Sicherheit: Nutzt deine bestehende esc() Funktion
    let html = esc(text);

    // 2. Mehrzeilige Code-Blöcke (```c ... ```) umwandeln -> Dunkles Theme

    html = html.replace(/```([a-zA-Z0-9]*)\s*([\s\S]*?)```/g, function (match, lang, code) {
        const langBadge = lang ? `<div class="text-[10px] text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-700 pb-1">${lang}</div>` : '';
        return `<div class="bg-slate-900 text-slate-50 p-3 rounded-md my-3 overflow-x-auto shadow-inner border border-slate-700 font-mono text-xs text-left whitespace-pre">
                    ${langBadge}<code>${code}</code>
                </div>`;
    });

    // 3. Einzeiliger Inline-Code (`code`) umwandeln -> Helles Badge
    html = html.replace(/`([^`\n]+)`/g, '<code class="bg-slate-100 text-rose-600 border border-slate-200 px-1.5 py-0.5 rounded font-mono text-xs">$1</code>');

    return html;
}


/**
 * Extrahiert die laufende Nummer aus einem Issue-Key.
 *
 * ISSUE-009 wird zu 9.
 */
function getIssueNumber(issueKey) {
    const match = String(issueKey ?? '').match(/(\d+)$/);

    return match
        ? parseInt(match[1], 10)
        : Number.MAX_SAFE_INTEGER;
}

/**
 * Sortiert aktive Issues zuerst.
 * Geschlossene und abgelehnte Issues stehen am Ende.
 * Innerhalb der Gruppen wird numerisch sortiert.
 */
function sortIssues(issueList) {
    return [...issueList].sort((issueA, issueB) => {
        const archivedA =
            issueA.status === 'closed' ||
                issueA.status === 'rejected'
                ? 1
                : 0;

        const archivedB =
            issueB.status === 'closed' ||
                issueB.status === 'rejected'
                ? 1
                : 0;

        if (archivedA !== archivedB) {
            return archivedA - archivedB;
        }

        const numberA = getIssueNumber(issueA.issue_key);
        const numberB = getIssueNumber(issueB.issue_key);

        if (numberA !== numberB) {
            return numberA - numberB;
        }

        return Number(issueA.id || 0) - Number(issueB.id || 0);
    });
}

/**
 * Formatiert ein Datum für die Ausgabe.
 */
function formatDisplayDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('de-DE');
}

/**
 * Liefert die CSS-Klassen einer Tabellenzeile.
 */
function getRowClasses(issue) {
    switch (issue.status) {
        case 'closed':
            return [
                'bg-slate-100',
                'text-slate-400',
                'opacity-75',
                'hover:bg-slate-200'
            ].join(' ');

        case 'rejected':
            return [
                'bg-red-50/60',
                'text-slate-500',
                'hover:bg-red-100/70'
            ].join(' ');

        case 'approved':
            return [
                'bg-emerald-50/40',
                'hover:bg-emerald-50'
            ].join(' ');

        case 'ready_for_test':
            return [
                'bg-blue-50/30',
                'hover:bg-blue-50'
            ].join(' ');

        default:
            return 'bg-white hover:bg-slate-50';
    }
}

/**
 * Liefert die CSS-Klassen des Titels.
 */
function getTitleClasses(issue) {
    if (issue.status === 'closed') {
        return [
            'text-slate-500',
            'line-through',
            'decoration-slate-400'
        ].join(' ');
    }

    if (issue.status === 'rejected') {
        return [
            'text-slate-600',
            'line-through',
            'decoration-red-400'
        ].join(' ');
    }

    return 'text-slate-900';
}

/**
 * Liefert die CSS-Klassen des Issue-Keys.
 */
function getKeyClasses(issue) {
    if (issue.status === 'closed') {
        return 'text-slate-400';
    }

    if (issue.status === 'rejected') {
        return 'text-red-700';
    }

    if (issue.status === 'approved') {
        return 'text-emerald-800';
    }

    return 'text-blue-950';
}

/**
 * Liefert die CSS-Klassen des Status-Badges.
 */
function getStatusClasses(status) {
    switch (status) {
        case 'open':
            return 'border-slate-300 bg-white text-slate-700';

        case 'in_progress':
            return 'border-indigo-300 bg-indigo-50 text-indigo-800';

        case 'waiting_response':
            return 'border-amber-300 bg-amber-50 text-amber-800';

        case 'ready_for_test':
            return 'border-blue-300 bg-blue-50 text-blue-800';

        case 'approved':
            return 'border-emerald-300 bg-emerald-100 text-emerald-800';

        case 'closed':
            return 'border-slate-300 bg-slate-200 text-slate-600';

        case 'rejected':
            return 'border-red-300 bg-red-100 text-red-700';

        default:
            return 'border-slate-300 bg-slate-100 text-slate-700';
    }
}

/**
 * Liefert die CSS-Klassen des Prioritäts-Badges.
 */
function getPriorityClasses(priority) {
    switch (priority) {
        case 'critical':
            return 'border-red-300 bg-red-100 text-red-800';

        case 'high':
            return 'border-orange-300 bg-orange-100 text-orange-800';

        case 'medium':
            return 'border-amber-300 bg-amber-50 text-amber-800';

        case 'low':
            return 'border-slate-300 bg-slate-100 text-slate-600';

        default:
            return 'border-slate-300 bg-white text-slate-600';
    }
}

/**
 * Lädt Issues und dazugehörige Auswahldaten.
 */
export async function loadIssues() {
    const tableBody = document.getElementById('issueTableBody');

    if (!tableBody) {
        return;
    }

    if (!currentProjectId) {
        issues = [];
        users = [];
        requirements = [];
        tasks = [];

        tableBody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="p-8 text-center text-slate-400 italic">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;

        renderKpis();
        return;
    }

    tableBody.innerHTML = `
        <tr>
            <td
                colspan="8"
                class="p-8 text-center text-slate-500 italic">
                Issues werden geladen...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `../api/get_issues.php?project_id=${encodeURIComponent(currentProjectId)}`
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Issues konnten nicht geladen werden.'
            );
        }

        issues = sortIssues(data.issues || []);
        users = data.users || [];
        requirements = data.requirements || [];
        tasks = data.tasks || [];

        fillLists();
        render();
    } catch (error) {
        console.error('Fehler beim Laden der Issues:', error);

        tableBody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="p-8 text-center font-semibold text-red-600">
                    ${esc(error.message)}
                </td>
            </tr>
        `;
    }
}

/**
 * Füllt Benutzer-, Requirement- und Task-Auswahlfelder.
 */
/**
 * Füllt Benutzer-Auswahlfelder.
 * (Anforderungen und Tasks laufen jetzt über den neuen Universal-Picker)
 */
function fillLists() {
    const assigneeSelect = document.getElementById('issue_assignee');

    if (assigneeSelect) {
        assigneeSelect.innerHTML = `
            <option value="">-- Niemand zugewiesen --</option>
            ${users.map(user => `
                <option value="${Number(user.id)}">
                    ${esc(user.username)}
                </option>
            `).join('')}
        `;
    }
    renderIssueRequirementList();
    renderIssueTaskList();
}
/**
 * Rendert die Issue-Tabelle.
 */
function render() {
    const tableBody =
        document.getElementById('issueTableBody');

    const searchInput =
        document.getElementById('issueSearch');

    const statusFilter =
        document.getElementById('issueStatusFilter');

    if (!tableBody) {
        return;
    }

    const query = String(searchInput?.value || '')
        .trim()
        .toLowerCase();

    const selectedStatus =
        statusFilter?.value || '';

    const filteredIssues = sortIssues(
        issues.filter(issue => {
            const matchesStatus =
                !selectedStatus ||
                issue.status === selectedStatus;

            const searchableText = [
                issue.issue_key,
                issue.external_id,
                issue.title,
                issue.description,
                issue.category,
                issue.assignee_name,
                issue.external_assignee,
                statusLabels[issue.status],
                priorityLabels[issue.priority],
                typeLabels[issue.issue_type]
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            const matchesSearch =
                !query ||
                searchableText.includes(query);

            return matchesStatus && matchesSearch;
        })
    );

    if (filteredIssues.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="p-8 text-center text-slate-400 italic">
                    Keine Issues gefunden.
                </td>
            </tr>
        `;

        renderKpis();
        renderResultCount(0);
        return;
    }

    tableBody.innerHTML = filteredIssues.map(issue => {
        const rowClasses = getRowClasses(issue);
        const titleClasses = getTitleClasses(issue);
        const keyClasses = getKeyClasses(issue);
        const statusClasses = getStatusClasses(issue.status);
        const priorityClasses = getPriorityClasses(issue.priority);

        const iconEye = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>`;
        const iconEdit = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>`;
        const iconTrash = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;


        const requirementCount =
            Number(issue.requirement_count || 0);

        const taskCount =
            Number(issue.task_count || 0);

        const reportedDate =
            formatDisplayDate(issue.reported_at);

        const typeLabel =
            typeLabels[issue.issue_type] ||
            issue.issue_type ||
            'Issue';

        return `
            <tr
                class="border-b border-slate-200 transition-colors ${rowClasses}">

                <td class="p-3 align-top">
                    <div
                        class="whitespace-nowrap font-mono text-sm font-extrabold ${keyClasses}">
                        ${esc(issue.issue_key)}
                    </div>

                    <div class="mt-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                        ${esc(typeLabel)}
                    </div>
                </td>

                <td class="p-3 align-top">
                    <div class="font-bold leading-5 ${titleClasses}">
                        ${esc(issue.title)}
                    </div>

                    ${reportedDate
                ? `
                                <div class="mt-1 text-[11px] text-slate-400">
                                    Gemeldet am ${esc(reportedDate)}
                                </div>
                            `
                : ''
            }
                </td>

                <td class="min-w-[155px] p-3 align-top">
                    <span
                        class="inline-flex items-center whitespace-nowrap border px-2.5 py-1 text-xs font-bold ${statusClasses}">
                        ${esc(statusLabels[issue.status] || issue.status)}
                    </span>
                </td>

                <td class="p-3 align-top">
                    <span
                        class="inline-flex items-center whitespace-nowrap border px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide ${priorityClasses}">
                        ${esc(priorityLabels[issue.priority] || issue.priority)}
                    </span>
                </td>

                <td class="p-3 align-top text-sm">
                    ${esc(issue.category || '-')}
                </td>

                <td class="p-3 align-top text-sm">
                    ${esc(
                issue.assignee_name ||
                issue.external_assignee ||
                '-'
            )}
                </td>

                <td class="p-3 align-top text-xs whitespace-nowrap">
                    <div class="font-semibold text-slate-700">
                        ${requirementCount} Reqs
                    </div>

                    <div class="mt-1 text-slate-500">
                        ${taskCount} Tasks
                    </div>
                </td>

            
        <td class="p-3 align-top text-right whitespace-nowrap">
            <div class="flex justify-end gap-1.5">
                <button
                    type="button"
                    onclick="window.viewIssueReport(${Number(issue.id)})"
                    class="rounded border border-blue-200 bg-blue-50 p-1.5 text-blue-600 shadow-sm transition hover:bg-blue-600 hover:text-white"
                    title="Bericht anzeigen">
                    ${iconEye}
                </button>

                <button
                    type="button"
                    onclick="window.editIssue(${Number(issue.id)})"
                    class="rounded border border-slate-200 bg-slate-50 p-1.5 text-slate-500 shadow-sm transition hover:bg-blue-600 hover:text-white"
                    title="Bearbeiten">
                    ${iconEdit}
                </button>

                <button
                    type="button"
                    onclick="window.deleteIssue(${Number(issue.id)})"
                    class="rounded border border-slate-200 bg-slate-50 p-1.5 text-slate-500 shadow-sm transition hover:bg-red-600 hover:text-white"
                    title="Löschen">
                    ${iconTrash}
                </button>
            </div>
        </td>
            </tr>
        `;
    }).join('');

    renderKpis();
    renderResultCount(filteredIssues.length);
}

/**
 * Rendert die KPI-Karten.
 */
function renderKpis() {
    const container =
        document.getElementById('issueKpis');

    if (!container) {
        return;
    }

    const counts = {};

    issues.forEach(issue => {
        counts[issue.status] =
            (counts[issue.status] || 0) + 1;
    });

    const statusConfiguration = [
        {
            status: 'open',
            border: 'border-l-slate-500',
            number: 'text-slate-800'
        },
        {
            status: 'in_progress',
            border: 'border-l-indigo-500',
            number: 'text-indigo-800'
        },
        {
            status: 'waiting_response',
            border: 'border-l-amber-500',
            number: 'text-amber-700'
        },
        {
            status: 'ready_for_test',
            border: 'border-l-blue-500',
            number: 'text-blue-800'
        },
        {
            status: 'approved',
            border: 'border-l-emerald-500',
            number: 'text-emerald-700'
        },
        {
            status: 'closed',
            border: 'border-l-slate-400',
            number: 'text-slate-500'
        }
    ];

    container.innerHTML = statusConfiguration.map(entry => `
        <button
            type="button"
            data-issue-status="${entry.status}"
            class="min-w-0 border border-slate-300 border-l-4 ${entry.border} bg-white p-3 text-left transition hover:bg-slate-50">

            <div
                class="whitespace-nowrap text-[10px] font-bold uppercase tracking-wide text-slate-500">
                ${esc(statusLabels[entry.status])}
            </div>

            <div
                class="mt-1 text-2xl font-extrabold ${entry.number}">
                ${counts[entry.status] || 0}
            </div>
        </button>
    `).join('');

    container
        .querySelectorAll('[data-issue-status]')
        .forEach(button => {
            button.addEventListener('click', () => {
                const statusFilter =
                    document.getElementById('issueStatusFilter');

                if (!statusFilter) {
                    return;
                }

                const clickedStatus =
                    button.dataset.issueStatus;

                statusFilter.value =
                    statusFilter.value === clickedStatus
                        ? ''
                        : clickedStatus;

                render();
            });
        });
}

/**
 * Aktualisiert die Ergebnisanzahl.
 */
function renderResultCount(count) {
    const resultCount =
        document.getElementById('issueResultCount');

    if (!resultCount) {
        return;
    }

    resultCount.textContent =
        `${count} von ${issues.length} Issues`;
}

/**
 * Liefert die ausgewählten IDs einer Mehrfachauswahl.
 */
function selected(elementId) {
    const element =
        document.getElementById(elementId);

    if (!element) {
        return [];
    }

    return Array.from(element.selectedOptions)
        .map(option => Number(option.value))
        .filter(value =>
            Number.isInteger(value) &&
            value > 0
        );
}

/**
 * Öffnet das Issue-Formular.
 */
function openForm(issue = null, detail = null) {
    const form = document.getElementById('issueForm');
    if (!form) return;

    form.reset();

    // NEU: Suchfeld leeren und UI-Tags zurücksetzen
    const initialReqSearch = document.getElementById('issueReqSearch');
    if (initialReqSearch) initialReqSearch.value = '';

    document.getElementById('issue_id').value = issue?.id || '';

    const fields = [
        'external_id', 'title', 'description', 'category',
        'reported_at', 'due_date', 'external_response',
        'internal_response', 'resolution'
    ];

    fields.forEach(fieldName => {
        const element = document.getElementById(`issue_${fieldName}`);
        if (element) {
            element.value = issue?.[fieldName] || '';
        }
    });

    document.getElementById('issue_type').value = issue?.issue_type || 'bug';
    document.getElementById('issue_status').value = issue?.status || 'open';
    document.getElementById('issue_priority').value = issue?.priority || 'medium';
    document.getElementById('issue_assignee').value = issue?.assignee_user_id || '';

    // --- NEUE TRACEABILITY LOGIK ---
    const linkedRequirements = detail?.requirements || [];
    const linkedTasks = detail?.tasks || [];

    const reqIds = linkedRequirements.map(r => r.requirement_id);
    const taskIds = linkedTasks.map(t => t.task_id);

    // Werte ins versteckte Feld schreiben
    const reqField = document.getElementById('issue_requirements');
    const taskField = document.getElementById('issue_tasks');

    if (reqField) reqField.value = reqIds.join(',');
    if (taskField) taskField.value = taskIds.join(',');

    const reqSearch = document.getElementById('issueReqSearch');
    const taskSearch = document.getElementById('issueTaskSearch');
    if (reqSearch) reqSearch.value = '';
    if (taskSearch) taskSearch.value = '';
    renderIssueRequirementList();
    renderIssueTaskList();

    document.getElementById('issueModalTitle').textContent = issue ? `${issue.issue_key} bearbeiten` : 'Neues Issue';

    // Modal zentriert öffnen
    const modal = document.getElementById('issueModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

/**
 * Schließt das Issue-Formular.
 */
function closeIssueModal() {
    document
        .getElementById('issueModal')
        ?.classList.add('hidden');
}

/**
 * Schließt das Importfenster.
 */
function closeImportModal() {
    document
        .getElementById('issueImportModal')
        ?.classList.add('hidden');
}

/**
 * Initialisiert alle Events.
 */
export function initIssueEvents() {
    document.getElementById('issueReqSearch')?.addEventListener('input', renderIssueRequirementList);
    document.getElementById('issueTaskSearch')?.addEventListener('input', renderIssueTaskList);
    document.getElementById('issueReqCheckboxList')?.addEventListener('change', event => {
        if (event.target.matches('.issue-req-cb')) syncIssueRequirementSelection();
    });
    document.getElementById('issueTaskCheckboxList')?.addEventListener('change', event => {
        if (event.target.matches('.issue-task-cb')) syncIssueTaskSelection();
    });


    document
        .getElementById('btnNewIssue')
        ?.addEventListener('click', () => {
            if (!currentProjectId) {
                alert('Bitte zuerst ein Projekt wählen.');
                return;
            }

            openForm();
        });

    document
        .getElementById('issueCancel')
        ?.addEventListener('click', closeIssueModal);

    document
        .getElementById('issueModalClose')
        ?.addEventListener('click', closeIssueModal);

    document
        .getElementById('issueSearch')
        ?.addEventListener('input', render);

    document
        .getElementById('issueStatusFilter')
        ?.addEventListener('change', render);

    document
        .getElementById('issueForm')
        ?.addEventListener('submit', save);

    document
        .getElementById('btnImportIssues')
        ?.addEventListener('click', () => {
            if (!currentProjectId) {
                alert('Bitte zuerst ein Projekt wählen.');
                return;
            }

            document
                .getElementById('issueImportModal')
                .classList.remove('hidden');
        });

    document
        .getElementById('issueImportCancel')
        ?.addEventListener('click', closeImportModal);

    document
        .getElementById('issueImportClose')
        ?.addEventListener('click', closeImportModal);

    document
        .getElementById('issueExcelFile')
        ?.addEventListener('change', readExcel);

    document
        .getElementById('issueImportConfirm')
        ?.addEventListener('click', doImport);
}

/**
 * Speichert ein Issue.
 */
async function save(event) {
    event.preventDefault();

    if (!currentProjectId) {
        alert('Bitte zuerst ein Projekt wählen.');
        return;
    }

    const submitButton =
        event.submitter;

    const payload = {
        id:
            document.getElementById('issue_id').value,

        project_id:
            currentProjectId,

        external_id:
            document.getElementById('issue_external_id').value,

        issue_type:
            document.getElementById('issue_type').value,

        title:
            document.getElementById('issue_title').value.trim(),

        description:
            document.getElementById('issue_description').value.trim(),

        status:
            document.getElementById('issue_status').value,

        priority:
            document.getElementById('issue_priority').value,

        severity:
            'medium',

        category:
            document.getElementById('issue_category').value.trim(),

        assignee_user_id:
            document.getElementById('issue_assignee').value,

        reported_at:
            document.getElementById('issue_reported_at').value,

        due_date:
            document.getElementById('issue_due_date').value,

        external_response:
            document.getElementById('issue_external_response').value.trim(),

        internal_response:
            document.getElementById('issue_internal_response').value.trim(),

        resolution:
            document.getElementById('issue_resolution').value.trim(),

        requirement_ids: document.getElementById('issue_requirements').value.split(',').filter(Boolean).map(Number),
        task_ids: document.getElementById('issue_tasks').value.split(',').filter(Boolean).map(Number)
    };

    if (!payload.title) {
        alert('Bitte einen Titel eingeben.');
        return;
    }

    const originalButtonText =
        submitButton?.textContent || 'Issue speichern';

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Speichert...';
    }

    try {
        const response =
            await fetch('../api/set_issue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

        const data =
            await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Issue konnte nicht gespeichert werden.'
            );
        }

        closeIssueModal();
        await loadIssues();
    } catch (error) {
        console.error(
            'Fehler beim Speichern des Issues:',
            error
        );

        alert(`Fehler: ${error.message}`);
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    }
}

/**
 * Öffnet ein bestehendes Issue.
 */
window.editIssue = async function (issueId) {
    const issue = issues.find(item =>
        Number(item.id) === Number(issueId)
    );

    if (!issue) {
        alert('Issue wurde nicht gefunden.');
        return;
    }

    try {
        const response = await fetch(
            `../api/get_issue.php?id=${encodeURIComponent(issueId)}`
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Issue konnte nicht geladen werden.'
            );
        }

        openForm(issue, data);
    } catch (error) {
        console.error(
            'Fehler beim Öffnen des Issues:',
            error
        );

        alert(`Fehler: ${error.message}`);
    }
};

/**
 * Löscht ein Issue.
 */
window.deleteIssue = async function (issueId) {
    const issue = issues.find(item =>
        Number(item.id) === Number(issueId)
    );

    const issueLabel =
        issue?.issue_key || `ID ${issueId}`;

    if (!confirm(`${issueLabel} wirklich löschen?`)) {
        return;
    }

    try {
        const response =
            await fetch('../api/delete_issue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: Number(issueId),
                    project_id: currentProjectId
                })
            });

        const data =
            await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Issue konnte nicht gelöscht werden.'
            );
        }

        await loadIssues();
    } catch (error) {
        console.error(
            'Fehler beim Löschen des Issues:',
            error
        );

        alert(`Fehler: ${error.message}`);
    }
};






/**
 * Normalisiert Excel-Spaltenüberschriften.
 */
function normalizeHeader(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[._-]/g, ' ')
        .replace(/\s+/g, ' ');
}

/**
 * Formatiert ein Excel-Datum.
 */
function formatImportDate(value) {
    if (
        value instanceof Date &&
        !Number.isNaN(value.getTime())
    ) {
        const day =
            String(value.getDate()).padStart(2, '0');

        const month =
            String(value.getMonth() + 1).padStart(2, '0');

        const year =
            value.getFullYear();

        return `${day}.${month}.${year}`;
    }

    return String(value ?? '').trim();
}

/**
 * Liest eine Excel- oder CSV-Datei.
 */
async function readExcel(event) {
    const file =
        event.target.files[0];

    if (!file) {
        return;
    }

    if (!window.XLSX) {
        alert(
            'Die Excel-Bibliothek konnte nicht geladen werden.'
        );
        return;
    }

    const confirmButton =
        document.getElementById('issueImportConfirm');

    if (confirmButton) {
        confirmButton.disabled = true;
    }

    try {
        const buffer =
            await file.arrayBuffer();

        const workbook =
            XLSX.read(buffer, {
                type: 'array',
                cellDates: true
            });

        let bestRows = [];
        let selectedSheetName = '';

        for (const sheetName of workbook.SheetNames) {
            const rows =
                XLSX.utils.sheet_to_json(
                    workbook.Sheets[sheetName],
                    {
                        header: 1,
                        defval: ''
                    }
                );

            if (rows.length > bestRows.length) {
                bestRows = rows;
                selectedSheetName = sheetName;
            }
        }

        let headerIndex = -1;

        for (
            let index = 0;
            index < Math.min(bestRows.length, 30);
            index++
        ) {
            const normalizedRow =
                bestRows[index].map(normalizeHeader);

            const hasIssueColumn =
                normalizedRow.includes('fehler');

            const hasStatusColumn =
                normalizedRow.includes('status');

            if (hasIssueColumn && hasStatusColumn) {
                headerIndex = index;
                break;
            }
        }

        if (headerIndex < 0) {
            throw new Error(
                'Eine Kopfzeile mit STATUS und Fehler wurde nicht gefunden.'
            );
        }

        const headers =
            bestRows[headerIndex].map(normalizeHeader);

        const findColumn = (...names) =>
            headers.findIndex(header =>
                names.includes(header)
            );

        const columns = {
            id:
                findColumn('#', 'nr', 'id'),

            date:
                findColumn('date', 'datum'),

            status:
                findColumn('status'),

            title:
                findColumn(
                    'fehler',
                    'meldung',
                    'issue'
                ),

            externalResponse:
                findColumn(
                    'rückmeldung epsa',
                    'rueckmeldung epsa'
                ),

            internalResponse:
                findColumn(
                    'spalte2',
                    'interne rückmeldung',
                    'rückmeldung intern'
                ),

            category:
                findColumn(
                    'category',
                    'kategorie'
                ),

            assignee:
                findColumn(
                    'assignee',
                    'zuweisung',
                    'verantwortlich'
                )
        };

        importRows = bestRows
            .slice(headerIndex + 1)
            .map((row, rowIndex) => ({
                external_id:
                    columns.id >= 0
                        ? row[columns.id]
                        : '',

                reported_at:
                    columns.date >= 0
                        ? formatImportDate(row[columns.date])
                        : '',

                status:
                    columns.status >= 0
                        ? row[columns.status]
                        : 'Open',

                title:
                    columns.title >= 0
                        ? row[columns.title]
                        : '',

                external_response:
                    columns.externalResponse >= 0
                        ? row[columns.externalResponse]
                        : '',

                internal_response:
                    columns.internalResponse >= 0
                        ? row[columns.internalResponse]
                        : '',

                category:
                    columns.category >= 0
                        ? row[columns.category]
                        : '',

                assignee:
                    columns.assignee >= 0
                        ? row[columns.assignee]
                        : '',

                source_row:
                    headerIndex + rowIndex + 2
            }))
            .filter(row =>
                String(row.title).trim() !== ''
            );

        importMeta = {
            filename: file.name,
            sheet_name: selectedSheetName,
            file_hash: await calculateSha256(buffer)
        };

        renderImportPreview();

        if (confirmButton) {
            confirmButton.disabled =
                importRows.length === 0;
        }
    } catch (error) {
        console.error(
            'Fehler beim Lesen der Excel-Datei:',
            error
        );

        alert(
            `Excel-Datei konnte nicht gelesen werden: ${error.message}`
        );

        importRows = [];
        importMeta = {};
        renderImportPreview();
    }
}

/**
 * Rendert die Importvorschau.
 */
function renderImportPreview() {
    const preview =
        document.getElementById('issueImportPreview');

    if (!preview) {
        return;
    }

    if (importRows.length === 0) {
        preview.innerHTML = `
            <div class="flex min-h-[110px] items-center justify-center italic text-slate-400">
                Keine importierbaren Zeilen gefunden.
            </div>
        `;
        return;
    }

    preview.innerHTML = `
        <div class="mb-3 flex items-center justify-between">
            <div class="font-bold text-slate-800">
                ${importRows.length} Einträge erkannt
            </div>

            <div class="text-xs text-slate-500">
                Tabellenblatt:
                ${esc(importMeta.sheet_name)}
            </div>
        </div>

        <div class="border border-slate-300 bg-white">
            ${importRows.slice(0, 8).map(row => `
                <div class="grid grid-cols-[55px_135px_1fr] gap-3 border-b border-slate-200 p-2 last:border-b-0">
                    <div class="font-mono font-bold text-blue-950">
                        ${esc(row.external_id)}
                    </div>

                    <div class="whitespace-nowrap text-xs font-bold text-slate-500">
                        ${esc(row.status)}
                    </div>

                    <div class="font-semibold text-slate-800">
                        ${esc(row.title)}
                    </div>
                </div>
            `).join('')}
        </div>

        ${importRows.length > 8
            ? `
                    <div class="mt-2 text-xs italic text-slate-500">
                        Vorschau zeigt 8 von
                        ${importRows.length} Einträgen.
                    </div>
                `
            : ''
        }
    `;
}

/**
 * Berechnet den Datei-Hash.
 */
async function calculateSha256(buffer) {
    const hashBuffer =
        await crypto.subtle.digest(
            'SHA-256',
            buffer
        );

    return Array.from(
        new Uint8Array(hashBuffer)
    )
        .map(byte =>
            byte.toString(16).padStart(2, '0')
        )
        .join('');
}

/**
 * Importiert die vorbereiteten Excel-Daten.
 */
async function doImport() {
    if (!currentProjectId) {
        alert('Bitte zuerst ein Projekt wählen.');
        return;
    }

    if (importRows.length === 0) {
        alert(
            'Es wurden keine importierbaren Einträge erkannt.'
        );
        return;
    }

    const confirmButton =
        document.getElementById('issueImportConfirm');

    if (confirmButton) {
        confirmButton.disabled = true;
        confirmButton.textContent = 'Import läuft...';
    }

    try {
        const response =
            await fetch('../api/import_issues.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    project_id: currentProjectId,
                    rows: importRows,
                    ...importMeta
                })
            });

        const data =
            await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Import fehlgeschlagen.'
            );
        }

        alert(
            `${data.imported} Issues importiert, ` +
            `${data.skipped} Einträge übersprungen.`
        );

        closeImportModal();

        const fileInput =
            document.getElementById('issueExcelFile');

        if (fileInput) {
            fileInput.value = '';
        }

        importRows = [];
        importMeta = {};

        renderImportPreview();
        await loadIssues();
    } catch (error) {
        console.error(
            'Fehler beim Issue-Import:',
            error
        );

        alert(`Import fehlgeschlagen: ${error.message}`);
    } finally {
        if (confirmButton) {
            confirmButton.disabled =
                importRows.length === 0;

            confirmButton.textContent =
                'Importieren';
        }
    }
}

/**
 * Öffnet das Slide-Over-Panel für den Issue-Bericht.
 */
window.viewIssueReport = async function (issueId) {
    const overlay = document.getElementById('issueReportOverlay');
    const panel = document.getElementById('issueReportPanel');

    if (!overlay || !panel) {
        return;
    }

    overlay.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-x-full');
    }, 10);

    document.getElementById('reportIssueKey').textContent = 'Lade...';
    document.getElementById('reportIssueTitle').textContent = 'Lade Bericht...';
    document.getElementById('issueReportBody').innerHTML = `
        <div class="py-12 text-center text-slate-400 italic">
            Bericht wird geladen...
        </div>
    `;

    try {
        const response = await fetch(
            `../api/get_issue.php?id=${encodeURIComponent(issueId)}`
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Bericht konnte nicht geladen werden.'
            );
        }

        const iss = data.issue || {};
        const linkedRequirements = data.requirements || [];
        const linkedTasks = data.tasks || [];

        document.getElementById('reportIssueKey').textContent =
            iss.issue_key || 'ISSUE';

        document.getElementById('reportIssueTitle').textContent =
            iss.title || 'Kein Titel';

        let html = `
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold uppercase">Status:</span>
                        <strong class="text-blue-950">${esc(statusLabels[iss.status] || iss.status)}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold uppercase">Typ:</span>
                        <strong class="text-blue-950">${esc(typeLabels[iss.issue_type] || iss.issue_type)}</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold uppercase">Priorität:</span>
                        <strong class="text-blue-950">${esc(priorityLabels[iss.priority] || iss.priority)}</strong>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                        Kategorie & Zuständigkeit
                    </div>
                    <div class="font-semibold text-slate-800">
                        ${esc(iss.category || '-')} · <span class="text-blue-900">Zuständig: ${esc(iss.assignee_name || iss.external_assignee || 'Niemand')}</span>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                        Fehlerbeschreibung / Meldung
                    </div>
                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-slate-800 whitespace-pre-wrap leading-relaxed">
                        ${formatIssueText(iss.description || 'Keine Beschreibung vorhanden.')}
                    </div>
                </div>
            </div>
        `;

        if (iss.external_response || iss.internal_response || iss.resolution) {
            html += `
                <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="border-b border-slate-200 pb-2 text-xs font-extrabold uppercase tracking-wider text-blue-950">
                        Kommunikation & Lösung
                    </h3>

                    ${iss.external_response
                    ? `
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Externe Rückmeldung</div>
                                <div class="rounded border border-slate-200 bg-slate-50 p-2.5 text-slate-700 whitespace-pre-wrap">${formatIssueText(iss.external_response)}</div>
                            </div>
                        `
                    : ''
                }

                    ${iss.internal_response
                    ? `
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Interne Rückmeldung</div>
                                <div class="rounded border border-slate-200 bg-slate-50 p-2.5 text-slate-700 whitespace-pre-wrap">${formatIssueText(iss.internal_response)}</div>
                            </div>
                        `
                    : ''
                }

                    ${iss.resolution
                    ? `
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 mb-1">Lösung / Abschlussinformation</div>
                                <div class="rounded border border-emerald-200 bg-emerald-50/50 p-2.5 font-medium text-emerald-950 whitespace-pre-wrap">${formatIssueText(iss.resolution)}</div>
                            </div>
                        `
                    : ''
                }
                </div>
            `;
        }

        html += `
            <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="border-b border-slate-200 pb-2 text-xs font-extrabold uppercase tracking-wider text-blue-950">
                    Traceability & Verknüpfungen
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Betroffene Reqs (${linkedRequirements.length})
                        </div>
                        ${linkedRequirements.length > 0
                ? linkedRequirements.map(r => `
                                <div class="mb-1 rounded border border-slate-200 bg-slate-50 p-2 font-mono text-xs">
                                    <strong class="text-blue-900">${esc(r.req_key)}</strong> — ${esc(r.title)}
                                </div>
                            `).join('')
                : '<div class="text-xs text-slate-400 italic">Keine Anforderungen verknüpft</div>'
            }
                    </div>

                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Umsetzungs-Tasks (${linkedTasks.length})
                        </div>
                        ${linkedTasks.length > 0
                ? linkedTasks.map(t => `
                                <div class="mb-1 rounded border border-slate-200 bg-slate-50 p-2 text-xs">
                                    <strong class="font-mono text-indigo-950">${esc(t.wbs_code || t.id)}</strong> — ${esc(t.title)}
                                </div>
                            `).join('')
                : '<div class="text-xs text-slate-400 italic">Keine Tasks verknüpft</div>'
            }
                    </div>
                </div>
            </div>
        `;

        document.getElementById('issueReportBody').innerHTML = html;

    } catch (error) {
        console.error('Fehler beim Laden des Berichts:', error);
        document.getElementById('issueReportBody').innerHTML = `
            <div class="p-4 font-bold text-red-600">
                Fehler: ${esc(error.message)}
            </div>
        `;
    }
};

/**
 * Schließt das Issue-Report-Panel.
 */
window.closeIssueReportPanel = function () {
    const overlay = document.getElementById('issueReportOverlay');
    const panel = document.getElementById('issueReportPanel');

    if (panel) {
        panel.classList.add('translate-x-full');
    }

    setTimeout(() => {
        if (overlay) {
            overlay.classList.add('hidden');
        }
    }, 300);
};
