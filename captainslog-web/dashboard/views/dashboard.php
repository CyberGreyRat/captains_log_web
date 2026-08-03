<!-- dashboard/views/dashboard.php -->
<div class="flex flex-col h-full overflow-y-auto p-2">
    <h2 class="text-2xl font-bold text-blue-900 mb-6">Projekt-Dashboard & KPIs</h2>
    
    <!-- Obere Reihe (Allgemein) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div onclick="window.renderDashboardList('total')" class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-blue-900 cursor-pointer hover:bg-slate-50 transition">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Gesamt Anforderungen</div>
            <div id="kpiTotalReqs" class="text-3xl font-extrabold text-blue-950 mt-2">0</div>
        </div>
        <div onclick="window.renderDashboardList('waiting')" class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-amber-500 cursor-pointer hover:bg-slate-50 transition">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Wartet auf Überprüfung</div>
            <div id="kpiWaitingReqs" class="text-3xl font-extrabold text-amber-600 mt-2">0</div>
        </div>
        <div onclick="window.renderDashboardList('approved')" class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-emerald-500 cursor-pointer hover:bg-slate-50 transition">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Geprüft & Freigegeben</div>
            <div id="kpiApprovedReqs" class="text-3xl font-extrabold text-emerald-600 mt-2">0</div>
        </div>
    </div>

    <!-- Untere Reihe (Risiko & Security) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div onclick="window.renderDashboardList('risks')" class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-red-600 cursor-pointer hover:bg-slate-50 transition">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Erfasste Risiken</div>
            <div id="kpiRiskReqs" class="text-3xl font-extrabold text-red-700 mt-2">0</div>
        </div>
        <div onclick="window.renderDashboardList('sec')" class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-indigo-600 cursor-pointer hover:bg-slate-50 transition">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Cyber Security & Normen</div>
            <div id="kpiSecReqs" class="text-3xl font-extrabold text-indigo-700 mt-2">0</div>
        </div>
        <div class="bg-white p-5 rounded-xl border shadow-sm border-l-4 border-l-purple-600">
            <div class="text-slate-500 text-xs uppercase font-bold tracking-wider">Phasen-Fortschritt</div>
            <div class="text-3xl font-extrabold text-purple-700 mt-2">Aktiv</div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl border shadow-sm flex-1">
        <h3 id="dashboardListTitle" class="font-bold text-slate-800 text-lg mb-4">Detail-Ansicht</h3>
        <div id="dashboardListContainer" class="space-y-2">
            <p class="text-slate-500 text-sm">Klicke oben auf eine Kennzahl, um hier die zugehörigen Einträge zu sehen.</p>
        </div>
    </div>
</div>