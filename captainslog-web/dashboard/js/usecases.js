// dashboard/js/usecases.js
import { currentProjectId } from './state.js';

export async function loadUseCases() {
    if (!currentProjectId) return;
    
    try {
        const res = await fetch(`../api/get_usecases.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const tbody = document.getElementById('useCaseTableBody');
        
        if (!data.success || data.use_cases.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400 italic">Keine Use Cases gefunden.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.use_cases.forEach(uc => {
            tbody.innerHTML += `
                <tr class="hover:bg-slate-50">
                    <td class="p-3 font-mono font-bold text-blue-900">${uc.uc_key}</td>
                    <td class="p-3 font-semibold text-slate-800">${uc.title}</td>
                    <td class="p-3 text-xs">${uc.primary_actor || '-'}</td>
                    <td class="p-3 text-xs truncate max-w-xs text-slate-500">${uc.main_scenario || '-'}</td>
                    <td class="p-3 text-right">
                        <button onclick="window.editUseCase(${uc.id})" class="text-blue-600 hover:underline text-xs font-bold">Bearbeiten</button>
                    </td>
                </tr>
            `;
        });
        
    } catch (e) {
        console.error("Fehler beim Laden der Use Cases:", e);
    }
}

export function initUseCaseEvents() {
    // Neuer Use Case Button
    document.getElementById('btnNewUseCase').addEventListener('click', () => {
        if (!currentProjectId) {
            alert("Bitte zuerst ein Projekt auswählen!");
            return;
        }
        document.getElementById('formUseCase').reset();
        document.getElementById('uc_id').value = '';
        document.getElementById('useCaseModalTitle').textContent = 'Neuer Use Case';
        document.getElementById('modalUseCase').classList.remove('hidden');
    });

    // Formular Absenden (Create / Update)
    document.getElementById('formUseCase').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            id: document.getElementById('uc_id').value,
            project_id: currentProjectId,
            title: document.getElementById('uc_title').value,
            primary_actor: document.getElementById('uc_primary_actor').value,
            preconditions: document.getElementById('uc_preconditions').value,
            main_scenario: document.getElementById('uc_main_scenario').value,
            alt_scenario: document.getElementById('uc_alt_scenario').value
        };

        const res = await fetch('../api/set_usecase.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            document.getElementById('modalUseCase').classList.add('hidden');
            loadUseCases();
        } else {
            alert("Fehler: " + data.error);
        }
    });
}