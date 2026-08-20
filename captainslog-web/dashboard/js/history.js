// dashboard/js/history.js

import { currentProjectId } from './state.js';

let currentPage = 1;
let totalPages = 1;
let searchTimer = null;
let filtersInitializedForProject = null;

const entityLabels = {
    requirement: 'Anforderung',
    asset: 'Asset',
    goal: 'Ziel',
    issue: 'Issue',
    issue_comment: 'Issue-Kommentar',
    task: 'Aufgabe',
    stakeholder: 'Stakeholder',
    use_case: 'Use Case',
    user_story: 'User Story',
    project_member: 'Projektmitglied',
    sbom: 'SBOM',
    iso14001: 'ISO 14001',
    evidence: 'Nachweis',
    project: 'Projekt'
};

const actionLabels = {
    CREATE: 'Erstellt',
    UPDATE: 'Geändert',
    DELETE: 'Gelöscht',
    IMPORT: 'Importiert',
    LINK: 'Verknüpft',
    UNLINK: 'Verknüpfung entfernt',
    COMMENT: 'Kommentar',
    LOGIN: 'Anmeldung',
    EXPORT: 'Exportiert'
};

const actionClasses = {
    CREATE: 'cl-badge-success',
    UPDATE: 'cl-badge-info',
    DELETE: 'cl-badge-danger',
    IMPORT: 'cl-badge-warning',
    LINK: 'cl-badge-info',
    UNLINK: 'cl-badge-neutral',
    COMMENT: 'cl-badge-neutral',
    LOGIN: 'cl-badge-neutral',
    EXPORT: 'cl-badge-neutral'
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[character]));
}

function formatDate(value) {
    if (!value) return '-';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return escapeHtml(value);
    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'medium'
    }).format(date);
}

function displayValue(value) {
    if (value === null || value === undefined || value === '') return 'Leer';
    if (typeof value === 'object') return JSON.stringify(value, null, 2);
    return String(value);
}

function calculateChanges(oldData, newData) {
    const oldObject = oldData && typeof oldData === 'object' ? oldData : {};
    const newObject = newData && typeof newData === 'object' ? newData : {};
    const keys = [...new Set([...Object.keys(oldObject), ...Object.keys(newObject)])];

    return keys
        .filter(key => JSON.stringify(oldObject[key] ?? null) !== JSON.stringify(newObject[key] ?? null))
        .map(key => ({
            field: key,
            oldValue: oldObject[key] ?? null,
            newValue: newObject[key] ?? null
        }));
}

function renderChanges(entry) {
    if (entry.action === 'UPDATE') {
        const changes = calculateChanges(entry.old_data, entry.new_data);
        if (!changes.length) {
            return '<div class="text-xs italic text-slate-400">Keine Feldänderung ermittelt.</div>';
        }

        return `<div class="overflow-x-auto rounded-md border border-slate-200 bg-white">
            <table class="w-full min-w-[650px] text-left text-xs">
                <thead class="bg-slate-100 text-blue-950"><tr><th class="p-2">Feld</th><th class="p-2">Vorher</th><th class="p-2">Nachher</th></tr></thead>
                <tbody>${changes.map(change => `<tr class="border-t border-slate-200"><td class="p-2 font-bold text-slate-700">${escapeHtml(change.field)}</td><td class="max-w-[300px] whitespace-pre-wrap break-words bg-red-50/40 p-2 text-slate-600">${escapeHtml(displayValue(change.oldValue))}</td><td class="max-w-[300px] whitespace-pre-wrap break-words bg-emerald-50/40 p-2 text-slate-700">${escapeHtml(displayValue(change.newValue))}</td></tr>`).join('')}</tbody>
            </table>
        </div>`;
    }

    const data = entry.action === 'DELETE' ? entry.old_data : entry.new_data;
    if (!data) return '<div class="text-xs italic text-slate-400">Keine Detaildaten vorhanden.</div>';

    return `<pre class="max-h-80 overflow-auto rounded-md border border-slate-200 bg-slate-950 p-3 text-xs leading-5 text-slate-100">${escapeHtml(JSON.stringify(data, null, 2))}</pre>`;
}

function renderEntry(entry) {
    const entityLabel = entityLabels[entry.entity_type] || entry.entity_type || 'Objekt';
    const actionLabel = actionLabels[entry.action] || entry.action;
    const badgeClass = actionClasses[entry.action] || 'cl-badge-neutral';
    const title = entry.entity_title || entry.entity_key || `${entityLabel} ${entry.entity_id}`;

    return `<article class="cl-card overflow-hidden" data-history-entry="${Number(entry.id)}">
        <button type="button" class="history-entry-toggle flex w-full items-start justify-between gap-4 p-4 text-left hover:bg-slate-50" aria-expanded="false">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="cl-badge cl-badge-neutral">${escapeHtml(entityLabel)}</span>
                    <span class="cl-badge ${badgeClass}">${escapeHtml(actionLabel)}</span>
                    ${entry.entity_key ? `<span class="font-mono text-xs font-bold text-blue-950">${escapeHtml(entry.entity_key)}</span>` : ''}
                </div>
                <h3 class="mt-2 truncate text-sm font-extrabold text-slate-900">${escapeHtml(title)}</h3>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
                    <span>${escapeHtml(formatDate(entry.created_at))}</span>
                    <span>von <strong class="text-slate-700">${escapeHtml(entry.actor_name || 'System')}</strong></span>
                    <span>Quelle: ${escapeHtml(entry.source_type || 'database')}${entry.source_name ? ` / ${escapeHtml(entry.source_name)}` : ''}</span>
                    ${entry.hostname ? `<span>Host: ${escapeHtml(entry.hostname)}</span>` : ''}
                </div>
            </div>
            <span class="history-chevron shrink-0 text-xl text-slate-400">⌄</span>
        </button>
        <div class="history-entry-details hidden border-t border-slate-200 bg-slate-50 p-4">
            ${renderChanges(entry)}
            <div class="mt-3 grid grid-cols-1 gap-2 text-[11px] text-slate-500 md:grid-cols-3">
                <div><strong>Audit-ID:</strong> ${Number(entry.id)}</div>
                <div><strong>Request-ID:</strong> ${escapeHtml(entry.request_id || '-')}</div>
                <div><strong>Batch-ID:</strong> ${escapeHtml(entry.batch_id || '-')}</div>
            </div>
        </div>
    </article>`;
}

function bindEntryToggles(container) {
    container.querySelectorAll('.history-entry-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const article = button.closest('[data-history-entry]');
            const details = article?.querySelector('.history-entry-details');
            const chevron = article?.querySelector('.history-chevron');
            if (!details) return;

            const willOpen = details.classList.contains('hidden');
            details.classList.toggle('hidden');
            button.setAttribute('aria-expanded', String(willOpen));
            if (chevron) chevron.textContent = willOpen ? '⌃' : '⌄';
        });
    });
}

function getFilterValue(id) {
    return document.getElementById(id)?.value?.trim() || '';
}

function buildQuery(extra = {}) {
    const params = new URLSearchParams({
        project_id: currentProjectId || '',
        page: String(extra.page || currentPage),
        limit: getFilterValue('historyLimit') || String(extra.limit || 50)
    });

    const mapping = {
        historySearch: 'search',
        historyEntityFilter: 'entity_type',
        historyActionFilter: 'action',
        historyActorFilter: 'actor_user_id',
        historySourceFilter: 'source_type',
        historyDateFrom: 'date_from',
        historyDateTo: 'date_to'
    };

    Object.entries(mapping).forEach(([elementId, parameter]) => {
        const value = getFilterValue(elementId);
        if (value) params.set(parameter, value);
    });

    Object.entries(extra).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
    });

    return params;
}

function fillSelect(id, values, valueKey, labelKey, placeholder) {
    const select = document.getElementById(id);
    if (!select) return;
    const selected = select.value;
    select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>` + values.map(item => {
        const value = typeof item === 'object' ? item[valueKey] : item;
        const label = typeof item === 'object' ? item[labelKey] : (entityLabels[item] || item);
        return `<option value="${escapeHtml(value ?? '')}">${escapeHtml(label ?? 'System')}</option>`;
    }).join('');
    select.value = selected;
}

export async function loadHistory(page = 1) {
    const container = document.getElementById('historyContainer');
    if (!container) return;

    if (!currentProjectId) {
        container.innerHTML = '<div class="cl-empty-state">Bitte zuerst ein Projekt auswählen.</div>';
        return;
    }

    currentPage = page;
    container.innerHTML = '<div class="cl-empty-state">Historie wird geladen...</div>';

    try {
        const response = await fetch(`../api/get_history.php?${buildQuery({ page })}`);
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Historie konnte nicht geladen werden.');

        totalPages = data.pagination?.pages || 1;
        currentPage = data.pagination?.page || 1;

        document.getElementById('historyTotalCount').textContent = String(data.pagination?.total || 0);
        document.getElementById('historyPageInfo').textContent = `Seite ${currentPage} von ${totalPages}`;
        document.getElementById('historyPreviousPage').disabled = currentPage <= 1;
        document.getElementById('historyNextPage').disabled = currentPage >= totalPages;

        if (filtersInitializedForProject !== currentProjectId) {
            fillSelect('historyEntityFilter', data.filters?.entity_types || [], null, null, 'Alle Objekttypen');
            fillSelect('historySourceFilter', data.filters?.source_types || [], null, null, 'Alle Quellen');
            fillSelect('historyActorFilter', data.filters?.actors || [], 'id', 'name', 'Alle Benutzer');
            filtersInitializedForProject = currentProjectId;
        }

        if (!data.entries?.length) {
            container.innerHTML = '<div class="cl-empty-state">Keine Ereignisse für die gewählten Filter gefunden.</div>';
            return;
        }

        container.innerHTML = `<div class="space-y-3">${data.entries.map(renderEntry).join('')}</div>`;
        bindEntryToggles(container);
    } catch (error) {
        container.innerHTML = `<div class="cl-empty-state text-red-600">${escapeHtml(error.message)}</div>`;
    }
}

export function initHistoryEvents() {
    document.getElementById('historyRefresh')?.addEventListener('click', () => loadHistory(currentPage));
    document.getElementById('historyResetFilters')?.addEventListener('click', () => {
        ['historySearch', 'historyEntityFilter', 'historyActionFilter', 'historyActorFilter', 'historySourceFilter', 'historyDateFrom', 'historyDateTo'].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.value = '';
        });
        loadHistory(1);
    });

    ['historyEntityFilter', 'historyActionFilter', 'historyActorFilter', 'historySourceFilter', 'historyDateFrom', 'historyDateTo', 'historyLimit'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => loadHistory(1));
    });

    document.getElementById('historySearch')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadHistory(1), 350);
    });

    document.getElementById('historyPreviousPage')?.addEventListener('click', () => {
        if (currentPage > 1) loadHistory(currentPage - 1);
    });

    document.getElementById('historyNextPage')?.addEventListener('click', () => {
        if (currentPage < totalPages) loadHistory(currentPage + 1);
    });
}

export async function renderHistory(reqKey) {
    const detail = document.getElementById('detail');
    if (!detail || !currentProjectId || !reqKey) return;

    detail.querySelector('#requirementAuditTrail')?.remove();
    const section = document.createElement('section');
    section.id = 'requirementAuditTrail';
    section.className = 'mt-6 border-t border-slate-200 pt-5';
    section.innerHTML = '<div class="cl-empty-state">Audit-Trail wird geladen...</div>';
    detail.appendChild(section);

    try {
        const params = new URLSearchParams({
            project_id: currentProjectId,
            entity_key: reqKey,
            page: '1',
            limit: '100'
        });
        const response = await fetch(`../api/get_history.php?${params}`);
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Audit-Trail konnte nicht geladen werden.');

        section.innerHTML = `<div class="mb-3 flex items-center justify-between"><div><p class="cl-panel-eyebrow">Nachvollziehbarkeit</p><h3 class="text-lg font-extrabold text-blue-950">Historie & Audit-Trail</h3></div><span class="cl-badge cl-badge-neutral">${data.pagination?.total || 0} Ereignisse</span></div>` +
            (data.entries?.length ? `<div class="space-y-3">${data.entries.map(renderEntry).join('')}</div>` : '<div class="cl-empty-state">Noch keine Audit-Einträge vorhanden.</div>');
        bindEntryToggles(section);
    } catch (error) {
        section.innerHTML = `<div class="cl-empty-state text-red-600">${escapeHtml(error.message)}</div>`;
    }
}
