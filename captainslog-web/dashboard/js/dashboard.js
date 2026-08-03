// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

window.dashboardData = null;

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
            window.dashboardData = data.kpis;
            document.getElementById('kpiTotalReqs').textContent = window.dashboardData.total.count;
            document.getElementById('kpiWaitingReqs').textContent = window.dashboardData.waiting.count;
            document.getElementById('kpiApprovedReqs').textContent = window.dashboardData.approved.count;
            document.getElementById('kpiRiskReqs').textContent = window.dashboardData.risks.count;
            document.getElementById('kpiSecReqs').textContent = window.dashboardData.sec.count;
            document.getElementById('dashboardListContainer').innerHTML = '<p class="text-slate-500 text-sm italic">Klicke oben auf eine Karte.</p>';
        }
    } catch (err) {
        console.error("Fehler beim Laden des Dashboards:", err);
    }
}

window.renderDashboardList = function (type, items) {
    const container = document.getElementById('dashboardListContainer');
    if (!container) return;

    // Sicherheits-Prüfung: Verhindert Abstürze, falls "items" leer oder kaputt ist!
    if (!items || !Array.isArray(items) || items.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500 italic p-4">Keine Einträge in dieser Kategorie vorhanden.</p>';
        return;
    }

    // Für die Baumstruktur müssen wir die Parents sicher parsen
    items.forEach(item => {
        try {
            item.parsedParents = JSON.parse(item.parents || '[]');
        } catch (e) {
            item.parsedParents = [];
        }
        if (!Array.isArray(item.parsedParents)) item.parsedParents = [];
    });

    let html = '';

    // ==========================================
    // ANSICHT 1: DER KOMPAKTE BAUM (Für "Alle Anforderungen")
    // ==========================================
    if (type === 'total') {
        html = '<div class="border  bg-white overflow-hidden shadow-sm">';
        const rendered = new Set();

        function renderNode(req, level) {
            if (rendered.has(req.req_key)) return '';
            rendered.add(req.req_key);

            const indentRem = level * 1.25;
            const bgClass = level % 2 === 0 ? 'bg-white' : 'bg-slate-50/50';
            const children = items.filter(r => r.parsedParents.includes(req.req_key));
            const icon = children.length > 0 ? `<span class="text-slate-400 text-[10px] w-3 inline-block">▼</span>` : `<span class="w-3 inline-block"></span>`;

            let nodeHtml = `
                <div onclick="window.openReqFromDashboard(${req.id})" class="flex items-center justify-between p-1.5 border-b border-slate-100 hover:bg-blue-100 cursor-pointer text-xs transition ${bgClass}" style="padding-left: calc(0.5rem + ${indentRem}rem)">
                    <div class="flex items-center gap-2 truncate">
                        ${icon}
                        <span class="font-bold font-mono text-blue-900">${req.req_key}</span>
                        <span class="text-slate-700 truncate">${req.title}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[9px] font-bold text-slate-400">${req.type}</span>
                        <span class="text-[10px] bg-slate-100 border border-slate-200 px-1.5 py-0.5  text-slate-600 whitespace-nowrap">${req.review_status || 'Neu'}</span>
                    </div>
                </div>
            `;

            children.forEach(child => {
                nodeHtml += renderNode(child, level + 1);
            });

            return nodeHtml;
        }

        // Finde Wurzel-Elemente (Elemente, die keine Parents haben)
        const roots = items.filter(req =>
            req.parsedParents.length === 0 ||
            !req.parsedParents.some(pk => items.find(r => r.req_key === pk))
        );

        roots.forEach(root => { html += renderNode(root, 0); });
        items.forEach(req => { if (!rendered.has(req.req_key)) html += renderNode(req, 0); });

        html += '</div>';
    }
    // ==========================================
    // ANSICHT 2: KOMPAKTE LISTE (Risiken etc.)
    // ==========================================
    else {
        html = '<div class="space-y-1.5">';
        items.forEach(item => {
            let mitigationBadge = '';
            let childrenHtml = '';

            if (type === 'risks') {
                if (item.mitigation_status) {
                    mitigationBadge = `<span class="text-[9px] border px-1.5 py-0.5 -full ml-2 font-bold uppercase tracking-wider ${item.mitigation_color}">${item.mitigation_status}</span>`;
                }
                if (item.child_details && Array.isArray(item.child_details) && item.child_details.length > 0) {
                    childrenHtml = `<div class="mt-1.5 pl-2 border-l-2 border-slate-200 flex flex-col gap-1 w-full">`;
                    item.child_details.forEach(child => {
                        const isDone = child.status === 'Geprüft & Freigegeben';
                        const statusColor = isDone ? 'text-emerald-600' : 'text-amber-600';
                        const icon = isDone ? '✅' : '⏳';
                        childrenHtml += `
                            <div class="text-[10px] text-slate-600 bg-white border border-slate-100  px-1.5 py-0.5 flex items-center">
                                <span class="mr-1">${icon}</span> 
                                <span class="font-mono font-bold text-blue-900 mx-1">${child.key}</span> 
                                <span class="truncate max-w-[50%] mr-1">${child.title}</span> 
                                <span class="${statusColor} italic ml-auto border-l pl-1">${child.status}</span>
                            </div>`;
                    });
                    childrenHtml += `</div>`;
                }
            }

            html += `
                <div onclick="window.openReqFromDashboard(${item.id})" class="p-2 border  bg-slate-50 hover:bg-blue-50/80 cursor-pointer flex flex-col transition shadow-sm">
                    <div class="flex justify-between items-start w-full">
                        <div class="flex items-center flex-wrap gap-y-1 text-xs">
                            <span class="font-bold font-mono text-blue-900 mr-2">${item.req_key}</span>
                            <span class="text-slate-700 font-bold">${item.title}</span>
                            ${mitigationBadge}
                        </div>
                        <span class="text-[10px] bg-white border border-slate-200 px-1.5 py-0.5  text-slate-500 whitespace-nowrap ml-2">${item.review_status || 'Neu'}</span>
                    </div>
                    ${childrenHtml}
                </div>
            `;
        });
        html += '</div>';
    }

    container.innerHTML = html;
};

window.openReqFromDashboard = function (id) {
    const tab = document.querySelector('[data-panel="requirements"]');
    if (tab) tab.click();
    if (window.showRequirementDetailById) {
        window.showRequirementDetailById(id);
    }
};