// dashboard/js/dashboard.js
import { currentProjectId } from './state.js';

export async function loadDashboard() {
    if (!currentProjectId) {
        document.getElementById('kpiTotalReqs').textContent = '0';
        document.getElementById('kpiWaitingReqs').textContent = '0';
        document.getElementById('kpiApprovedReqs').textContent = '0';
        return;
    }
    
    try {
        const res = await fetch(`../api/get_dashboard_kpis.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('kpiTotalReqs').textContent = data.kpis.total;
            document.getElementById('kpiWaitingReqs').textContent = data.kpis.waiting;
            document.getElementById('kpiApprovedReqs').textContent = data.kpis.approved;
        }
    } catch (err) {
        console.error("Fehler beim Laden des Dashboards:", err);
    }
}