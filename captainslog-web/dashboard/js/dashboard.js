// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

let dashboardData = null;

export async function loadDashboard() {
    if (!currentProjectId) {
        ['kpiTotalReqs', 'kpiWaitingReqs', 'kpiApprovedReqs', 'kpiRiskReqs', 'kpiSecReqs'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.textContent = '0';
        });
        const elCont = document.getElementById('dashboardListContainer');
        if(elCont) elCont.innerHTML = '<p class="text-slate-500 text-sm">Bitte Projekt wählen.</p>';
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

window.renderDashboardList = function(type) {
    if (!dashboardData) return;
    
    let title = '';
    let items = [];

    if (type === 'total') { title = 'Alle Anforderungen & Ziele'; items = dashboardData.total.items; } 
    else if (type === 'waiting') { title = 'Wartet auf Überprüfung'; items = dashboardData.waiting.items; } 
    else if (type === 'approved') { title = 'Geprüft & Freigegeben'; items = dashboardData.approved.items; }
    else if (type === 'risks') { title = 'Erfasste Risiken (RISK)'; items = dashboardData.risks.items; }
    else if (type === 'sec') { title = 'Security Anforderungen (SEC)'; items = dashboardData.sec.items; }

    const elTitle = document.getElementById('dashboardListTitle');
    if(elTitle) elTitle.textContent = title;
    
    const container = document.getElementById('dashboardListContainer');
    if(!container) return;
    
    if(!items || items.length === 0) {
        container.innerHTML = '<p class="text-slate-500 text-sm italic">Keine Einträge in dieser Kategorie.</p>';
        return;
    }

    let html = '';
    items.forEach(item => {
        html += `
            <div onclick="window.openReqFromDashboard(${item.id})" class="p-3 border rounded bg-slate-50 hover:bg-blue-50 cursor-pointer flex justify-between items-center transition">
                <div>
                    <span class="font-bold font-mono text-blue-900 mr-2">${item.req_key}</span>
                    <span class="text-slate-700 font-medium">${item.title}</span>
                </div>
                <span class="text-xs bg-white border px-2 py-1 rounded text-slate-500">${item.review_status || 'Neu'}</span>
            </div>
        `;
    });
    container.innerHTML = html;
};

window.openReqFromDashboard = function(id) {
    const tab = document.querySelector('[data-panel="requirements"]');
    if(tab) tab.click();
    if(window.showRequirementDetailById) {
        window.showRequirementDetailById(id);
    }
};