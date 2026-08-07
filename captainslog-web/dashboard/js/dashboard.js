// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

window.dashboardData = null;
let currentStakeholders = [];

export async function loadDashboard() {
    if (!currentProjectId) {
        document.getElementById('kpiProjectProgress').textContent = '0%';
        document.getElementById('kpiTotalReqs').textContent = '0';
        document.getElementById('kpiApprovedReqs').textContent = '0';
        document.getElementById('kpiSbomWarnings').textContent = '0';
        document.getElementById('dashboardListContainer').innerHTML = '<p class="text-slate-500 text-sm">Bitte Projekt wählen.</p>';
        return;
    }

    try {
        const res = await fetch(`../api/get_dashboard_kpis.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        if (data.success) {
            window.dashboardData = data.kpis;
            currentStakeholders = data.stakeholders || [];
            
            // KPIs setzen
            document.getElementById('kpiProjectProgress').textContent = data.project_progress + '%';
            document.getElementById('kpiTotalReqs').textContent = data.kpis.total.count;
            document.getElementById('kpiApprovedReqs').textContent = data.kpis.approved.count;
            document.getElementById('kpiSbomWarnings').textContent = data.sbom_warnings;

            // Matrizen zeichnen
            drawMiniRiskMap(data.kpis.risks.items);
            renderMiniStakeholderList(currentStakeholders);

            // Standardmäßig die Liste "Wartet auf Überprüfung" laden (wenn vorhanden)
            if(data.kpis.waiting.count > 0) {
                window.renderDashboardList('waiting', data.kpis.waiting.items);
            } else {
                window.renderDashboardList('total', data.kpis.total.items);
            }
        }
    } catch (err) {
        console.error("Fehler beim Laden des Dashboards:", err);
    }
}

// Zeichnet die Risiko-Matrix
function drawMiniRiskMap(risks) {
    const map = document.getElementById('dashRiskMap');
    if (!map) return;
    map.innerHTML = '';
    
    // Nur aktive Risiken
    const activeRisks = risks.filter(r => r.review_status !== 'Archiviert');
    
    activeRisks.forEach((risk) => {
        let attrs = {};
        try { attrs = JSON.parse(risk.attributes || '{}'); } catch(e) {}
        const w = parseInt(attrs.w) || 1;
        const e = parseInt(attrs.e) || 1;
        
        const xPos = ((w - 1) * 20) + 10;
        const yPos = 100 - (((e - 1) * 20) + 10);
        
        // Etwas zufälliger Offset bei  Überlappung
        const offset = (Math.random() - 0.5) * 10; 

        map.innerHTML += `
            <div onclick="window.openReqFromDashboard(${risk.id})" class="absolute transform -translate-x-1/2 -translate-y-1/2 w-4 h-4 bg-blue-900 text-white rounded-full flex items-center justify-center text-[8px] font-bold shadow border border-white cursor-pointer hover:scale-150 transition-transform z-10" 
                 style="left: calc(${xPos}% + ${offset}px); top: calc(${yPos}% + ${offset}px);"
                 title="${risk.req_key}: ${risk.title}">
                R
            </div>
        `;
    });
}

// Hilfsfunktion: Erkennt die Wichtigkeit anhand der Rolle/Position
function getStakeholderRank(s) {
    const text = ((s.role || '') + ' ' + (s.position || '')).toLowerCase();
    
    // Rang 1: Projektleitung
    if (text.includes('projektleiter') || text.includes('pl') || text.includes('manager')) return 1;
    // Rang 2: Kunde / Auftraggeber
    if (text.includes('kunde') || text.includes('auftraggeber') || text.includes('client')) return 2;
    // Rang 3: Interne Mitarbeiter / Team
    if (text.includes('mitarbeiter') || text.includes('entwickler') || text.includes('intern') || text.includes('team')) return 3;
    
    // Rang 4: Alle anderen
    return 4; 
}

// Zeichnet die sortierte Stakeholder-Liste
function renderMiniStakeholderList(stakeholders) {
    const list = document.getElementById('dashStakeholderList');
    if (!list) return;

    if (!stakeholders || stakeholders.length === 0) {
        list.innerHTML = '<div class="text-xs text-slate-500 italic p-2">Keine Stakeholder vorhanden.</div>';
        return;
    }

    // Sortieren: Erst nach Rang (1-4), dann alphabetisch nach Name
    const sorted = [...stakeholders].sort((a, b) => {
        const rankA = getStakeholderRank(a);
        const rankB = getStakeholderRank(b);
        if (rankA === rankB) return a.name.localeCompare(b.name);
        return rankA - rankB;
    });

    let html = '';
    sorted.forEach(s => {
        // Initialen generieren (z.B. "Max Mustermann" -> "MM")
        const initials = s.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const roleText = s.role || s.position || 'Stakeholder';

        html += `
            <div onclick="window.jumpToStakeholder(${s.id})" class="flex items-center gap-3 p-2 hover:bg-blue-50 rounded-md cursor-pointer transition border border-transparent hover:border-blue-100">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-900 flex items-center justify-center text-xs font-extrabold shrink-0 shadow-sm border border-blue-200">
                    ${initials}
                </div>
                <div class="flex flex-col truncate">
                    <span class="text-sm font-bold text-slate-800 truncate">${s.name}</span>
                    <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider truncate">${roleText}</span>
                </div>
            </div>
        `;
    });
    list.innerHTML = html;
}

// Hilfsfunktion: Springt zum Stakeholder-Tab und öffnet das Modal
window.jumpToStakeholder = function(id) {
    const tab = document.querySelector('[data-panel="stakeholders"]');
    if (tab) tab.click();
    
    // Kleines Timeout, damit der Tab in Ruhe laden kann
    setTimeout(() => {
        if(window.editStakeholder) window.editStakeholder(id);
    }, 100);
}

// Die bestehende List-Rendering Funktion für die Detailansicht
window.renderDashboardList = function (type, items) {
    const container = document.getElementById('dashboardListContainer');
    if (!container) return;

    if (!items || !Array.isArray(items) || items.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500 italic p-4 border bg-slate-50 rounded">Keine Einträge in dieser Kategorie.</p>';
        return;
    }

    items.forEach(item => {
        try { item.parsedParents = JSON.parse(item.parents || '[]'); } catch (e) { item.parsedParents = []; }
        if (!Array.isArray(item.parsedParents)) item.parsedParents = [];
    });

    let html = '';

    // KOMPAKTE LISTE (Risiken, Wartend etc.)
    if (type !== 'total') {
        html = '<div class="space-y-2">';
        items.forEach(item => {
            const isDone = item.review_status === 'Geprüft & Freigegeben';
            const statusColor = isDone ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600 bg-white';
            
            html += `
                <div onclick="window.openReqFromDashboard(${item.id})" class="p-3 border border-slate-200 rounded-md ${statusColor} hover:shadow-md cursor-pointer flex flex-col transition">
                    <div class="flex justify-between items-start w-full">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-bold font-mono text-blue-900 bg-white border border-slate-200 px-1.5 py-0.5 rounded shadow-sm">${item.req_key}</span>
                            <span class="text-slate-800 font-bold">${item.title}</span>
                        </div>
                        <span class="text-[10px] bg-white border border-slate-200 px-2 py-1 rounded font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap ml-2">${item.review_status || 'Neu'}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    } 
    // BAUMSTRUKTUR (Total)
    else {
        html = '<div class="border rounded-md bg-white overflow-hidden">';
        const rendered = new Set();

        function renderNode(req, level) {
            if (rendered.has(req.req_key)) return '';
            rendered.add(req.req_key);
            
            const indentRem = level * 1.5;
            const bgClass = level % 2 === 0 ? 'bg-white' : 'bg-slate-50/50';
            const children = items.filter(r => r.parsedParents.includes(req.req_key));
            const icon = children.length > 0 ? `<span class="text-slate-400 text-xs w-4 inline-block">▶</span>` : `<span class="w-4 inline-block"></span>`;
            
            let nodeHtml = `
                <div onclick="window.openReqFromDashboard(${req.id})" class="flex items-center justify-between p-2 border-b border-slate-100 hover:bg-blue-50 cursor-pointer text-sm transition ${bgClass}" style="padding-left: calc(1rem + ${indentRem}rem)">
                    <div class="flex items-center gap-2 truncate">
                        ${icon}
                        <span class="font-bold font-mono text-blue-900">${req.req_key}</span>
                        <span class="text-slate-700 truncate">${req.title}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-slate-600 whitespace-nowrap">${req.review_status || 'Neu'}</span>
                    </div>
                </div>
            `;
            children.forEach(child => { nodeHtml += renderNode(child, level + 1); });
            return nodeHtml;
        }

        const roots = items.filter(req => req.parsedParents.length === 0 || !req.parsedParents.some(pk => items.find(r => r.req_key === pk)));
        roots.forEach(root => { html += renderNode(root, 0); });
        items.forEach(req => { if (!rendered.has(req.req_key)) html += renderNode(req, 0); });
        
        html += '</div>';
    }
    container.innerHTML = html;
}

window.openReqFromDashboard = function (id) {
    const tab = document.querySelector('[data-panel="requirements"]');
    if (tab) tab.click();
    setTimeout(() => {
        if (window.showRequirementDetailById) window.showRequirementDetailById(id);
    }, 50);
};