<!-- dashboard/views/requirements.php -->
<div class="flex flex-col h-full">
    <div class="flex justify-between items-center mb-4">
        <!-- ID hinzugefügt, damit JS den Titel  ndern kann -->
        <h2 id="reqMainTitle" class="text-2xl font-bold text-blue-900">Anforderungen</h2>
        <button id="new"
            class="bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition rounded">
            + Neues Element
        </button>
    </div>
    <div class="grid gap-5 lg:grid-cols-[350px_1fr] flex-1 min-h-0">

        <aside class="rounded border bg-white shadow-sm overflow-hidden flex flex-col h-full">
            <!-- Interne Filter Tabs WURDEN ENTFERNT -->
            <div id="items" class="p-4 text-sm text-slate-500 overflow-y-auto flex-1">
                Bitte wähle oben ein Projekt aus.
            </div>
        </aside>

        <!-- Der restliche Code (<article id="detail"... und die Modals) bleibt absolut unverändert! -->
        <article id="detail" class="-lg border bg-white p-6 shadow-sm overflow-y-auto h-full relative">
            <div class="flex h-full items-center justify-center text-slate-400 italic">
                Wähle ein Element aus, um Details zu sehen.
            </div>
        </article>
    </div>
</div>

<!-- Modal für Neues Element -->
<div id="reqModal"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 sm:p-6 backdrop-blur-sm">
    <form id="reqForm"
        class="w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col space-y-4  bg-white p-6 sm:p-8 shadow-2xl">
        <h2 id="reqHeading" class="text-xl font-bold text-blue-900 border-b pb-3">Neues Element anlegen</h2>

        <div>
            <label class="text-sm font-semibold block mb-1 text-slate-700">Typ</label>
            <select id="type"
                class="w-full  border p-2 text-sm bg-slate-50 font-medium focus:border-blue-500 outline-none">
                <optgroup label="Ziele & Strategie">
                    <option value="GOAL">Ziel (Stakeholder Goal)</option>
                </optgroup>
                <optgroup label="Systemkontext & Architektur">
                    <option value="AST">Asset (Schützenswertes Gut)</option>
                </optgroup>
                <optgroup label="System & Architektur">
                    <option value="USR">User Requirement (USR)</option>
                    <option value="SYS">System Requirement (SYS)</option>
                    <option value="SEC">Security & Cyber Resilience (SEC)</option>
                    <option value="SRS">Software/Hardware Requirement (SRS)</option>
                    <option value="SWC">Komponente / Modul (SWC)</option>
                </optgroup>
                <optgroup label="Verifikation & Test">
                    <option value="TC">Test Case / Specification (TC)</option>
                    <option value="TR">Test Result / Protocol (TR)</option>
                </optgroup>
            </select>
        </div>

        <label class="block text-sm font-semibold text-slate-700">Titel / Name
            <input id="title" required class="mt-1 w-full  border p-2 font-normal focus:border-blue-500 outline-none">
        </label>

        <label class="block text-sm font-semibold text-slate-700">Beschreibung
            <textarea id="text" required rows="3"
                class="mt-1 w-full  border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold text-slate-700">Begründung (Rationale)
            <textarea id="rationale" rows="2"
                class="mt-1 w-full  border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <!-- Dynamische Attribute -->
        <div id="dynamicAttributes" class="hidden -lg bg-indigo-50 p-4 border border-indigo-100 shadow-inner mt-2">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-indigo-800">Spezifische Attribute</h3>
            <div id="attributeFields" class="grid gap-4 md:grid-cols-2"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <label class="block text-sm font-semibold text-slate-700">Zuständigkeit (Stakeholder)
                <select id="source_contact" class="mt-1 w-full  border p-2 font-normal outline-none bg-slate-50">
                    <option value="">-- Niemand zugewiesen --</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Aufwandschätzung
                <input id="effort" type="text" placeholder="z.B. 3 Tage / 5 Story Points"
                    class="mt-1 w-full  border p-2 font-normal outline-none">
            </label>
        </div>

        <div id="criteria_container" class="mt-2 transition-all">
            <label class="block text-sm font-semibold text-slate-700">Akzeptanzkriterien (Ein Kriterium pro Zeile)
                <textarea id="acceptance_criteria" rows="3" placeholder="- Kriterium 1&#10;- Kriterium 2"
                    class="mt-1 w-full  border p-2 font-normal outline-none"></textarea>
            </label>
        </div>

        <label class="block text-sm font-semibold text-slate-700 mt-2">Prüf-Status & Workflow
            <select id="review_status" class="mt-1 w-full  border p-2 font-normal bg-slate-50 outline-none">
                <option value="Neu">Neu</option>
                <option value="Wartet auf Überprüfung">Wartet auf Überprüfung</option>
                <option value="Geprüft & Freigegeben">Geprüft & Freigegeben</option>
                <option value="Abgelehnt">Abgelehnt</option>
            </select>
        </label>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-200 pt-5 mt-2">
            <div>
                <label class="text-sm font-semibold block mb-1 text-slate-700">Parents (Erfüllt)</label>
                <input type="text" id="parentSearch" placeholder="Suchen..."
                    class="w-full text-xs  border p-1.5 mb-2 bg-slate-50 outline-none"
                    oninput="window.filterCheckboxes('parentSearch', 'parentsCheckboxList')">
                <div id="parentsCheckboxList"
                    class="h-40 overflow-y-auto  border bg-white p-2 space-y-1 text-xs shadow-inner"></div>
            </div>
            <div>
                <label class="text-sm font-semibold block mb-1 text-slate-700">Children (Wird erfüllt durch)</label>
                <input type="text" id="childSearch" placeholder="Suchen..."
                    class="w-full text-xs  border p-1.5 mb-2 bg-slate-50 outline-none"
                    oninput="window.filterCheckboxes('childSearch', 'childrenCheckboxList')">
                <div id="childrenCheckboxList"
                    class="h-40 overflow-y-auto  border bg-white p-2 space-y-1 text-xs shadow-inner"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('reqModal').classList.add('hidden')"
                class=" border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
            <button type="submit"
                class=" bg-blue-900 px-5 py-2 font-medium text-white shadow hover:bg-blue-800 transition">Speichern</button>
        </div>
    </form>
</div>

<!-- Modal für Akzeptanzkriterien-Prüfung bleibt unverändert -->
<div id="verifyModal"
    class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="verifyForm" class="w-full max-w-md space-y-4  bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-blue-900 border-b pb-2">Kriterium prüfen</h2>
        <input type="hidden" id="verify_req_id">
        <input type="hidden" id="verify_crit_idx">
        <p id="verify_crit_text" class="text-sm font-semibold text-slate-800 bg-slate-100 p-3  border"></p>
        <label class="block text-sm font-semibold text-slate-700 mt-4">Notiz / Link zum Testprotokoll
            <textarea id="verify_note" required rows="3" placeholder="z.B. Test T-045 erfolgreich durchgeführt..."
                class="mt-1 w-full  border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>
        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('verifyModal').classList.add('hidden')"
                class=" border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
            <button type="submit"
                class=" bg-emerald-600 px-5 py-2 font-medium text-white shadow hover:bg-emerald-500 transition">Als
                'Geprüft' markieren</button>
        </div>
    </form>
</div>