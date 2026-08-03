<!-- dashboard/views/stakeholders.php -->
<div class="-lg border bg-white shadow-sm h-[calc(100vh-250px)] flex flex-col p-6 overflow-y-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Stakeholder Management</h2>
        <button id="btnNewStakeholder" class=" bg-blue-900 px-4 py-2 font-semibold text-white shadow hover:bg-blue-800 transition">
            + Neuer Stakeholder
        </button>
    </div>

    <!-- Die flache Tabelle für alle Details -->
    <div class="overflow-x-auto mb-8 border -lg">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-800 uppercase font-semibold border-b">
                <tr>
                    <th class="p-3">Name</th>
                    <th class="p-3">Rolle</th>
                    <th class="p-3">Position</th>
                    <th class="p-3">Kontakt</th>
                    <th class="p-3">Wissen / Verfügbarkeit</th>
                    <th class="p-3">Einfluss / Interesse</th>
                    <th class="p-3 text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody id="stakeholderTableBody" class="divide-y">
                <tr>
                    <td colspan="7" class="p-4 text-center text-slate-400 italic">Lade Stakeholder...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Hier kommt später die visuelle Matrix rein -->
    <h3 class="text-xl font-bold text-blue-900 mb-4 border-t pt-6">Stakeholder Matrix / Map</h3>
    <div class="relative w-full max-w-3xl aspect-video bg-slate-50 border-2 border-slate-200  overflow-hidden self-center">
        <!-- Achsen-Beschriftungen -->
        <div class="absolute left-2 top-1/2 -translate-y-1/2 -rotate-90 text-xs font-bold text-slate-400 tracking-widest">Einfluss (Influence)</div>
        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-xs font-bold text-slate-400 tracking-widest">Interesse (Interest)</div>
        
        <!-- Die 4 Quadranten (farblich passend zu deiner Vorlage) -->
        <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 p-8 gap-2">
            <div class="bg-amber-100 -lg opacity-50"></div> <!-- Handle with care -->
            <div class="bg-blue-100 -lg opacity-50"></div>  <!-- Top priority -->
            <div class="bg-slate-200 -lg opacity-50"></div> <!-- Low priority -->
            <div class="bg-emerald-100 -lg opacity-50"></div><!-- Keep informed -->
        </div>

        <!-- Hier wirft unser JavaScript später die Namen (Punkte) rein -->
        <div id="stakeholderMapPoints" class="absolute inset-0 p-8"></div>
    </div>

</div>

<!-- Stakeholder Modal -->
<div id="modalStakeholder" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formStakeholder" class="w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white p-8  shadow-2xl">
        <h2 class="text-xl font-bold text-blue-900 mb-4 border-b pb-2">Stakeholder bearbeiten</h2>
        
        <input type="hidden" id="stk_id">
        
        <div class="grid grid-cols-2 gap-4">
            <label class="block text-sm font-semibold col-span-2">Name <input id="stk_name" required class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">Rolle im Projekt <input id="stk_role" class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">Position im Unternehmen <input id="stk_position" class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">E-Mail <input id="stk_email" type="email" class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">Telefon <input id="stk_phone" class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">Wissensgebiet <input id="stk_expertise" class="mt-1 w-full  border p-2"></label>
            <label class="block text-sm font-semibold">Verfügbarkeit <input id="stk_availability" class="mt-1 w-full  border p-2"></label>
            
            <label class="block text-sm font-semibold">Einfluss (Influence)
                <select id="stk_influence" class="mt-1 w-full  border p-2">
                    <option value="Low">Gering (Low)</option>
                    <option value="High">Hoch (High)</option>
                </select>
            </label>
            <label class="block text-sm font-semibold">Interesse (Interest)
                <select id="stk_interest" class="mt-1 w-full  border p-2">
                    <option value="Low">Gering (Low)</option>
                    <option value="High">Hoch (High)</option>
                </select>
            </label>
        </div>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('modalStakeholder').classList.add('hidden')" class="border px-4 py-2 ">Abbrechen</button>
            <button type="submit" class="bg-blue-900 text-white px-4 py-2  shadow">Speichern</button>
        </div>
    </form>
</div>