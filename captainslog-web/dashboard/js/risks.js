// dashboard/js/risks.js
import { currentProjectId } from './state.js';

let pendingArchiveRiskId = null;
let loadedRisks = [];
let stakeholders = [];

export async function loadRisks() {
    if (!currentProjectId) return;
    try {
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

        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();

        const tbody = document.getElementById('riskTableBody');
        const mapContainer = document.getElementById('riskMapPoints');
        if (!tbody || !mapContainer) return;
        if (!data.success) return;

        loadedRisks = data.requirements.filter(r => r.type === 'RISK' && r.review_status !== 'Archiviert');

        if (loadedRisks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="p-4 text-center text-slate-400 italic">Keine aktiven Risiken erfasst.</td></tr>';
            mapContainer.innerHTML = '';
            return;
        }

        tbody.innerHTML = '';
        mapContainer.innerHTML = '';

        loadedRisks.forEach((risk, index) => {
            let attrs = {};
            try { attrs = JSON.parse(risk.attributes || '{}'); } catch (e) { }

            const w = parseInt(attrs.w) || 1;
            const e = parseInt(attrs.e) || 1;
            const rScore = w * e;

            let rColor = 'bg-emerald-500 text-white';
            if (rScore >= 5) rColor = 'bg-amber-400 text-slate-900';
            if (rScore >= 10) rColor = 'bg-orange-500 text-white';
            if (rScore >= 15) rColor = 'bg-red-600 text-white';

            const shName = stakeholders.find(s => s.id == risk.source_contact)?.name || attrs.responsible || '';

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 transition-colors">
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
                    <td class="p-2 border-r text-xs">${attrs.effect || ''}</td>
                    <td class="p-2 text-center whitespace-nowrap">
                        <button onclick="window.editRisk(${risk.id})" class="text-blue-600 hover:text-blue-900 mx-1 transition" title="Bearbeiten">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button onclick="window.archiveRisk(${risk.id})" class="text-red-500 hover:text-red-700 mx-1 transition" title="Löschen (Archivieren)">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                </tr>
            `;

            const xPos = ((w - 1) * 20) + 10;
            const yPos = 100 - (((e - 1) * 20) + 10);
            const offset = (index * 2) % 10;
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
        if (!currentProjectId) { alert("Bitte zuerst ein Projekt auswählen!"); return; }
        document.getElementById('formRisk').reset();
        document.getElementById('risk_id').value = '';
        document.getElementById('riskModalTitle').textContent = 'Neues Risiko erfassen';
        document.getElementById('modalRisk').classList.remove('hidden');
    });

    document.getElementById('formRisk').addEventListener('submit', async (e) => {
        e.preventDefault();
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
            type: 'RISK',
            title: document.getElementById('risk_title').value,
            description: document.getElementById('risk_title').value,
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

    document.getElementById('modalRiskCancelBtn').addEventListener('click', () => {
        pendingArchiveRiskId = null;
        document.getElementById('riskArchiveModal').classList.add('hidden');
    });

    document.getElementById('modalRiskConfirmBtn').addEventListener('click', async () => {
        if (!pendingArchiveRiskId) return;
        const risk = loadedRisks.find(item => item.id == pendingArchiveRiskId);
        if (!risk) return;
        let attrs = {};
        try { attrs = JSON.parse(risk.attributes || '{}'); } catch (e) { }
        const payload = {
            id: risk.id,
            project_id: currentProjectId,
            type: 'RISK',
            title: risk.title,
            description: risk.description || risk.title,
            source_contact: risk.source_contact || '',
            review_status: 'Archiviert',
            attributes: attrs
        };
        document.getElementById('riskArchiveModal').classList.add('hidden');
        pendingArchiveRiskId = null;
        try {
            const res = await fetch('../api/set_requirements.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) { loadRisks(); } else { alert("Fehler beim Löschen: " + data.error); }
        } catch (e) {
            console.error("Netzwerkfehler beim Archivieren", e);
        }
    });
}

window.editRisk = function (id) {
    const risk = loadedRisks.find(item => item.id == id);
    if (!risk) return;
    let attrs = {};
    try { attrs = JSON.parse(risk.attributes || '{}'); } catch (e) { }
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

window.archiveRisk = function (id) {
    const risk = loadedRisks.find(item => item.id == id);
    if (!risk) return;
    pendingArchiveRiskId = id;
    document.getElementById('modalArchiveRiskName').textContent = `"${risk.req_key}"`;
    document.getElementById('riskArchiveModal').classList.remove('hidden');
};