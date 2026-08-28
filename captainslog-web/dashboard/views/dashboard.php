<!-- dashboard/views/dashboard.php -->
<div class="flex h-full flex-col overflow-hidden border border-slate-300 bg-white shadow-sm">

    <!-- Standard Kopfbereich (wie in den anderen Reitern) -->
    <div class="flex shrink-0 items-center justify-between border-b border-slate-300 bg-white px-5 py-4">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                System Dashboard
            </p>
            <h2 id="dashProjectTitle" class="text-2xl font-extrabold text-blue-950">
                Projektübersicht
            </h2>
        </div>
    </div>

    <!-- Scrollbarer Inhalt -->
    <div class="flex-1 overflow-y-auto bg-slate-50/50 p-5 space-y-6">

        <!-- Top KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white p-5 rounded border border-slate-300 shadow-sm flex items-center justify-between border-l-4 border-l-blue-900">
                <div>
                    <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">Fortschritt (Tasks)</div>
                    <div id="kpiProjectProgress" class="text-3xl font-extrabold text-blue-950">0%</div>
                </div>
                <svg class="w-10 h-10 text-slate-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>

            <div class="bg-white p-5 rounded border border-slate-300 shadow-sm flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">SBOM Lizenzen</div>
                    <div class="flex items-baseline gap-2">
                        <div id="kpiSbomWarnings" class="text-3xl font-extrabold text-amber-600">0</div>
                        <div class="text-xs font-bold text-slate-400 uppercase">Warnungen</div>
                    </div>
                </div>
                <svg class="w-10 h-10 text-slate-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>

            <div class="bg-white p-5 rounded border border-slate-300 shadow-sm flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <div class="text-slate-500 text-xs uppercase font-bold tracking-wider mb-1">Requirements</div>
                    <div class="flex items-baseline gap-2">
                        <div id="kpiApprovedReqs" class="text-3xl font-extrabold text-emerald-600">0</div>
                        <div class="text-xs font-bold text-slate-400 uppercase">/ <span id="kpiTotalReqs">0</span> Freigegeben</div>
                    </div>
                </div>
                <svg class="w-10 h-10 text-slate-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
        </div>

        <!-- Health Check Panel (Helles Log) -->
        <div class="bg-white border border-slate-300 rounded shadow-sm flex flex-col font-mono text-sm overflow-hidden">
            <div class="bg-slate-100 border-b border-slate-300 px-5 py-3 flex items-center justify-between">
                <span class="text-slate-600 font-bold tracking-widest uppercase text-xs">System_Health_Log</span>
                <span id="healthCheckBadge" class="text-slate-500 text-xs">Warte...</span>
            </div>
            <div id="healthCheckContent" class="h-48 overflow-y-auto bg-slate-50 custom-scrollbar">
                <!-- Log-Zeilen per JS -->
            </div>
        </div>

        <!-- Kompakte Listen: Risiken & Stakeholder -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Risiken -->
            <div class="bg-white rounded border border-slate-300 shadow-sm p-5 flex flex-col h-72">
                <div class="flex justify-between items-center mb-3 border-b border-slate-100 pb-2 shrink-0">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Offene Risiken</h3>
                    <button onclick="window.renderDashboardList('risks', window.dashboardData.risks.items)" class="text-xs font-bold text-blue-600 hover:underline">Alle zeigen</button>
                </div>
                <div id="dashRiskList" class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar"></div>
            </div>

            <!-- Stakeholder -->
            <div class="bg-white rounded border border-slate-300 shadow-sm p-5 flex flex-col h-72">
                <div class="flex justify-between items-center mb-3 border-b border-slate-100 pb-2 shrink-0">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Stakeholder</h3>
                </div>
                <div id="dashStakeholderList" class="flex-1 overflow-y-auto space-y-1 pr-1 custom-scrollbar"></div>
            </div>
        </div>

    </div>
</div>