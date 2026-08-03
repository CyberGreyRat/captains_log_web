// dashboard/js/stakeholders.js
import { currentProjectId } from './state.js';

let loadedStakeholders = [];

export async function loadStakeholders() {
    if (!currentProjectId) {
        const tbody = document.getElementById('stakeholderTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-slate-400 italic">Bitte wähle oben ein Projekt aus.</td></tr>';
        return;
    }
    
    try {
        const res = await fetch(`../api/get_stakeholders.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const tbody = document.getElementById('stakeholderTableBody');
        if (!tbody) return;
        
        if (!data.success || !data.stakeholders || data.stakeholders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-slate-400 italic">Keine Stakeholder gefunden.</td></tr>';
            loadedStakeholders = [];
            drawStakeholderMap([]);
            return;
        }

        loadedStakeholders = data.stakeholders;
        tbody.innerHTML = '';
        
        data.stakeholders.forEach(s => {
            tbody.innerHTML += `
                <tr class="hover:bg-slate-50">
                    <td class="p-3 font-bold text-blue-900">${s.name}</td>
                    <td class="p-3">${s.role || '-'}</td>
                    <td class="p-3">${s.position || '-'}</td>
                    <td class="p-3 text-xs">${s.email || ''}<br>${s.phone || ''}</td>
                    <td class="p-3 text-xs">${s.expertise || '-'}<br><span class="text-slate-400">${s.availability || ''}</span></td>
                    <td class="p-3 text-xs">
                        Einfluss: <b class="${s.influence === 'High' ? 'text-red-500' : 'text-slate-500'}">${s.influence}</b><br>
                        Interesse: <b class="${s.interest === 'High' ? 'text-red-500' : 'text-slate-500'}">${s.interest}</b>
                    </td>
                    <td class="p-3 text-right">
                        <button type="button" onclick="window.editStakeholder(${s.id})" class="text-blue-600 hover:underline text-xs font-bold">Bearbeiten</button>
                    </td>
                </tr>
            `;
        });

        drawStakeholderMap(data.stakeholders);
        
    } catch (e) {
        console.error("Fehler beim Laden der Stakeholder:", e);
    }
}

function drawStakeholderMap(stakeholders) {
    const mapContainer = document.getElementById('stakeholderMapPoints');
    if (!mapContainer) return;
    
    mapContainer.innerHTML = ''; 

    stakeholders.forEach(s => {
        let xBase = s.interest === 'High' ? 75 : 25;
        let yBase = s.influence === 'High' ? 25 : 75;

        let xOffset = (Math.random() - 0.5) * 15; 
        let yOffset = (Math.random() - 0.5) * 15;

        const point = document.createElement('div');
        point.className = 'absolute transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center transition-transform hover:scale-110 z-10 cursor-pointer';
        point.style.left = `${Math.max(10, Math.min(90, xBase + xOffset))}%`;
        point.style.top = `${Math.max(10, Math.min(90, yBase + yOffset))}%`;
        
        point.innerHTML = `
            <div class="bg-blue-900 text-white p-1.5 -full shadow-md">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            </div>
            <span class="text-[10px] font-bold text-slate-800 bg-white/90 px-1.5 py-0.5  shadow-sm border whitespace-nowrap mt-1">${s.name}</span>
        `;

        mapContainer.appendChild(point);
    });
}

export function initStakeholderEvents() {
    const btnNew = document.getElementById('btnNewStakeholder');
    if (btnNew) {
        btnNew.addEventListener('click', () => {
            if (!currentProjectId) { 
                alert("Bitte wähle oben im Header zuerst ein Projekt aus!"); 
                return; 
            }
            document.getElementById('formStakeholder').reset();
            document.getElementById('stk_id').value = '';
            document.getElementById('modalStakeholder').classList.remove('hidden');
        });
    }

    const formStakeholder = document.getElementById('formStakeholder');
    if (formStakeholder) {
        formStakeholder.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!currentProjectId) {
                alert("Fehler: Kein Projekt ausgewählt!");
                return;
            }

            const payload = {
                id: document.getElementById('stk_id').value,
                project_id: currentProjectId,
                name: document.getElementById('stk_name').value,
                role: document.getElementById('stk_role').value,
                position: document.getElementById('stk_position').value,
                email: document.getElementById('stk_email').value,
                phone: document.getElementById('stk_phone').value,
                expertise: document.getElementById('stk_expertise').value,
                availability: document.getElementById('stk_availability').value,
                influence: document.getElementById('stk_influence').value,
                interest: document.getElementById('stk_interest').value
            };

            try {
                const res = await fetch('../api/set_stakeholder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.success) {
                    document.getElementById('modalStakeholder').classList.add('hidden');
                    loadStakeholders();
                } else {
                    alert("Fehler beim Speichern: " + (data.error || "Unbekannter Fehler"));
                }
            } catch (err) {
                console.error("Netzwerkfehler:", err);
                alert("Verbindungsfehler zum Server.");
            }
        });
    }
}

window.editStakeholder = function(id) {
    const s = loadedStakeholders.find(item => item.id == id);
    if (!s) return;
    
    document.getElementById('stk_id').value = s.id;
    document.getElementById('stk_name').value = s.name;
    document.getElementById('stk_role').value = s.role || '';
    document.getElementById('stk_position').value = s.position || '';
    document.getElementById('stk_email').value = s.email || '';
    document.getElementById('stk_phone').value = s.phone || '';
    document.getElementById('stk_expertise').value = s.expertise || '';
    document.getElementById('stk_availability').value = s.availability || '';
    document.getElementById('stk_influence').value = s.influence || 'Low';
    document.getElementById('stk_interest').value = s.interest || 'Low';
    
    document.getElementById('modalStakeholder').classList.remove('hidden');
};