// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

window.dashboardData = null;

export async function loadDashboard() {
    if (!currentProjectId) {
        document.getElementById('dashProjectTitle').textContent = 'Kein Projekt gewählt';
        return;
    }

    if (window.showLoader) window.showLoader();

    // Versuche den Projektnamen aus dem Dropdown zu lesen, ansonsten Standardtext
    const projectSelect = document.getElementById('projectSelect'); // Oder wie deine ID heißt
    if (projectSelect && projectSelect.options[projectSelect.selectedIndex]) {
        document.getElementById('dashProjectTitle').textContent = projectSelect.options[projectSelect.selectedIndex].text;
    } else {
        document.getElementById('dashProjectTitle').textContent = 'Projektübersicht';
    }

    try {
        const [kpiRes, healthLoaded] = await Promise.all([
            fetch(`../api/get_dashboard_kpis.php?project_id=${currentProjectId}`),
            loadHealthDiagnostics()
        ]);
        
        const data = await kpiRes.json();
        
        if (data.success) {
            window.dashboardData = data.kpis;
            
            document.getElementById('kpiProjectProgress').textContent = data.project_progress + '%';
            document.getElementById('kpiTotalReqs').textContent = data.kpis.total.count;
            document.getElementById('kpiApprovedReqs').textContent = data.kpis.approved.count;
            document.getElementById('kpiSbomWarnings').textContent = data.sbom_warnings;

            renderMiniRiskList(data.kpis.risks.items);
            renderMiniStakeholderList(data.stakeholders || []);
        }
    } catch (err) {
        console.error("Dashboard Ladefehler:", err);
    } finally {
        if (window.hideLoader) window.hideLoader();
    }
}

// ============================================================================
// HELLES TERMINAL LOGIC (Health Check)
// ============================================================================
async function loadHealthDiagnostics() {
    const container = document.getElementById('healthCheckContent');
    const badge = document.getElementById('healthCheckBadge');
    if (!container || !currentProjectId) return;

    container.innerHTML = '<div class="p-4 text-slate-400 animate-pulse">> Scanne Projekt auf Inkonsistenzen...</div>';

    try {
        const res = await fetch(`../api/get_health_diagnostics.php?project_id=${currentProjectId}`);
        const data = await res.json();

        if (data.success && data.warnings.length > 0) {
            badge.innerHTML = `<span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded border border-rose-200">${data.warnings.length} Fehler</span>`;
            
            container.innerHTML = data.warnings.map(w => {
                const isCrit = w.severity === 'critical';
                const color = isCrit ? 'text-rose-600' : 'text-amber-600';
                const prefix = isCrit ? '[CRIT]' : '[WARN]';
                
                // Klickbar machen und Styling anpassen
                return `
                <div onclick="window.resolveHealthWarning('${w.type}', '${w.id}')" class="flex gap-4 p-3 border-b border-slate-200 hover:bg-blue-50 cursor-pointer transition-colors group">
                    <span class="${color} font-bold shrink-0">${prefix}</span>
                    <span class="text-slate-700 group-hover:text-blue-700 transition-colors">${w.message}</span>
                </div>`;
            }).join('');
        } else {
            badge.innerHTML = '<span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded border border-emerald-200">OK</span>';
            container.innerHTML = `<div class="p-4 text-emerald-600">> Alle Prüfungen bestanden. Keine Anomalien gefunden.</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class="p-4 text-rose-500">> [FATAL] Diagnose-Server nicht erreichbar.</div>`;
    }
}

// Navigiert zum entsprechenden Reiter und öffnet das Element
window.resolveHealthWarning = function(type, id) {
    if (type === 'issue') {
        document.querySelector('[data-panel="issues"]')?.click();
        setTimeout(() => { if (window.editIssue) window.editIssue(id); }, 100);
    } else if (type === 'task') {
        document.querySelector('[data-panel="projectplan"]')?.click();
        setTimeout(() => { if (window.editTask) window.editTask(id); }, 100);
    } else if (type === 'requirement') {
        // Da die ID hier der req_key als String ist, springen wir zumindest in den Reiter
        document.querySelector('[data-panel="requirements"]')?.click();
        // Optional: Wenn du ein Suchfeld hast, könntest du hier den key eintragen
        const searchInput = document.getElementById('reqSearchInput');
        if (searchInput) {
            searchInput.value = id;
            searchInput.dispatchEvent(new Event('input'));
        }
    }
};

// ============================================================================
// KOMPAKTE LISTEN (Bleiben wie zuvor, nur Schriften minimal größer)
// ============================================================================

function renderMiniRiskList(risks) {
    const list = document.getElementById('dashRiskList');
    if (!list) return;
    
    const activeRisks = risks.filter(r => r.review_status !== 'Archiviert');
    if (activeRisks.length === 0) {
        list.innerHTML = '<div class="text-sm text-slate-400 italic">Keine aktiven Risiken.</div>';
        return;
    }

    const sortedRisks = activeRisks.sort((a, b) => {
        let aW=1, aE=1, bW=1, bE=1;
        try { const pa = JSON.parse(a.attributes||'{}'); aW=pa.w||1; aE=pa.e||1; } catch(e){}
        try { const pb = JSON.parse(b.attributes||'{}'); bW=pb.w||1; bE=pb.e||1; } catch(e){}
        return (bW*bE) - (aW*aE);
    });

    list.innerHTML = sortedRisks.map(risk => {
        let score = 1;
        try { const p = JSON.parse(risk.attributes||'{}'); score = (p.w||1)*(p.e||1); } catch(e){}
        
        let color = 'bg-slate-200 text-slate-700';
        if(score >= 15) color = 'bg-rose-600 text-white';
        else if(score >= 8) color = 'bg-amber-500 text-white';
        else if(score >= 4) color = 'bg-emerald-500 text-white';

        return `
            <div onclick="window.openReqFromDashboard(${risk.id})" class="flex items-center gap-3 p-2 border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition">
                <span class="${color} text-xs font-bold w-6 h-6 flex items-center justify-center shrink-0 rounded">${score}</span>
                <div class="flex flex-col truncate">
                    <span class="text-sm text-slate-800 truncate font-semibold">${risk.title}</span>
                    <span class="text-xs font-mono text-slate-500">${risk.req_key}</span>
                </div>
            </div>`;
    }).join('');
}

function getStakeholderRank(s) {
    const t = ((s.role || '') + ' ' + (s.position || '')).toLowerCase();
    if (t.includes('leiter') || t.includes('manager')) return 1;
    if (t.includes('kunde') || t.includes('auftrag')) return 2;
    return 3; 
}

function renderMiniStakeholderList(stakeholders) {
    const list = document.getElementById('dashStakeholderList');
    if (!list) return;
    if (stakeholders.length === 0) {
        list.innerHTML = '<div class="text-sm text-slate-400 italic">Keine Einträge.</div>';
        return;
    }

    const sorted = [...stakeholders].sort((a, b) => {
        const rA = getStakeholderRank(a), rB = getStakeholderRank(b);
        if (rA === rB) return a.name.localeCompare(b.name);
        return rA - rB;
    });

    list.innerHTML = sorted.map(s => `
        <div onclick="window.jumpToStakeholder(${s.id})" class="flex justify-between items-center p-2 border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition">
            <div class="flex flex-col truncate">
                <span class="text-sm font-bold text-slate-800">${s.name}</span>
                <span class="text-xs text-slate-500 uppercase">${s.role || 'Stakeholder'}</span>
            </div>
            <span class="text-xs text-blue-600 font-bold bg-blue-50 border border-blue-200 px-2 py-1 rounded">Profil</span>
        </div>`).join('');
}

window.jumpToStakeholder = function(id) {
    const tab = document.querySelector('[data-panel="stakeholders"]');
    if (tab) tab.click();
    setTimeout(() => { if(window.editStakeholder) window.editStakeholder(id); }, 100);
}

window.openReqFromDashboard = function (id) {
    const tab = document.querySelector('[data-panel="requirements"]');
    if (tab) tab.click();
    setTimeout(() => { if (window.showRequirementDetailById) window.showRequirementDetailById(id); }, 50);
};