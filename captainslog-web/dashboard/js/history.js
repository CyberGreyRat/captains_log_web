import { currentProjectId } from './state.js';

export async function loadHistory() {
    const container = document.getElementById('historyContainer');
    if (!container) return;

    if (!currentProjectId) {
        container.innerHTML = '<p class="text-sm text-slate-500 italic">Bitte wähle oben ein Projekt aus.</p>';
        return;
    }

    try {
        const res = await fetch(`../api/get_history.php?project_id=${currentProjectId}`);
        const data = await res.json();

        if (!data.success || !data.history || data.history.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 italic">Keine Historie für dieses Projekt vorhanden.</p>';
            return;
        }

        let html = '<div class="space-y-3">';
        data.history.forEach(h => {
            html += `
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-1 font-mono">
                        <span class="font-bold text-slate-700">${h.req_key} (${h.modified_at})</span>
                        <span>User: <strong>${h.modified_by_user || 'System'}</strong></span>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm mt-1">${h.title}</h3>
                    <p class="text-slate-600 mt-1"><strong>Aktion:</strong> ${h.action}</p>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (e) {
        console.error("Fehler beim Laden der Historie:", e);
    }
}