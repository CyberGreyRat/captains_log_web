<!-- dashboard/views/usecases.php -->
<div class="rounded-lg border bg-white shadow-sm h-[calc(100vh-250px)] flex flex-col p-6 overflow-y-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Use Case Management</h2>
        <button id="btnNewUseCase"
            class="rounded bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neuer Use Case
        </button>
    </div>

    <!-- Tabelle für Use Cases -->
    <div class="overflow-x-auto border rounded-lg">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-800 uppercase font-semibold border-b">
                <tr>
                    <th class="p-3">Key</th>
                    <th class="p-3">Titel</th>
                    <th class="p-3">Primärer Akteur</th>
                    <th class="p-3">Hauptszenario (Auszug)</th>
                    <th class="p-3 text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody id="useCaseTableBody" class="divide-y">
                <tr>
                    <td colspan="5" class="p-4 text-center text-slate-400 italic">Bitte wähle ein Projekt aus.</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<!-- Use Case Modal (Neu & Bearbeiten) -->
<div id="modalUseCase"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formUseCase"
        class="w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white p-8 rounded-xl shadow-2xl space-y-4">
        <h2 id="useCaseModalTitle" class="text-xl font-bold text-blue-900 border-b pb-2">Neuer Use Case</h2>

        <input type="hidden" id="uc_id">

        <label class="block text-sm font-semibold">Titel / Name
            <input id="uc_title" required
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none">
        </label>

        <label class="block text-sm font-semibold">Primärer Akteur
            <input id="uc_primary_actor"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"
                placeholder="z.B. Systemadministrator, Kunde">
        </label>

        <label class="block text-sm font-semibold">Vorbedingungen (Preconditions)
            <textarea id="uc_preconditions" rows="2"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold">Hauptszenario (Erfolgsfall)
            <textarea id="uc_main_scenario" rows="3"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <label class="block text-sm font-semibold">Alternativ- & Fehlerszenarien
            <textarea id="uc_alt_scenario" rows="2"
                class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
        </label>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('modalUseCase').classList.add('hidden')"
                class="border px-4 py-2 rounded hover:bg-slate-50 transition">Abbrechen</button>
            <button type="submit"
                class="bg-blue-900 text-white px-5 py-2 rounded shadow hover:bg-blue-800 transition">Speichern</button>
        </div>
    </form>
</div>