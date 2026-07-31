// dashboard/js/userstories.js
import { currentProjectId } from './state.js';

// Wir speichern die geladenen Stories kurz zwischen, damit der "Bearbeiten" Button sie direkt ins Modal laden kann
let loadedUserStories = [];

export async function loadUserStories() {
    if (!currentProjectId) return;
    
    try {
        const res = await fetch(`../api/get_userstories.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const tbody = document.getElementById('userStoryTableBody');
        
        if (!data.success || data.user_stories.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-slate-400 italic">Keine User Stories gefunden.</td></tr>';
            loadedUserStories = [];
            return;
        }

        loadedUserStories = data.user_stories;
        tbody.innerHTML = '';
        
        data.user_stories.forEach(us => {
            tbody.innerHTML += `
                <tr class="hover:bg-slate-50">
                    <td class="p-3 font-mono font-bold text-blue-900">${us.us_key}</td>
                    <td class="p-3 font-semibold text-slate-800">${us.title}</td>
                    <td class="p-3 text-sm">${us.us_role || '-'}</td>
                    <td class="p-3 text-sm truncate max-w-xs text-slate-500">${us.us_action || '-'}</td>
                    <td class="p-3 text-sm text-center font-mono">${us.story_points || '-'}</td>
                    <td class="p-3 text-right">
                        <button onclick="window.editUserStory(${us.id})" class="text-blue-600 hover:underline text-xs font-bold">Bearbeiten</button>
                    </td>
                </tr>
            `;
        });
        
    } catch (e) {
        console.error("Fehler beim Laden der User Stories:", e);
    }
}

export function initUserStoryEvents() {
    // Neuer Eintrag Button
    document.getElementById('btnNewUserStory').addEventListener('click', () => {
        if (!currentProjectId) {
            alert("Bitte zuerst ein Projekt auswählen!");
            return;
        }
        document.getElementById('formUserStory').reset();
        document.getElementById('us_id').value = '';
        document.getElementById('userStoryModalTitle').textContent = 'Neue User Story';
        document.getElementById('modalUserStory').classList.remove('hidden');
    });

    // Formular Absenden
    document.getElementById('formUserStory').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            id: document.getElementById('us_id').value,
            project_id: currentProjectId,
            title: document.getElementById('us_title').value,
            us_role: document.getElementById('us_role').value,
            us_action: document.getElementById('us_action').value,
            us_benefit: document.getElementById('us_benefit').value,
            acceptance_criteria: document.getElementById('us_acceptance_criteria').value,
            story_points: document.getElementById('us_story_points').value
        };

        const res = await fetch('../api/set_userstories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            document.getElementById('modalUserStory').classList.add('hidden');
            loadUserStories();
        } else {
            alert("Fehler: " + data.error);
        }
    });
}

// Globale Funktion für den HTML "Bearbeiten" Button
window.editUserStory = function(id) {
    const us = loadedUserStories.find(item => item.id == id);
    if (!us) return;
    
    document.getElementById('us_id').value = us.id;
    document.getElementById('us_title').value = us.title;
    document.getElementById('us_role').value = us.us_role || '';
    document.getElementById('us_action').value = us.us_action || '';
    document.getElementById('us_benefit').value = us.us_benefit || '';
    document.getElementById('us_acceptance_criteria').value = us.acceptance_criteria || '';
    document.getElementById('us_story_points').value = us.story_points || '';
    
    document.getElementById('userStoryModalTitle').textContent = 'User Story bearbeiten (' + us.us_key + ')';
    document.getElementById('modalUserStory').classList.remove('hidden');
};