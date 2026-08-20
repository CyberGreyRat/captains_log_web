<!-- dashboard/views/history.php -->
<div class="cl-panel">
    <div class="cl-panel-header">
        <div>
            <p class="cl-panel-eyebrow">Audit-Trail · Änderungen · Nachvollziehbarkeit</p>
            <h2 class="cl-panel-title">Projekt-Historie</h2>
        </div>
        <button id="historyRefresh" type="button" class="cl-button cl-button-secondary">Aktualisieren</button>
    </div>

    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">
        <div class="cl-kpi">
            <div class="cl-kpi-label">Gefundene Ereignisse</div>
            <div id="historyTotalCount" class="cl-kpi-value">0</div>
        </div>
        <div class="cl-kpi border-l-blue-500">
            <div class="cl-kpi-label">Audit-Trail</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Webseite, API, Import und direkte SQL-Änderungen.</div>
        </div>
        <div class="cl-kpi border-l-amber-500">
            <div class="cl-kpi-label">Nachvollziehbarkeit</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Alter und neuer Zustand können direkt verglichen werden.</div>
        </div>
    </div>

    <div class="shrink-0 border-b border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-7">
            <label class="xl:col-span-2">
                <span class="sr-only">Historie durchsuchen</span>
                <input id="historySearch" type="search" class="cl-input mt-0" placeholder="Key, Titel, Benutzer, Quelle oder Inhalt suchen">
            </label>

            <label>
                <span class="sr-only">Objekttyp</span>
                <select id="historyEntityFilter" class="cl-select mt-0">
                    <option value="">Alle Objekttypen</option>
                </select>
            </label>

            <label>
                <span class="sr-only">Aktion</span>
                <select id="historyActionFilter" class="cl-select mt-0">
                    <option value="">Alle Aktionen</option>
                    <option value="CREATE">Erstellt</option>
                    <option value="UPDATE">Geändert</option>
                    <option value="DELETE">Gelöscht</option>
                    <option value="IMPORT">Importiert</option>
                    <option value="LINK">Verknüpft</option>
                    <option value="UNLINK">Verknüpfung entfernt</option>
                    <option value="COMMENT">Kommentar</option>
                    <option value="EXPORT">Exportiert</option>
                </select>
            </label>

            <label>
                <span class="sr-only">Benutzer</span>
                <select id="historyActorFilter" class="cl-select mt-0">
                    <option value="">Alle Benutzer</option>
                </select>
            </label>

            <label>
                <span class="sr-only">Quelle</span>
                <select id="historySourceFilter" class="cl-select mt-0">
                    <option value="">Alle Quellen</option>
                </select>
            </label>

            <button id="historyResetFilters" type="button" class="cl-button cl-button-secondary">Filter löschen</button>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
            <label class="cl-label">
                Von
                <input id="historyDateFrom" type="date" class="cl-input">
            </label>
            <label class="cl-label">
                Bis
                <input id="historyDateTo" type="date" class="cl-input">
            </label>
            <label class="cl-label">
                Einträge pro Seite
                <select id="historyLimit" class="cl-select">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </label>
        </div>
    </div>

    <div id="historyContainer" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4">
        <div class="cl-empty-state">Bitte zuerst ein Projekt auswählen.</div>
    </div>

    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-300 bg-[#eef2f6] px-4 py-3">
        <span id="historyPageInfo" class="text-xs font-semibold text-slate-600">Seite 1 von 1</span>
        <div class="flex gap-2">
            <button id="historyPreviousPage" type="button" class="cl-button cl-button-secondary" disabled>Zurück</button>
            <button id="historyNextPage" type="button" class="cl-button cl-button-secondary" disabled>Weiter</button>
        </div>
    </div>
</div>
