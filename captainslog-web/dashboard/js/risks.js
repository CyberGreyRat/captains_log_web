// dashboard/js/risks.js
import { currentProjectId } from './state.js';

let loadedRisks = [];
let stakeholders = [];

export async function loadRisks() {
    if (!currentProjectId) return;

    try {
        // Stakeholder für das Dropdown laden
        const shRes = await fetch(`../api/get_stakeholders.php?project_id=${currentProjectId}`);
        const shData = await shRes.json();
        stakeholders = shData.success ? shData.stakeholders : [];

        const sel = document.getElementById('risk_responsible');
        if (sel) {
            sel.innerHTML = '<option value="">-- Niemand --</option>';
            stakeholders.forEach(s => {
                sel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
        }

        // Risiken laden (Typ 'RISK' aus der Requirements-Tabelle)
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const tbody = document.getElementById('riskTableBody');
        const mapContainer = document.getElementById('riskMapPoints');
        if (!tbody || !mapContainer) return;

        if (!data.success) return;

        // Filtere nur Elemente vom Typ RISK
        loadedRisks = data.requirements.filter(r => r.type === 'RISK');

        if (loadedRisks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="p-4 text-center text-slate-400 italic">Keine Risiken erfasst.</td></tr>';
            mapContainer.innerHTML = '';
            return;
        }

        tbody.innerHTML = '';
        mapContainer.innerHTML = '';

        loadedRisks.forEach((risk, index) => {
            let attrs = {};
            try { attrs = JSON.parse(risk.attributes || '{}'); } catch (e) {}

            const w = parseInt(attrs.w) || 1;
            const e = parseInt(attrs.e) || 1;
            const rScore = w * e;
            
            // Farbcodierung für die Zelle R
            let rColor = 'bg-emerald-500 text-white';
            if (rScore >= 5) rColor = 'bg-amber-400 text-slate-900';
            if (rScore >= 10) rColor = 'bg-orange-500 text-white';
            if (rScore >= 15) rColor = 'bg-red-600 text-white';

            // Stakeholder Name auflösen
            const shName = stakeholders.find(s => s.id == risk.source_contact)?.name || attrs.responsible || '';

            // Zeile in der Tabelle
            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 cursor-pointer" onclick="window.editRisk(${risk.id})">
                    <td class="p-2 border-r text-xs whitespace-nowrap">${new Date(risk.created_at || Date.now()).toLocaleDateString('de-DE')}</td>
                    <td class="p-2 border-r font-mono font-bold text-blue-900">${risk.req_key}</td>
                    <td class="p-2 border-r font-semibold text-slate-800">${risk.title}</td>
                    <td class="p-2 border-r text-center">${w}</td>
                    <td class="p-2 border-r text-center">${e}</td>
                    <td class="p-2 border-r text-center font-bold ${rColor}">${rScore}</td>
                    <td class="p-2 border-r text-xs">${shName}</td>
                    <td class="p-2 border-r text-xs whitespace-nowrap text-red-600">${attrs.review_date ? new Date(attrs.review_date).toLocaleDateString('de-DE') : ''}</td>
                    <td class="p-2 border-r text-xs">${attrs.mitigation_plan || ''}</td>
                    <td class="p-2 border-r text-xs">${attrs.decision || ''}</td>
                    <td class="p-2 text-xs">${attrs.effect || ''}</td>
                </tr>
            `;

            // Punkt in der Matrix
            // X-Achse: Wahrscheinlichkeit (W 1-5), Y-Achse: Auswirkung (E 1-5, inverted for visual)
            const xPos = ((w - 1) * 20) + 10; 
            const yPos = 100 - (((e - 1) * 20) + 10);
            const offset = (index * 2) % 10; // Leichter Versatz für überlappende Punkte

            mapContainer.innerHTML += `
                <div class="absolute transform -translate-x-1/2 -translate-y-1/2 w-5 h-5 bg-blue-900 text-white rounded-full flex items-center justify-center text-[10px] font-bold shadow border border-white cursor-pointer hover:scale-150 transition-transform z-10" 
                     style="left: calc(${xPos}% + ${offset}px); top: calc(${yPos}% + ${offset}px);"
                     title="${risk.req_key}: ${risk.title}">
                    ${index + 1}
                </div>
            `;
        });

    } catch (e) {
        console.error("Fehler beim Laden der Risiken:", e);
    }
}

export function initRiskEvents() {
    document.getElementById('btnNewRisk').addEventListener('click', () => {
        if (!currentProjectId) {
            alert("Bitte zuerst ein Projekt auswählen!");
            return;
        }
        document.getElementById('formRisk').reset();
        document.getElementById('risk_id').value = '';
        document.getElementById('riskModalTitle').textContent = 'Neues Risiko erfassen';
        document.getElementById('modalRisk').classList.remove('hidden');
    });

    document.getElementById('formRisk').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Wir packen alle Excel-spezifischen Felder in das attributes JSON
        const dynamicAttrs = {
            w: document.getElementById('risk_w').value,
            e: document.getElementById('risk_e').value,
            review_date: document.getElementById('risk_date').value,
            mitigation_plan: document.getElementById('risk_mitigation').value,
            decision: document.getElementById('risk_decision').value,
            effect: document.getElementById('risk_effect').value
        };

        const payload = {
            id: document.getElementById('risk_id').value,
            project_id: currentProjectId,
            type: 'RISK', // WICHTIG: Damit es ein RISK-xxx Key wird
            title: document.getElementById('risk_title').value,
            description: document.getElementById('risk_title').value, // Fallback für Requirements View
            source_contact: document.getElementById('risk_responsible').value,
            attributes: dynamicAttrs
        };

        const res = await fetch('../api/set_requirements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            document.getElementById('modalRisk').classList.add('hidden');
            loadRisks();
        } else {
            alert("Fehler: " + data.error);
        }
    });
}

window.editRisk = function(id) {
    const risk = loadedRisks.find(item => item.id == id);
    if (!risk) return;

    let attrs = {};
    try { attrs = JSON.parse(risk.attributes || '{}'); } catch (e) {}

    document.getElementById('risk_id').value = risk.id;
    document.getElementById('risk_title').value = risk.title;
    document.getElementById('risk_w').value = attrs.w || 1;
    document.getElementById('risk_e').value = attrs.e || 1;
    document.getElementById('risk_responsible').value = risk.source_contact || '';
    document.getElementById('risk_date').value = attrs.review_date || '';
    document.getElementById('risk_mitigation').value = attrs.mitigation_plan || '';
    document.getElementById('risk_decision').value = attrs.decision || '';
    document.getElementById('risk_effect').value = attrs.effect || '';

    document.getElementById('riskModalTitle').textContent = 'Risiko bearbeiten (' + risk.req_key + ')';
    document.getElementById('modalRisk').classList.remove('hidden');
};