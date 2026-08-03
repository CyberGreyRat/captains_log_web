<!-- dashboard/views/requirements.php -->
<div class="flex flex-col h-full">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-blue-900">System- & Softwareanforderungen</h2>
        <button id="new"
            class="rounded bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neue Anforderung
        </button>
    </div>

    <div class="grid gap-5 lg:grid-cols-[350px_1fr] flex-1 min-h-0">
        <aside class="rounded-lg border bg-white shadow-sm overflow-y-auto h-full">
            <div id="items" class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>
        </aside>
        <article id="detail" class="rounded-lg border bg-white p-6 shadow-sm overflow-y-auto h-full relative">
            <div class="flex h-full items-center justify-center text-slate-400 italic">
                Wähle einen Eintrag aus dem Baum aus, um Details zu sehen.
            </div>
        </article>
    </div>
</div>

<!-- dashboard/views/requirements.php -->
<div class="flex flex-col h-full">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-blue-900">Ziele & Anforderungen</h2>
        <button id="new"
            class="rounded bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neuer Eintrag
        </button>
    </div>
    <div class="grid gap-5 lg:grid-cols-[350px_1fr] flex-1 min-h-0">
        <aside class="rounded-lg border bg-white shadow-sm overflow-y-auto h-full">
            <div id="items" class="p-4 text-sm text-slate-500">Bitte wähle oben ein Projekt aus.</div>
        </aside>
        <article id="detail" class="rounded-lg border bg-white p-6 shadow-sm overflow-y-auto h-full relative">
            <div class="flex h-full items-center justify-center text-slate-400 italic">
                Wähle einen Eintrag aus dem Baum aus, um Details zu sehen.
            </div>
        </article>
    </div>
</div>

<!-- Modal für Neue Anforderungen & Ziele -->
<div id="reqModal"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 sm:p-6 backdrop-blur-sm">
    <form id="reqForm"
        class="w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col space-y-4 rounded-xl bg-white p-6 sm:p-8 shadow-2xl">
        <h2 id="reqHeading" class="text-xl font-bold text-blue-900 border-b pb-3">Neuer Eintrag</h2>

        <div>
            <label class="text-sm font-semibold block mb-1 text-slate-700">Typ</label>
            <select id="type"
                class="w-full rounded border p-2 text-sm bg-slate-50 font-medium focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                <optgroup label="Ziele & Strategie">
                    <option value="GOAL">Ziel (Stakeholder Goal)</option>
                </optgroup>
                <optgroup label="System & Architektur">
                    <option value="USR">User Requirement (USR)</option>
                    <option value="SYS">System Requirement (SYS)</option>
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
            <input id="title" required
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none">
        </label>

        <label class="block text-sm font-semibold text-slate-700">Beschreibung
            <textarea id="text" required rows="3"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold text-slate-700">Begründung (Rationale)
            <textarea id="rationale" rows="2"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <label class="block text-sm font-semibold text-slate-700">Stakeholder (Ziel-Besitzer)
                <select id="source_contact" class="mt-1 w-full rounded border p-2 font-normal outline-none bg-slate-50">
                    <option value="">-- Kein Stakeholder --</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Aufwandschätzung
                <input id="effort" type="text" placeholder="z.B. 3 Tage / 5 Story Points"
                    class="mt-1 w-full rounded border p-2 font-normal outline-none">
            </label>
        </div>

        <label class="block text-sm font-semibold text-slate-700 mt-2">Akzeptanzkriterien (Ein Kriterium pro Zeile)
            <textarea id="acceptance_criteria" rows="3" placeholder="- Kriterium 1&#10;- Kriterium 2"
                class="mt-1 w-full rounded border p-2 font-normal outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold text-slate-700 mt-2">Prüf-Status & Workflow
            <select id="review_status" class="mt-1 w-full rounded border p-2 font-normal bg-slate-50 outline-none">
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
                    class="w-full text-xs rounded border p-1.5 mb-2 bg-slate-50 outline-none"
                    oninput="filterCheckboxes('parentSearch', 'parentsCheckboxList')">
                <div id="parentsCheckboxList"
                    class="h-40 overflow-y-auto rounded border bg-white p-2 space-y-1 text-xs shadow-inner"></div>
            </div>
            <div>
                <label class="text-sm font-semibold block mb-1 text-slate-700">Children (Wird erfüllt durch)</label>
                <input type="text" id="childSearch" placeholder="Suchen..."
                    class="w-full text-xs rounded border p-1.5 mb-2 bg-slate-50 outline-none"
                    oninput="filterCheckboxes('childSearch', 'childrenCheckboxList')">
                <div id="childrenCheckboxList"
                    class="h-40 overflow-y-auto rounded border bg-white p-2 space-y-1 text-xs shadow-inner"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('reqModal').classList.add('hidden')"
                class="rounded border px-4 py-2 hover:bg-slate-50 font-medium transition">Abbrechen</button>
            <button type="submit"
                class="rounded bg-blue-900 px-5 py-2 font-medium text-white shadow hover:bg-blue-800 transition">Speichern</button>
        </div>
    </form>
</div>
<script>
    document.getElementById('cancelReq').addEventListener('click', () => {
        document.getElementById('reqModal').classList.add('hidden');
    });
</script>