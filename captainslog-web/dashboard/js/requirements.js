// dashboard/js/requirements.js
import { currentProjectId } from './state.js';

let loadedRequirements = [];

export async function loadRequirements() {
    if (!currentProjectId) return;
    
    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        
        const listContainer = document.getElementById('items');
        
        if (!data.success || data.requirements.length === 0) {
            listContainer.innerHTML = '<div class="p-4 text-sm text-slate-500 italic">Noch keine Anforderungen vorhanden.</div>';
            loadedRequirements = [];
            document.getElementById('detail').innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 italic">Anforderung auswählen</div>';
            return;
        }

        loadedRequirements = data.requirements;
        
        // Parents für den Baum parsen
        loadedRequirements.forEach(req => {
            let p = req.parents;
            if (typeof p === 'string') {
                try { p = JSON.parse(p); } catch(e) { p = []; }
            }
            req.parsedParents = Array.isArray(p) ? p : [];
        });

        listContainer.innerHTML = '';
        const rendered = new Set();

        function renderNode(req, level) {
            if (rendered.has(req.req_key)) return;
            rendered.add(req.req_key);

            const btn = document.createElement('button');
            const indentRem = level * 1.2;
            const bgClass = level > 0 ? 'bg-slate-50/60' : 'bg-white';
            
            btn.className = `w-full text-left p-2.5 border-b border-slate-100 hover:bg-blue-50 transition focus:bg-blue-100 flex items-center justify-between text-xs ${bgClass}`;
            btn.style.paddingLeft = `calc(0.75rem + ${indentRem}rem)`;
            
            btn.innerHTML = `
                <div>
                    <span class="font-mono font-bold text-blue-950 mr-1">${req.req_key}</span>
                    <span class="text-slate-700 truncate">${req.title}</span>
                </div>
                <span class="text-[9px] bg-slate-200 text-slate-600 px-1 py-0.5 rounded font-mono">${req.status}</span>
            `;
            btn.onclick = () => showRequirementDetail(req);
            listContainer.appendChild(btn);

            // Kinder finden und rekursiv einrücken
            const children = loadedRequirements.filter(r => r.parsedParents.includes(req.req_key));
            children.forEach(child => renderNode(child, level + 1));
        }

        // Wurzel-Elemente (Roots) ermitteln
        const roots = loadedRequirements.filter(req => 
            req.parsedParents.length === 0 || 
            !req.parsedParents.some(pk => loadedRequirements.find(r => r.req_key === pk))
        );

        roots.forEach(root => renderNode(root, 0));
        
        // Falls Reste übrig sind
        loadedRequirements.forEach(req => {
            if (!rendered.has(req.req_key)) renderNode(req, 0);
        });
        
    } catch (e) {
        console.error("Fehler beim Laden der Anforderungen:", e);
    }
}

function showRequirementDetail(req) {
    const detail = document.getElementById('detail');
    detail.innerHTML = `
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-mono text-xs font-bold bg-slate-100 px-2 py-1 rounded text-blue-900 border">${req.type}</span>
                    <span class="font-mono text-sm text-blue-900 font-bold ml-2">${req.req_key}</span>
                    <h2 class="text-2xl font-bold text-slate-900 mt-1">${req.title}</h2>
                </div>
                <button onclick="window.editRequirement(${req.id})" class="bg-blue-900 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-blue-800">Bearbeiten</button>
            </div>
        </div>
        
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Beschreibung</h3>
        <p class="text-sm text-slate-800 whitespace-pre-wrap mb-6">${req.description || '<span class="italic text-slate-400">Keine Beschreibung</span>'}</p>
        
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Begründung (Rationale)</h3>
        <div class="bg-slate-50 border p-3 rounded text-sm text-slate-700 whitespace-pre-wrap">${req.rationale || '-'}</div>
    `;
}

export function initRequirementEvents() {
    const newBtn = document.getElementById('new');
    if(newBtn) {
        newBtn.addEventListener('click', () => {
            if (!currentProjectId) { alert("Projekt wählen!"); return; }
            document.getElementById('reqForm').reset();
            document.getElementById('reqForm').dataset.editId = '';
            document.getElementById('reqHeading').textContent = 'Neue Anforderung';
            document.getElementById('reqModal').classList.remove('hidden');
        });
    }

    const cancelBtn = document.getElementById('cancelReq');
    if(cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('reqModal').classList.add('hidden');
        });
    }

    const form = document.getElementById('reqForm');
    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: document.getElementById('reqForm').dataset.editId,
                project_id: currentProjectId,
                type: document.getElementById('type').value,
                title: document.getElementById('title').value,
                description: document.getElementById('text').value,
                rationale: document.getElementById('rationale').value
            };

            const res = await fetch('../api/set_requirement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            if (data.success) {
                document.getElementById('reqModal').classList.add('hidden');
                loadRequirements();
            } else {
                alert("Fehler: " + data.error);
            }
        });
    }
}

window.editRequirement = function(id) {
    const req = loadedRequirements.find(r => r.id == id);
    if (!req) return;
    
    document.getElementById('reqForm').dataset.editId = req.id;
    document.getElementById('type').value = req.type;
    document.getElementById('title').value = req.title;
    document.getElementById('text').value = req.description;
    document.getElementById('rationale').value = req.rationale || '';
    
    document.getElementById('reqHeading').textContent = 'Anforderung bearbeiten (' + req.req_key + ')';
    document.getElementById('reqModal').classList.remove('hidden');
};