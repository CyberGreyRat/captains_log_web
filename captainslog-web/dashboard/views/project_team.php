<!-- dashboard/views/project_team.php -->
<div class="cl-panel">
    <div class="flex shrink-0 items-center justify-between border-b border-slate-300 px-5 py-4">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Interne Projektorganisation
            </p>
            <h2 class="text-2xl font-extrabold text-blue-950">Projektteam</h2>
        </div>
        <button id="btnNewProjectMember" type="button"
            class="whitespace-nowrap bg-blue-950 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">+
            Mitglied zuweisen</button>
    </div>

    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-300 bg-slate-50 px-5 py-4 md:grid-cols-4">
        <div class="border border-slate-300 border-l-4 border-l-blue-950 bg-white p-3">
            <div class="text-[10px] font-bold uppercase text-slate-500">Teammitglieder</div>
            <div id="teamMemberCount" class="mt-1 text-2xl font-extrabold text-blue-950">0</div>
        </div>
        <div class="border border-slate-300 border-l-4 border-l-emerald-500 bg-white p-3">
            <div class="text-[10px] font-bold uppercase text-slate-500">Aktiv</div>
            <div id="teamActiveCount" class="mt-1 text-2xl font-extrabold text-emerald-700">0</div>
        </div>
        <div class="border border-slate-300 border-l-4 border-l-amber-500 bg-white p-3 md:col-span-2">
            <div class="text-[10px] font-bold uppercase text-slate-500">Hinweis</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Systemrolle steuert Rechte. Projektrolle beschreibt
                die fachliche Aufgabe.</div>
        </div>
    </div>

    <div class="flex shrink-0 gap-2 border-b border-slate-300 p-3">
        <input id="projectTeamSearch" type="search" placeholder="Name, Projektrolle oder Fachgebiet suchen"
            class="h-10 flex-1 border border-slate-300 px-3 text-sm outline-none focus:border-blue-950">
        <select id="projectTeamRoleFilter"
            class="h-10 min-w-[220px] border border-slate-300 bg-white px-3 text-sm font-semibold outline-none focus:border-blue-950">
            <option value="">Alle Projektrollen</option>
        </select>
    </div>

    <div class="min-h-0 flex-1 overflow-auto">
        <table class="w-full min-w-[1050px] border-collapse text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-slate-300 bg-[#eef2f6] text-blue-950">
                <tr>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Name</th>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Projektrolle</th>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Fachgebiet</th>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Verfügbarkeit</th>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Systemrolle</th>
                    <th class="px-3 py-3 text-xs font-extrabold uppercase">Status</th>
                    <th class="px-3 py-3 text-right text-xs font-extrabold uppercase">Aktionen</th>
                </tr>
            </thead>
            <tbody id="projectTeamTableBody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="7" class="p-8 text-center italic text-slate-400">Bitte zuerst ein Projekt auswählen.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        class="flex h-9 shrink-0 items-center justify-between border-t border-slate-300 bg-[#eef2f6] px-4 text-[10px] font-semibold uppercase text-slate-500">
        <span id="projectTeamResultCount">0 Mitglieder</span>
        <span>Interne Nutzer und fachliche Projektrollen</span>
    </div>
</div>

<div id="projectMemberModal"
    class="fixed inset-0 z-[220] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <form id="projectMemberForm"
        class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden border border-slate-400 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Projektzuweisung</p>
                <h2 id="projectMemberModalTitle" class="text-xl font-extrabold text-blue-950">Projektmitglied zuweisen
                </h2>
            </div>
            <button id="projectMemberModalClose" type="button"
                class="border border-slate-300 bg-white p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <div class="flex-1 space-y-5 overflow-y-auto p-6">
            <input id="project_member_original_user_id" type="hidden">

            <label class="block text-sm font-bold text-slate-700">Nutzer
                <select id="project_member_user_id" required
                    class="mt-1 w-full border border-slate-300 bg-white p-2.5 font-normal outline-none focus:border-blue-950">
                    <option value="">-- Nutzer auswählen --</option>
                </select>
            </label>

            <label class="block text-sm font-bold text-slate-700">Projektrolle
                <input id="project_member_role" list="projectRoleOptions" required autocomplete="off"
                    placeholder="Rolle auswählen oder neue Rolle eingeben"
                    class="mt-1 w-full border border-slate-400 p-2.5 font-semibold outline-none focus:border-blue-950">
                <datalist id="projectRoleOptions"></datalist>
                <span class="mt-1 block text-xs font-normal text-slate-500">Neue Eingaben werden automatisch in den
                    Rollen-Katalog übernommen.</span>
            </label>

            <label class="block text-sm font-bold text-slate-700">Fachgebiet / Kenntnisse
                <input id="project_member_expertise" placeholder="z.B. Embedded C++, AURIX, Hardwaretest"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal outline-none focus:border-blue-950">
            </label>

            <label class="block text-sm font-bold text-slate-700">Verfügbarkeit
                <input id="project_member_availability" placeholder="z.B. 60 %, montags bis donnerstags, nach Bedarf"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal outline-none focus:border-blue-950">
            </label>

            <label
                class="flex cursor-pointer items-center gap-3 border border-slate-300 bg-slate-50 p-3 text-sm font-bold text-slate-700">
                <input id="project_member_active" type="checkbox" checked class="h-4 w-4">
                Aktives Projektmitglied
            </label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-300 bg-slate-50 px-6 py-4">
            <button id="projectMemberCancel" type="button"
                class="border border-slate-300 bg-white px-5 py-2 text-xs font-bold uppercase hover:bg-slate-100">Abbrechen</button>
            <button type="submit"
                class="bg-blue-950 px-6 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">Mitglied
                speichern</button>
        </div>
    </form>
</div>