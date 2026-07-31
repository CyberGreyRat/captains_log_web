<!-- dashboard/views/userstories.php -->
<div class="rounded-lg border bg-white shadow-sm h-full flex flex-col p-6 overflow-y-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">User Stories</h2>
        <button id="btnNewUserStory" class="rounded bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neue User Story
        </button>
    </div>

    <!-- Tabelle -->
    <div class="overflow-x-auto border rounded-lg">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-800 uppercase font-semibold border-b">
                <tr>
                    <th class="p-3">Key</th>
                    <th class="p-3">Titel</th>
                    <th class="p-3">Als (Rolle)</th>
                    <th class="p-3">möchte ich (Aktion)</th>
                    <th class="p-3">Story Points</th>
                    <th class="p-3 text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody id="userStoryTableBody" class="divide-y">
                <tr>
                    <td colspan="6" class="p-4 text-center text-slate-400 italic">Bitte wähle ein Projekt aus.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- User Story Modal -->
<div id="modalUserStory" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formUserStory" class="w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white p-8 rounded-xl shadow-2xl space-y-4">
        <h2 id="userStoryModalTitle" class="text-xl font-bold text-blue-900 border-b pb-2">Neue User Story</h2>
        
        <input type="hidden" id="us_id">
        
        <label class="block text-sm font-semibold">Titel (Kurzbezeichnung)
            <input id="us_title" required class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none">
        </label>
        
        <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg space-y-4 my-4">
            <label class="block text-sm font-semibold text-blue-900">Als (Rolle/Person)
                <input id="us_role" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none" placeholder="z.B. Endanwender">
            </label>
            
            <label class="block text-sm font-semibold text-blue-900">möchte ich (Ziel/Aktion)
                <textarea id="us_action" rows="2" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
            </label>
            
            <label class="block text-sm font-semibold text-blue-900">so dass (Nutzen/Mehrwert)
                <textarea id="us_benefit" rows="2" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none"></textarea>
            </label>
        </div>
        
        <label class="block text-sm font-semibold">Akzeptanzkriterien
            <textarea id="us_acceptance_criteria" rows="3" class="mt-1 w-full rounded border p-2 font-normal focus:border-blue-500 outline-none" placeholder="- Kriterium 1...&#10;- Kriterium 2..."></textarea>
        </label>
        
        <label class="block text-sm font-semibold">Story Points (Aufwand)
            <input id="us_story_points" type="number" class="mt-1 w-32 rounded border p-2 font-normal focus:border-blue-500 outline-none">
        </label>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('modalUserStory').classList.add('hidden')" class="border px-4 py-2 rounded hover:bg-slate-50 transition">Abbrechen</button>
            <button type="submit" class="bg-blue-900 text-white px-5 py-2 rounded shadow hover:bg-blue-800 transition">Speichern</button>
        </div>
    </form>
</div>