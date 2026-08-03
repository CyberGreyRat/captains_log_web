// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

let dashboardData = null;

export async function loadDashboard() {
    if (!currentProjectId) {
        ['kpiTotalReqs', 'kpiWaitingReqs', 'kpiApprovedReqs', 'kpiRiskReqs', 'kpiSecReqs'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '0';
        });
        const elCont = document.getElementById('dashboardListContainer');
        if (elCont) elCont.innerHTML = '<p class="text-slate-500 text-sm">Bitte Projekt wählen.</p>';
        return;
    }

    try {
        const res = await fetch(`../api/get_dashboard_kpis.php?project_id=${currentProjectId}`);
        const data = await res.json();

        if (data.success) {
            dashboardData = data.kpis;
            document.getElementById('kpiTotalReqs').textContent = dashboardData.total.count;
            document.getElementById('kpiWaitingReqs').textContent = dashboardData.waiting.count;
            document.getElementById('kpiApprovedReqs').textContent = dashboardData.approved.count;
            document.getElementById('kpiRiskReqs').textContent = dashboardData.risks.count;
            document.getElementById('kpiSecReqs').textContent = dashboardData.sec.count;
            document.getElementById('dashboardListContainer').innerHTML = '<p class="text-slate-500 text-sm italic">Klicke oben auf eine Karte.</p>';
        }
    } catch (err) {
        console.error("Fehler beim Laden des Dashboards:", err);
    }
}

window.renderDashboardList = function (type) {
    if (!dashboardData) return;

    let title = '';
    let items = [];

    if (type === 'total') { title = 'Alle Anforderungen & Ziele'; items = dashboardData.total.items; }
    else if (type === 'waiting') { title = 'Wartet auf Überprüfung'; items = dashboardData.waiting.items; }
    else if (type === 'approved') { title = 'Geprüft & Freigegeben'; items = dashboardData.approved.items; }
    else if (type === 'risks') { title = 'Erfasste Risiken (RISK)'; items = dashboardData.risks.items; }
    else if (type === 'sec') { title = 'Security Anforderungen (SEC)'; items = dashboardData.sec.items; }

    const elTitle = document.getElementById('dashboardListTitle');
    if (elTitle) elTitle.textContent = title;

    const container = document.getElementById('dashboardListContainer');
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = '<p class="text-slate-500 text-sm italic">Keine Einträge in dieser Kategorie.</p>';
        return;
    }

    let html = '';
    items.forEach(item => {
        let mitigationBadge = '';
        let childrenHtml = ''; // NEU: HTML für die Children

        if (type === 'risks') {
            if (item.mitigation_status) {
                mitigationBadge = `<span class="text-[10px] border px-2 py-0.5 rounded-full ml-3 font-bold uppercase tracking-wider ${item.mitigation_color}">${item.mitigation_status}</span>`;
            }

            // NEU: Children ausgeben
            if (item.child_details && item.child_details.length > 0) {
                childrenHtml = `<div class="mt-3 pl-3 border-l-2 border-slate-200 flex flex-col gap-1.5 w-full">`;
                item.child_details.forEach(child => {
                    const isDone = child.status === 'Geprüft & Freigegeben';
                    const statusColor = isDone ? 'text-emerald-600' : 'text-amber-600';
                    const icon = isDone ? '✅' : '⏳';
                    childrenHtml += `
                        <div class="text-[11px] text-slate-600 bg-white border border-slate-100 rounded px-2 py-1 shadow-sm flex items-center">
                            <span class="mr-1">${icon}</span> 
                            <span class="font-mono font-bold text-blue-900 mx-1">${child.key}</span> 
                            <span class="truncate max-w-[50%] mr-1">${child.title}</span> 
                            <span class="${statusColor} italic ml-auto border-l pl-2">${child.status}</span>
                        </div>`;
                });
                childrenHtml += `</div>`;
            } else {
                childrenHtml = `<div class="mt-2 text-[11px] text-red-600 italic border-l-2 border-red-200 pl-2">Keine Gegenmaßnahmen (Children) verknüpft! Bitte SEC-Anforderung anlegen.</div>`;
            }
        }

        html += `
            <div onclick="window.openReqFromDashboard(${item.id})" class="p-4 mb-3 border rounded bg-slate-50 hover:bg-blue-50/50 cursor-pointer flex flex-col transition shadow-sm">
                <div class="flex justify-between items-start w-full">
                    <div class="flex items-center flex-wrap gap-y-2">
                        <span class="font-bold font-mono text-blue-900 mr-2">${item.req_key}</span>
                        <span class="text-slate-700 font-bold">${item.title}</span>
                        ${mitigationBadge}
                    </div>
                    <span class="text-xs bg-white border px-2 py-1 rounded text-slate-500 shadow-sm whitespace-nowrap ml-2">${item.review_status || 'Neu'}</span>
                </div>
                ${childrenHtml}
            </div>
        `;
    });
    container.innerHTML = html;
};

window.openReqFromDashboard = function (id) {
    const tab = document.querySelector('[data-panel="requirements"]');
    if (tab) tab.click();
    if (window.showRequirementDetailById) {
        window.showRequirementDetailById(id);
    }
};