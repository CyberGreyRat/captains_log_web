<!-- dashboard/views/dashboard.php -->
<div class="flex flex-col h-full overflow-y-auto p-4 space-y-6">

    <!-- Top KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Projektfortschritt -->
        <div
            class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between border-l-4 border-l-blue-900">
            <div>
                <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">Projektfortschritt (Tasks)
                </div>
                <div id="kpiProjectProgress" class="text-4xl font-extrabold text-blue-950">0%</div>
            </div>
            <svg class="w-12 h-12 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <!-- SBOM Warnungen -->
        <div
            class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between border-l-4 border-l-amber-500">
            <div>
                <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">SBOM Warnungen</div>
                <div class="flex items-baseline gap-2">
                    <div id="kpiSbomWarnings" class="text-4xl font-extrabold text-amber-600">0</div>
                    <div class="text-xs font-bold text-slate-400">ohne Lizenz</div>
                </div>
            </div>
            <svg class="w-12 h-12 text-amber-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
        </div>

        <!-- System Anforderungen -->
        <div
            class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between border-l-4 border-l-emerald-500">
            <div>
                <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">Geprüft & Freigegeben</div>
                <div class="flex items-baseline gap-2">
                    <div id="kpiApprovedReqs" class="text-4xl font-extrabold text-emerald-600">0</div>
                    <div class="text-xs font-bold text-slate-400">/ <span id="kpiTotalReqs">0</span> Elemente</div>
                </div>
            </div>
            <svg class="w-12 h-12 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                </path>
            </svg>
        </div>
    </div>

<!-- Matrizen & Filter -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- RISIKO MATRIX (Mini) -->
        <!-- Feste Höhe: h-[420px] -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 flex flex-col h-[420px]">
            <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider shrink-0">Risiko Matrix</h3>
            <div class="relative w-full max-w-[250px] aspect-square mx-auto border-l-2 border-b-2 border-slate-800 bg-slate-50 shrink-0">
                <div class="absolute inset-0 grid grid-cols-5 grid-rows-5 opacity-80">
                    <div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div><div class="bg-red-600"></div><div class="bg-red-700"></div>
                    <div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div><div class="bg-red-600"></div>
                    <div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div><div class="bg-red-500"></div>
                    <div class="bg-emerald-500"></div><div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div><div class="bg-orange-500"></div>
                    <div class="bg-emerald-500"></div><div class="bg-emerald-500"></div><div class="bg-emerald-400"></div><div class="bg-emerald-300"></div><div class="bg-amber-400"></div>
                </div>
                <div id="dashRiskMap" class="absolute inset-0"></div>
            </div>
            <!-- Footer bleibt immer unten -->
            <div class="mt-auto pt-4 text-center border-t border-slate-100 shrink-0">
                <button onclick="window.renderDashboardList('risks', window.dashboardData.risks.items)" class="text-xs font-bold text-blue-600 hover:underline">Alle Risiken in Liste anzeigen</button>
            </div>
        </div>

        <!-- STAKEHOLDER LISTE (Mini) -->
        <!-- Identische feste Höhe: h-[420px] -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 flex flex-col h-[420px]">
            <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider shrink-0">Top Stakeholder</h3>
            
            <!-- Die scrollbare Liste füllt den exakten Zwischenraum -->
            <div id="dashStakeholderList" class="flex-1 overflow-y-auto space-y-1 pr-2 custom-scrollbar">
                <!-- JS füllt das -->
            </div>
            
            <!-- Footer bleibt immer unten -->
            <div class="mt-auto pt-4 text-center text-xs text-slate-500 italic border-t border-slate-100 shrink-0">
                Klicke auf einen Eintrag, um sein Profil zu öffnen.
            </div>
        </div>
    </div>

    <!-- Dynamische Detail-Liste -->
    <div class="border rounded-lg bg-white p-6 shadow-sm">
        <div class="flex gap-2 mb-4 border-b border-slate-100 pb-4">
            <button onclick="window.renderDashboardList('total', window.dashboardData.total.items)"
                class="bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded text-xs font-bold text-slate-700 transition">Alle
                Elemente</button>
            <button onclick="window.renderDashboardList('waiting', window.dashboardData.waiting.items)"
                class="bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded text-xs font-bold text-amber-700 transition">Offene
                Reviews</button>
            <button onclick="window.renderDashboardList('sec', window.dashboardData.sec.items)"
                class="bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded text-xs font-bold text-indigo-700 transition">Security
                (SEC)</button>
        </div>
        <h3 id="dashboardListTitle" class="mb-4 text-lg font-bold text-blue-900">Listenansicht</h3>
        <div id="dashboardListContainer" class="text-sm text-slate-500 italic">
            Wähle oben eine Filter-Option aus.
        </div>
    </div>
</div>