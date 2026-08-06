// dashboard/js/history.js
import { currentProjectId } from './state.js';
import { fetchHistory } from './api.js';

// Rendert die globale Historie (Logbuch-Ansicht)
export async function loadHistory() {
    const container = document.getElementById('historyContainer');
    if (!container) return;

    if (!currentProjectId) {
        container.innerHTML = '<p class="text-sm text-slate-500 italic">Bitte wähle oben ein Projekt aus.</p>';
        return;
    }

    try {
        const historyData = await fetchHistory();

        if (!historyData || historyData.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 italic">Keine Historie für dieses Projekt vorhanden.</p>';
            return;
        }

        let html = '<div class="space-y-3">';
        historyData.forEach(h => {
            html += `
                <div class="-lg border border-slate-200 bg-slate-50 p-4 text-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-1 font-mono">
                        <span class="font-bold text-slate-700">${h.req_key || 'EINTRAG'} (${h.modified_at || ''})</span>
                        <span>User: <strong>${h.modified_by_user || 'System'} @ ${h.hostname || 'Unbekannt'}</strong></span>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm mt-1">${h.title || ''}</h3>
                    <p class="text-slate-600 mt-1"><strong>Aktion:</strong> ${h.action || 'Änderung'}</p>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (e) {
        console.error("Fehler beim Laden der Historie:", e);
        container.innerHTML = '<p class="text-sm text-red-500">Fehler beim Laden der Historie.</p>';
    }
}

// Rendert die Historie für eine spezifische Anforderung im Detail-Fenster
export async function renderHistory(reqKey) {
    try {
        const historyData = await fetchHistory(); 
        
        if (!historyData || historyData.length === 0) return;

        // Filtere nur die Einträge für die aktuell gewählte Anforderung
        const history = historyData.filter(h => h.req_key === reqKey);
        
        let html = `<div class="mt-8 border-t pt-4">
            <h3 class="text-lg font-bold text-blue-900 mb-3">Historie & Audit-Trail</h3>`;
            
        if (history.length === 0) {
            html += `<p class="text-sm text-slate-500">Keine Änderungen erfasst.</p>`;
        } else {
            html += `<div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 border-b">
                    <tr>
                        <th class="p-2">Datum</th>
                        <th class="p-2">Benutzer</th>
                        <th class="p-2">Änderung</th>
                    </tr>
                </thead>
                <tbody>`;
            history.forEach(entry => {
                const date = new Date(entry.modified_at).toLocaleString('de-DE');
                const user = `${entry.modified_by_user || 'System'} @ ${entry.hostname || 'Unbekannt'}`;
                const action = entry.action; 
                
                html += `<tr class="border-b hover:bg-slate-50">
                    <td class="p-2 whitespace-nowrap">${date}</td>
                    <td class="p-2 font-semibold">${user}</td>
                    <td class="p-2">${action}</td>
                </tr>`;
            });
            html += `</tbody></table></div>`;
        }
        html += `</div>`;
        
        document.getElementById('detail').insertAdjacentHTML('beforeend', html);
        
    } catch (error) {
        console.error("Fehler beim Rendern der Historie:", error);
    }
}