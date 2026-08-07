<!-- dashboard/views/project_plan.php -->
<div class="border bg-white shadow-sm h-full flex flex-col p-6 overflow-y-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Projektplan & Aufgaben</h2>
        <button id="btnNewTask"
            class="bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition rounded">
            + Neue Aufgabe
        </button>
    </div>

    <!-- Task Tabelle -->
    <div class="overflow-x-auto border border-slate-200 pb-32 rounded">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-800 uppercase font-bold border-b">
                <tr>
                    <th class="p-4 w-20">ID</th>
                    <th class="p-4">Aufgabe / Kommentare</th>
                    <th class="p-4 w-32">Zuweisung</th>
                    <th class="p-4 text-center w-24">Aufwand</th>
                    <th class="p-4 text-center w-40">Zeitraum</th>
                    <th class="p-4 w-48">Fortschritt</th>
                    <th class="p-4 text-right w-32">Aktionen</th>
                </tr>
            </thead>
            <tbody id="taskTableBody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="7" class="p-4 text-center text-slate-400 italic">Bitte Projekt auswählen.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal für Aufgaben -->
<div id="modalTask"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formTask"
        class="w-full max-w-4xl max-h-[95vh] overflow-y-auto bg-white p-8 shadow-2xl rounded-sm space-y-6">
        <h2 id="taskModalTitle" class="text-2xl font-bold text-blue-950 border-b-2 border-blue-900 pb-3">Aufgabe
            bearbeiten</h2>

        <input type="hidden" id="task_id">

        <!-- KLARES 2-SCHRITT KATALOG MENÜ -->
        <div class="bg-blue-50/50 p-5 border border-blue-200 rounded-md">
            <h3 class="text-sm font-extrabold text-blue-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                Aus Vorlage laden (Katalog)
            </h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-blue-900 mb-1.5 uppercase tracking-wide">1. Hauptgruppe
                        wählen</label>
                    <select id="tpl_category"
                        class="w-full border border-slate-300 p-2.5 font-semibold text-slate-800 bg-white shadow-sm outline-none transition"></select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-900 mb-1.5 uppercase tracking-wide">2. Aufgabe
                        wählen</label>
                    <select id="tpl_item" disabled
                        class="w-full border border-slate-300 p-2.5 font-semibold text-slate-800 bg-slate-100 shadow-sm outline-none transition cursor-not-allowed"></select>
                </div>
            </div>
        </div>

        <!-- FORMULAR FELDER (Ohne Obergruppen-Dropdown) -->
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-3">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">ID (Automatisch)</label>
                <input id="task_wbs" readonly
                    class="w-full border border-slate-300 p-2.5 font-bold bg-slate-100 text-slate-600 shadow-inner outline-none">
            </div>
            <div class="col-span-9">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Kategorie / Bereich</label>
                <input id="task_category" required
                    class="w-full border border-slate-400 p-2.5 font-semibold text-slate-900 bg-white shadow-sm outline-none">
            </div>

            <div class="col-span-12">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Titel der Aufgabe</label>
                <input id="task_title" required
                    class="w-full border border-slate-400 p-2.5 font-semibold text-slate-900 bg-white shadow-sm outline-none text-lg">
            </div>

            <div class="col-span-12">
                <label class="block text-sm font-bold text-slate-900 mb-1.5 flex justify-between">
                    <span>Kommentare / Checkliste</span>
                    <span class="text-xs text-slate-500 font-normal italic">Tipp: Zeilen mit "-- " werden magisch zu
                        Unteraufgaben!</span>
                </label>
                <textarea id="task_description" rows="3"
                    class="w-full border border-slate-400 p-2.5 font-medium text-slate-700 bg-white shadow-sm outline-none focus:border-blue-500 transition"
                    placeholder="z.B. -- pcb 3d in blender visualisieren"></textarea>
            </div>

            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Zuweisung (Assignee)</label>
                <input id="task_assignee"
                    class="w-full border border-slate-400 p-2.5 font-medium text-slate-900 bg-white shadow-sm outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4 col-span-12 md:col-span-6">
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1.5">Startdatum</label>
                    <input type="date" id="task_start"
                        class="w-full border border-slate-400 p-2.5 font-medium text-slate-900 bg-white shadow-sm outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1.5">Enddatum</label>
                    <input type="date" id="task_end"
                        class="w-full border border-slate-400 p-2.5 font-medium text-slate-900 bg-white shadow-sm outline-none">
                </div>
            </div>

            <div class="col-span-6">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Aufwand (in Stunden)</label>
                <input type="number" step="0.1" id="task_effort"
                    class="w-full border border-slate-400 p-2.5 font-medium text-slate-900 bg-white shadow-sm outline-none">
            </div>
            <div class="col-span-6">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Performance (%)</label>
                <input type="number" id="task_performance" value="100"
                    class="w-full border border-slate-400 p-2.5 font-medium text-slate-900 bg-white shadow-sm outline-none">
            </div>
        </div>

        <hr class="my-6 border-slate-300">

        <!-- Automatik & Desktop Hover Menü -->
        <div class="bg-slate-50 p-5 border border-slate-300 rounded-md shadow-sm">
            <label class="flex items-center gap-3 text-base font-bold text-slate-900 cursor-pointer mb-4">
                <input type="checkbox" id="task_is_auto" checked
                    class="w-5 h-5 text-blue-900 focus:ring-blue-500 rounded cursor-pointer">
                Fortschritt automatisch aus Anforderungen berechnen
            </label>

            <div id="container_linked_reqs" class="transition-all relative">
                <label class="block text-sm font-bold text-slate-900 mb-2">Verknüpfte Anforderungen</label>
                <div id="task_selected_reqs_container"
                    class="min-h-[48px] p-3 border border-slate-300 bg-white mb-3 flex flex-wrap gap-2 rounded shadow-inner">
                </div>

                <div id="reqMenuContainer" class="relative z-[150]">
                    <!-- JS füllt das Dropdown hier -->
                </div>
                <input type="hidden" id="task_linked_reqs">
            </div>

            <div id="container_manual_progress" class="hidden transition-all mt-4">
                <label class="block text-sm font-bold text-slate-900 mb-1.5">Manueller Fortschritt (%)</label>
                <input type="number" id="task_progress" min="0" max="100" value="0"
                    class="w-full max-w-xs border border-slate-400 p-2.5 font-bold text-slate-900 bg-white shadow-sm outline-none">
            </div>
        </div>

        <div class="flex justify-end gap-4 mt-8 pt-4">
            <button type="button" onclick="document.getElementById('modalTask').classList.add('hidden')"
                class="border border-slate-400 px-6 py-2.5 rounded hover:bg-slate-100 transition font-bold text-slate-700">Abbrechen</button>
            <button type="submit"
                class="bg-blue-900 text-white px-8 py-2.5 rounded shadow-lg hover:bg-blue-800 transition font-bold text-lg">Speichern</button>
        </div>
    </form>
</div>


<!-- Slide-over Panel für Analytics -->
<div id="analyticsPanelOverlay"
    class="hidden fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-[200] transition-opacity cursor-pointer"
    onclick="window.closeAnalyticsPanel()"></div>
<div id="analyticsPanel"
    class="fixed top-0 right-0 h-full w-full max-w-md bg-slate-50 shadow-2xl z-[210] transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-300">

    <!-- Header -->
    <div class="bg-blue-950 p-6 text-white flex justify-between items-start shadow-md">
        <div>
            <div class="text-blue-300 text-xs font-bold tracking-widest uppercase mb-1">Performance Analyse</div>
            <h2 id="analyticsTitle" class="text-xl font-extrabold leading-tight">Lade Daten...</h2>
        </div>
        <button onclick="window.closeAnalyticsPanel()"
            class="text-slate-300 hover:text-white transition bg-white/10 p-2 rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <!-- NEU: Checkliste (Unterpunkte aus --) -->
    <div id="analyticsChecklistContainer" class="hidden">
        <h3 class="text-sm font-bold text-slate-900 mb-3 border-b border-slate-200 pb-2">Checkliste (Unteraufgaben)</h3>
        <div id="analyticsChecklist" class="space-y-2">
            <!-- Wird per JS gefüllt -->
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-8">

        <!-- Gesamtfortschritt -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-900 mb-4">Fortschritt nach Anforderungen</h3>
            <div class="flex justify-between items-end mb-2">
                <span id="analyticsReqCount" class="text-xs font-semibold text-slate-500">0 / 0 Freigegeben</span>
                <span id="analyticsTotalProgress" class="text-2xl font-extrabold text-blue-900">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 border border-slate-200">
                <div id="analyticsProgressBar" class="bg-emerald-500 h-3 rounded-full transition-all duration-1000 w-0">
                </div>
            </div>
        </div>

        <!-- Beitragende Entwickler (Contributors) -->
        <div>
            <h3 class="text-sm font-bold text-slate-900 mb-3 border-b border-slate-200 pb-2">Top Contributors</h3>
            <div id="analyticsContributors" class="space-y-4">
                <!-- Wird per JS gefüllt -->
            </div>
        </div>

        <!-- Anforderungs-Log -->
        <div>
            <h3 class="text-sm font-bold text-slate-900 mb-3 border-b border-slate-200 pb-2">Detail-Log</h3>
            <div id="analyticsReqList" class="space-y-2">
                <!-- Wird per JS gefüllt -->
            </div>
        </div>

    </div>
</div>