<!-- dashboard/views/risks.php -->
<div class="rounded-lg border bg-white shadow-sm h-full flex flex-col p-6 overflow-y-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Risikomanagement</h2>
        <button id="btnNewRisk" class="rounded bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neues Risiko
        </button>
    </div>

    <!-- Excel-Style Tabelle -->
  <!-- Excel-Style Tabelle -->
    <div class="overflow-x-auto border rounded-lg mb-8">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-800 font-semibold border-b">
                <tr>
                    <th class="p-2 border-r">Datum</th>
                    <th class="p-2 border-r">Nr.</th>
                    <th class="p-2 border-r w-1/4">Risiko</th>
                    <th class="p-2 border-r" title="Wahrscheinlichkeit">W</th>
                    <th class="p-2 border-r" title="Auswirkung">E</th>
                    <th class="p-2 border-r" title="Risikozahl">R</th>
                    <th class="p-2 border-r">Verantw.</th>
                    <th class="p-2 border-r">Termin</th>
                    <th class="p-2 border-r">Mitigations-Strat.</th>
                    <th class="p-2 border-r">Entscheidung</th>
                    <th class="p-2 border-r">Auswirkung</th>
                    <th class="p-2 text-center">Aktionen</th>
                </tr>
            </thead>
            <tbody id="riskTableBody" class="divide-y">
                <tr><td colspan="12" class="p-4 text-center text-slate-400 italic">Bitte Projekt auswählen.</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Visuelle Risiko Matrix (5x5) -->
    <h3 class="text-xl font-bold text-blue-900 mb-4 border-t pt-6">Risiko Matrix (W x E)</h3>
    <div class="relative w-full max-w-lg aspect-square self-center border-l-2 border-b-2 border-slate-800 bg-slate-50">
        <div class="absolute -left-8 top-1/2 -translate-y-1/2 -rotate-90 text-sm font-bold text-slate-600">Auswirkung (E)</div>
        <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-sm font-bold text-slate-600">Wahrscheinlichkeit (W)</div>
        
        <!-- 5x5 Grid Background -->
        <div class="absolute inset-0 grid grid-cols-5 grid-rows-5 opacity-80">
            <!-- Row 5 (Schlimmste Auswirkung) -->
            <div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div><div class="bg-red-600"></div><div class="bg-red-700"></div>
            <!-- Row 4 -->
            <div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div><div class="bg-red-600"></div>
            <!-- Row 3 -->
            <div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div>
            <!-- Row 2 -->
            <div class="bg-emerald-500"></div><div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div>
            <!-- Row 1 (Geringste Auswirkung) -->
            <div class="bg-emerald-500"></div><div class="bg-emerald-500"></div><div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div>
        </div>
        <!-- Dots container -->
        <div id="riskMapPoints" class="absolute inset-0"></div>
    </div>
</div>

<!-- Risiko Formular Modal -->
<div id="modalRisk" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formRisk" class="w-full max-w-4xl max-h-[90vh] overflow-y-auto bg-white p-8 rounded shadow-2xl space-y-4">
        <h2 id="riskModalTitle" class="text-xl font-bold text-blue-900 border-b pb-2">Neues Risiko erfassen</h2>
        
        <input type="hidden" id="risk_id">
        <input type="hidden" id="risk_key">

        <label class="block text-sm font-semibold">Risiko-Beschreibung (Titel)
            <input id="risk_title" required class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none">
        </label>

        <div class="grid grid-cols-3 gap-4 bg-slate-50 p-4 border rounded">
            <label class="block text-sm font-semibold text-blue-900">Wahrscheinlichkeit (W: 1-5)
                <input id="risk_w" type="number" min="1" max="5" value="1" required class="mt-1 w-full rounded border p-2 font-normal outline-none">
            </label>
            <label class="block text-sm font-semibold text-blue-900">Auswirkung (E: 1-5)
                <input id="risk_e" type="number" min="1" max="5" value="1" required class="mt-1 w-full rounded border p-2 font-normal outline-none">
            </label>
            <label class="block text-sm font-semibold text-slate-500">Verantwortlich (Stakeholder)
                <select id="risk_responsible" class="mt-1 w-full rounded border p-2 font-normal outline-none bg-white"></select>
            </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <label class="block text-sm font-semibold">Review-Termin
                <input id="risk_date" type="date" class="mt-1 w-full rounded border p-2 font-normal outline-none">
            </label>
            <label class="block text-sm font-semibold">Entscheidung
                <input id="risk_decision" class="mt-1 w-full rounded border p-2 font-normal outline-none" placeholder="z.B. Lieferkette verfolgen">
            </label>
        </div>

        <label class="block text-sm font-semibold">Mitigations-Strategie
            <textarea id="risk_mitigation" rows="2" class="mt-1 w-full rounded border p-2 font-normal outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold">Auswirkung (Effect / Details)
            <textarea id="risk_effect" rows="2" class="mt-1 w-full rounded border p-2 font-normal outline-none" placeholder="z.B. Zeit: kein Mehraufwand..."></textarea>
        </label>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('modalRisk').classList.add('hidden')" class="border rounded px-4 py-2 hover:bg-slate-50 transition">Abbrechen</button>
            <button type="submit" class="bg-blue-900 rounded text-white px-5 py-2 shadow hover:bg-blue-800 transition">Speichern</button>
        </div>
    </form>
</div>


<!-- Risiko Archivieren (Löschen) Bestätigungs-Modal -->
<div id="riskArchiveModal" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md p-6 transform transition-all">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Risiko archivieren?</h3>
                <p class="text-xs text-slate-500">Dieser Vorgang entfernt das Risiko aus der Übersicht.</p>
            </div>
        </div>
        <p class="text-sm text-slate-600 mb-6">
            Möchtest du das Risiko <span id="modalArchiveRiskName" class="font-bold text-red-600"></span> wirklich archivieren? Aus Revisionsgründen bleibt es in der Historie erhalten.
        </p>
        <div class="flex justify-end space-x-3">
            <button id="modalRiskCancelBtn" type="button" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Abbrechen</button>
            <button id="modalRiskConfirmBtn" type="button" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-sm">Ja, archivieren</button>
        </div>
    </div>
</div>