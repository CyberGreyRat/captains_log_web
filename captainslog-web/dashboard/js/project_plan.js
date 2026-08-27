// dashboard/js/project_plan.js
import { currentProjectId } from './state.js';

let loadedTasks = [];
let loadedTemplates = [];
let allRequirements = [];
let allIssues = [];

export async function loadProjectPlan() {
    if (!currentProjectId) return;

    try {
        const res = await fetch(`../api/get_tasks.php?project_id=${currentProjectId}`);
        const data = await res.json();
        const tbody = document.getElementById('taskTableBody');

        if (!tbody) return;

        if (!data.success || data.tasks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-slate-400 italic font-medium">Keine Aufgaben im Projektplan gefunden.</td></tr>';
            loadedTasks = [];
        } else {
            loadedTasks = data.tasks;
            tbody.innerHTML = '';

            const groupedTasks = {};
            loadedTasks.forEach(task => {
                const cat = task.category || 'Allgemein';
                if (!groupedTasks[cat]) groupedTasks[cat] = [];
                groupedTasks[cat].push(task);
            });

            for (const cat in groupedTasks) {
                tbody.innerHTML += `
                    <tr class="bg-blue-950 border-b-2 border-blue-900">
                        <td colspan="7" class="p-3 px-4 text-sm font-extrabold text-white uppercase tracking-widest shadow-inner">${cat}</td>
                    </tr>
                `;

                const mainTasks = groupedTasks[cat].filter(t => !t.parent_id);
                mainTasks.forEach(mainTask => {
                    renderTaskRow(mainTask, tbody);
                });
            }
        }

        if (data.templates) {
            loadedTemplates = data.templates;
            const tplCategory = document.getElementById('tpl_category');
            if (tplCategory && tplCategory.options.length <= 1) {
                const cats = [...new Set(loadedTemplates.map(t => t.category))].sort();
                cats.forEach(c => {
                    tplCategory.innerHTML += `<option value="${c}">${c}</option>`;
                });
            }
        }

        await fetchRequirementsForMenu();
        await fetchIssuesForMenu();
        await fetchTeamMembers();

    } catch (e) {
        console.error("Fehler beim Laden des Plans:", e);
    }
}

function renderTaskRow(task, tbody) {
    const sDate = task.start_date ? new Date(task.start_date).toLocaleDateString('de-DE') : '-';
    const eDate = task.end_date ? new Date(task.end_date).toLocaleDateString('de-DE') : '-';

    // Assignee Logik: Direkt zugewiesen ODER aus Issues geerbt
    let assigneeHtml = '<span class="text-slate-400 italic">-</span>';
    if (task.assignee && task.assignee.trim() !== '') {
        assigneeHtml = `<span class="font-bold text-slate-800">${task.assignee}</span>`;
    } else if (task.inherited_assignees && task.inherited_assignees.trim() !== '') {
        assigneeHtml = `<span class="text-xs text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded shadow-sm" title="Aus verknüpften Issues übernommen">Übernommen: ${task.inherited_assignees}</span>`;
    }

    let tagsHtml = '';

    try {
        const reqs = JSON.parse(task.linked_reqs || '[]');
        reqs.forEach(r => {
            tagsHtml += `<span class="inline-block bg-indigo-100 text-indigo-900 text-[10px] px-1.5 py-0.5 rounded border border-indigo-300 mr-1 mt-1 font-mono font-bold shadow-sm">${r}</span>`;
        });
    } catch (e) { }

    if (task.has_checklist) {
        tagsHtml += `<span class="inline-block bg-sky-100 text-sky-900 text-[10px] px-2 py-0.5 rounded border border-sky-300 mr-1 mt-1 font-bold shadow-sm">📋 Checkliste: ${task.checklist_done}/${task.checklist_total}</span>`;
    }

    let autoIcon = '';
    if (task.is_auto_progress && !task.has_checklist) {
        autoIcon = `<span class="text-[9px] bg-indigo-50 border border-indigo-300 text-indigo-700 px-1 rounded font-bold mr-1">AUTO</span>`;
    } else if (task.has_checklist) {
        autoIcon = `<span class="text-[9px] bg-sky-50 border border-sky-300 text-sky-700 px-1 rounded font-bold mr-1">LIST</span>`;
    }

    const progress = task.progress_pct || 0;
    const barColor = progress === 100 ? 'bg-emerald-500' : 'bg-blue-500';
    const effortVal = task.effort_mt ? task.effort_mt + ' h' : '-';
    const indentClass = 'font-bold bg-white hover:bg-blue-50/50 border-t border-slate-200 transition-colors';

    const iconEye = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>`;
    const iconEdit = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>`;
    const iconTrash = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;

    tbody.innerHTML += `
        <tr class="${indentClass}">
            <td class="p-3 font-mono text-sm font-extrabold text-slate-800 border-r border-slate-100">${task.wbs_code || ''}</td>
            <td class="p-3 border-r border-slate-100">
                <div class="text-blue-950 text-base font-extrabold">${task.title}</div>
                <div>${tagsHtml}</div>
            </td>
            <td class="p-3 text-xs border-r border-slate-100 leading-tight">${assigneeHtml}</td>
            <td class="p-3 text-center text-sm font-mono font-semibold text-slate-800 border-r border-slate-100">${effortVal}</td>
            <td class="p-3 text-center text-[11px] whitespace-nowrap font-medium text-slate-600 border-r border-slate-100">${sDate} <br> ${eDate}</td>
            <td class="p-3 border-r border-slate-100">
                <div class="flex justify-between items-end mb-1 text-xs">
                    <div>${autoIcon}</div>
                    <span class="font-extrabold text-slate-800">${progress}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 border border-slate-300">
                    <div class="${barColor} h-2.5 rounded-full transition-all duration-500" style="width: ${progress}%"></div>
                </div>
            </td>
            <td class="p-3 text-right">
                <div class="flex justify-end gap-1.5">
                    <button onclick="window.viewTaskAnalytics(${task.id})" class="text-blue-600 hover:text-white hover:bg-blue-600 transition p-1.5 bg-blue-50 border border-blue-200 rounded shadow-sm" title="Analyse & Checkliste">${iconEye}</button>
                    <button onclick="window.editTask(${task.id})" class="text-slate-500 hover:text-blue-600 transition p-1.5 bg-slate-50 border border-slate-200 rounded shadow-sm" title="Bearbeiten">${iconEdit}</button>
                    <button onclick="window.deleteTask(${task.id})" class="text-slate-500 hover:text-red-600 transition p-1.5 bg-slate-50 border border-slate-200 rounded shadow-sm" title="Löschen">${iconTrash}</button>
                </div>
            </td>
        </tr>
    `;
}

function generateAutoId() {
    const mainTasks = loadedTasks.filter(t => !t.parent_id);
    document.getElementById('task_wbs').value = String(mainTasks.length + 1);
}

// =========================================================================
// NEU: ZENTRALE PICKER LOGIK ("Windows Explorer" Style)
// =========================================================================

let currentPickerMode = ''; // 'req' oder 'issue'

async function fetchRequirementsForMenu() {
    if (!currentProjectId) return;
    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        if (data.success) allRequirements = data.requirements;
    } catch (e) { console.error(e); }
}

async function fetchIssuesForMenu() {
    if (!currentProjectId) return;
    try {
        const res = await fetch(`../api/get_issues.php?project_id=${currentProjectId}`);
        const data = await res.json();
        if (data.success) allIssues = data.issues || [];
    } catch (e) { console.error(e); }
}

window.openReqPicker = function() {
    currentPickerMode = 'req';
    document.getElementById('pickerTitle').textContent = 'Anforderungen (Requirements) auswählen';
    
    const currentVals = document.getElementById('task_linked_reqs').value.split(',').filter(x => x);
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    
    const grouped = {};
    allRequirements.forEach(req => {
        if (!grouped[req.type]) grouped[req.type] = [];
        grouped[req.type].push(req);
    });

    for(const type in grouped) {
        html += `
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="bg-blue-950 text-white px-4 py-2 text-sm font-extrabold uppercase tracking-wider sticky top-0 z-10">${type}</div>
                <div class="max-h-64 overflow-y-auto p-2 space-y-1">
        `;
        grouped[type].forEach(req => {
            const isChecked = currentVals.includes(req.req_key) ? 'checked' : '';
            html += `
                <label class="flex items-start gap-3 p-2.5 hover:bg-blue-50 rounded-md cursor-pointer transition border border-transparent hover:border-blue-100">
                    <input type="checkbox" value="${req.req_key}" class="picker-cb mt-0.5 w-4 h-4 text-blue-700 rounded border-slate-300" ${isChecked}>
                    <div class="flex flex-col">
                        <span class="text-xs font-extrabold text-slate-900 font-mono">${req.req_key}</span>
                        <span class="text-xs text-slate-600 leading-tight mt-0.5">${req.title}</span>
                    </div>
                </label>
            `;
        });
        html += `</div></div>`;
    }
    html += '</div>';
    
    document.getElementById('pickerContent').innerHTML = html;
    document.getElementById('modalPicker').classList.remove('hidden');
    document.getElementById('modalPicker').style.display = 'flex'; // Tailwind Fix
};

// NEU: Team-Mitglieder für die Datalist laden (ROBUSTE VERSION)
async function fetchTeamMembers() {
    if (!currentProjectId) return;
    try {
        const res = await fetch(`../api/get_project_team.php?project_id=${currentProjectId}`);
        const data = await res.json();
        const datalist = document.getElementById('teamMembersList');
        
        // Fängt beide Varianten ab, je nachdem wie dein API-Skript das Array nennt
        const teamArray = data.team || data.members || [];
        
        if (datalist) {
            datalist.innerHTML = '';
            teamArray.forEach(m => {
                // Laut deiner Datenbankstruktur hast du "username" in der users-Tabelle
                const displayName = m.username || m.name || `User-ID: ${m.user_id}`;
                datalist.innerHTML += `<option value="${displayName}"></option>`;
            });
        }
    } catch (e) { 
        console.error("Fehler beim Laden des Teams:", e); 
    }
}

window.openIssuePicker = function () {
    currentPickerMode = 'issue';
    document.getElementById('pickerTitle').textContent = 'Issues (Bugs, Change Requests) auswählen';

    const currentVals = document.getElementById('task_linked_issues').value.split(',').filter(x => x);
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

    const grouped = {};
    
    // NEU: Issues sauber nach ihrer Nummer im Key (z.B. ISSUE-012) sortieren
    const sortedIssues = [...allIssues].sort((a, b) => {
        const numA = parseInt((a.issue_key || '').replace(/\D/g, '')) || 0;
        const numB = parseInt((b.issue_key || '').replace(/\D/g, '')) || 0;
        return numA - numB;
    });

    sortedIssues.forEach(iss => {
        // FILTER ENTFERNT: Geschlossene Issues werden jetzt auch angezeigt!
        const type = iss.issue_type || 'bug';
        if (!grouped[type]) grouped[type] = [];
        grouped[type].push(iss);
    });

    if (Object.keys(grouped).length === 0) {
        html = '<div class="p-6 text-center text-slate-500 italic bg-white rounded border border-slate-200">Keine Issues im Projekt vorhanden.</div>';
    }

    for (const type in grouped) {
        html += `
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="bg-rose-950 text-white px-4 py-2 text-sm font-extrabold uppercase tracking-wider sticky top-0 z-10">${type}</div>
                <div class="max-h-64 overflow-y-auto p-2 space-y-1">
        `;
        grouped[type].forEach(iss => {
            const isChecked = currentVals.includes(String(iss.id)) ? 'checked' : '';
            
            // NEU: Wenn ein Issue geschlossen ist, machen wir es leicht transparent und geben ihm ein Label
            const isClosed = (iss.status === 'closed' || iss.status === 'approved' || iss.status === 'rejected');
            const statusBadge = isClosed ? `<span class="ml-2 text-[9px] bg-slate-200 text-slate-600 px-1 py-0.5 rounded uppercase">${iss.status}</span>` : '';
            const opacityClass = isClosed ? 'opacity-60 grayscale' : '';

            html += `
                <label class="flex items-start gap-3 p-2.5 hover:bg-rose-50 rounded-md cursor-pointer transition border border-transparent hover:border-rose-100 ${opacityClass}">
                    <input type="checkbox" value="${iss.id}" class="picker-cb mt-0.5 w-4 h-4 text-rose-700 rounded border-slate-300" ${isChecked}>
                    <div class="flex flex-col">
                        <span class="text-xs font-extrabold text-slate-900 font-mono">${iss.issue_key} ${statusBadge}</span>
                        <span class="text-xs text-slate-600 leading-tight mt-0.5">${iss.title}</span>
                    </div>
                </label>
            `;
        });
        html += `</div></div>`;
    }
    html += '</div>';

    document.getElementById('pickerContent').innerHTML = html;
    document.getElementById('modalPicker').classList.remove('hidden');
    document.getElementById('modalPicker').style.display = 'flex';
};

// UI RE-RENDER HILFSFUNKTIONEN
function renderReqTagsUI(vals) {
    const container = document.getElementById('task_selected_reqs_container');
    if(!container) return;
    if (vals.length === 0) {
        container.innerHTML = '<span class="text-slate-500 text-sm font-medium italic mt-1">Keine ausgewählt...</span>';
        return;
    }
    container.innerHTML = vals.map(key => `
        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-950 px-2.5 py-1 rounded text-xs font-extrabold font-mono border border-indigo-300 shadow-sm">
            ${key}
            <button type="button" onclick="window.removeReqTag('${key}')" class="text-indigo-500 hover:text-red-600 transition-colors ml-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </span>
    `).join('');
}

function renderIssueTagsUI(ids) {
    const container = document.getElementById('task_selected_issues_container');
    if(!container) return;
    if (ids.length === 0) {
        container.innerHTML = '<span class="text-slate-500 text-sm font-medium italic mt-1">Keine ausgewählt...</span>';
        return;
    }
    container.innerHTML = ids.map(id => {
        const iss = allIssues.find(i => i.id == id);
        const key = iss ? iss.issue_key : id;
        return `
        <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-950 px-2.5 py-1 rounded text-xs font-extrabold font-mono border border-rose-300 shadow-sm">
            ${key}
            <button type="button" onclick="window.removeIssueTag('${id}')" class="text-rose-500 hover:text-red-600 transition-colors ml-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </span>
        `;
    }).join('');
}

window.removeReqTag = function(key) {
    let vals = document.getElementById('task_linked_reqs').value.split(',').filter(x => x);
    vals = vals.filter(v => v !== key);
    document.getElementById('task_linked_reqs').value = vals.join(',');
    renderReqTagsUI(vals);
};

window.removeIssueTag = function(id) {
    let vals = document.getElementById('task_linked_issues').value.split(',').filter(x => x);
    vals = vals.filter(v => v !== String(id));
    document.getElementById('task_linked_issues').value = vals.join(',');
    renderIssueTagsUI(vals);
};

// EVENT FÜR DEN "ÜBERNEHMEN" BUTTON IM PICKER
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnPickerApply').addEventListener('click', () => {
        const checkboxes = Array.from(document.querySelectorAll('#pickerContent .picker-cb:checked'));
        const vals = checkboxes.map(cb => cb.value);
        
        if (currentPickerMode === 'req') {
            document.getElementById('task_linked_reqs').value = vals.join(',');
            renderReqTagsUI(vals);
        } else if (currentPickerMode === 'issue') {
            document.getElementById('task_linked_issues').value = vals.join(',');
            renderIssueTagsUI(vals);
        }
        
        document.getElementById('modalPicker').classList.add('hidden');
        document.getElementById('modalPicker').style.display = '';
    });
});

// =========================================================================
// INIT EVENTS
// =========================================================================
export function initProjectPlanEvents() {
    const btnNew = document.getElementById('btnNewTask');
    const form = document.getElementById('formTask');
    const autoToggle = document.getElementById('task_is_auto');
    const tplCat = document.getElementById('tpl_category');
    const tplItem = document.getElementById('tpl_item');

    if (btnNew) {
        btnNew.addEventListener('click', () => {
            if (!currentProjectId) { alert("Projekt wählen!"); return; }
            form.reset();
            document.getElementById('task_id').value = '';
            document.getElementById('taskModalTitle').textContent = 'Neue Aufgabe anlegen';
            
            // Löst den Fehler, wenn is_auto nicht existiert, aber das Feld MUSS existieren!
            if (autoToggle) autoToggle.dispatchEvent(new Event('change'));

            if (tplCat) tplCat.value = '';
            if (tplItem) {
                tplItem.innerHTML = '<option value="">-- Zuerst Hauptgruppe wählen --</option>';
                tplItem.disabled = true;
                tplItem.classList.add('bg-slate-100', 'cursor-not-allowed');
                tplItem.classList.remove('bg-white');
            }

            document.getElementById('task_linked_reqs').value = '';
            renderReqTagsUI([]);

            document.getElementById('task_linked_issues').value = '';
            renderIssueTagsUI([]);

            generateAutoId();

            document.getElementById('modalTask').classList.remove('hidden');
        });
    }

    if (tplCat && tplItem) {
        tplCat.addEventListener('change', (e) => {
            const cat = e.target.value;
            const catInput = document.getElementById('task_category');
            if (catInput) catInput.value = cat;

            if (!cat) {
                tplItem.innerHTML = '<option value="">-- Zuerst Hauptgruppe wählen --</option>';
                tplItem.disabled = true;
                tplItem.classList.add('bg-slate-100', 'cursor-not-allowed');
                tplItem.classList.remove('bg-white');
                return;
            }

            tplItem.disabled = false;
            tplItem.classList.remove('bg-slate-100', 'cursor-not-allowed');
            tplItem.classList.add('bg-white');
            tplItem.innerHTML = '<option value="">-- Aufgabe wählen --</option>';

            const items = loadedTemplates.filter(t => t.category === cat);
            items.forEach(t => {
                tplItem.innerHTML += `<option value="${t.id}">${t.title} (${t.default_effort} h)</option>`;
            });
        });

        tplItem.addEventListener('change', (e) => {
            const tpl = loadedTemplates.find(t => t.id == e.target.value);
            if (tpl) {
                document.getElementById('task_category').value = tpl.category;
                document.getElementById('task_title').value = tpl.title;
                document.getElementById('task_effort').value = tpl.default_effort;
            }
        });
    }

    if (autoToggle) {
        autoToggle.addEventListener('change', (e) => {
            const isAuto = e.target.checked;
            document.getElementById('container_linked_reqs').style.display = isAuto ? 'block' : 'none';
            document.getElementById('container_linked_issues').style.display = isAuto ? 'block' : 'none';
            document.getElementById('container_manual_progress').style.display = isAuto ? 'none' : 'block';
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: document.getElementById('task_id').value,
                project_id: currentProjectId,
                parent_id: null,
                category: document.getElementById('task_category').value,
                title: document.getElementById('task_title').value,
                description: document.getElementById('task_description').value,
                wbs_code: document.getElementById('task_wbs').value,
                assignee: document.getElementById('task_assignee').value,
                start_date: document.getElementById('task_start').value,
                end_date: document.getElementById('task_end').value,
                effort_mt: document.getElementById('task_effort').value,
                performance_pct: document.getElementById('task_performance').value,
                is_auto_progress: document.getElementById('task_is_auto').checked,

                linked_reqs: document.getElementById('task_linked_reqs') ? document.getElementById('task_linked_reqs').value : '',
                linked_issues: document.getElementById('task_linked_issues') ? document.getElementById('task_linked_issues').value : '',

                progress_pct: document.getElementById('task_progress').value
            };

            const res = await fetch('../api/set_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('modalTask').classList.add('hidden');
                loadProjectPlan();
            } else {
                alert("Fehler: " + data.error);
            }
        });
    }
}

window.editTask = function (id) {
    const task = loadedTasks.find(item => item.id == id);
    if (!task) return;

    document.getElementById('task_id').value = task.id;
    document.getElementById('task_category').value = task.category || '';
    document.getElementById('task_title').value = task.title;
    document.getElementById('task_description').value = task.description || '';
    document.getElementById('task_wbs').value = task.wbs_code || '';
    document.getElementById('task_assignee').value = task.assignee || '';
    document.getElementById('task_start').value = task.start_date || '';
    document.getElementById('task_end').value = task.end_date || '';
    document.getElementById('task_effort').value = task.effort_mt || '';
    document.getElementById('task_performance').value = task.performance_pct || 100;

    document.getElementById('task_is_auto').checked = task.is_auto_progress == 1;
    document.getElementById('task_progress').value = task.progress_pct || 0;

    // Verknüpfte Elemente laden und rendern
    const reqs = JSON.parse(task.linked_reqs || '[]');
    document.getElementById('task_linked_reqs').value = reqs.join(',');
    renderReqTagsUI(reqs);

    const issues = JSON.parse(task.linked_issues || '[]');
    document.getElementById('task_linked_issues').value = issues.join(',');
    renderIssueTagsUI(issues);

    document.getElementById('task_is_auto').dispatchEvent(new Event('change'));
    document.getElementById('taskModalTitle').textContent = 'Aufgabe bearbeiten (ID: ' + (task.wbs_code || 'Neu') + ')';
    document.getElementById('modalTask').classList.remove('hidden');
};

// =========================================================================
// ANALYTICS & DELETE LOGIC
// =========================================================================
window.viewTaskAnalytics = async function (id) {
    document.getElementById('analyticsPanelOverlay').classList.remove('hidden');
    setTimeout(() => { document.getElementById('analyticsPanel').classList.remove('translate-x-full'); }, 10);

    document.getElementById('analyticsTitle').textContent = "Lade Analyse...";
    document.getElementById('analyticsContributors').innerHTML = '<div class="text-xs text-slate-500 animate-pulse">Lade Beiträge...</div>';
    document.getElementById('analyticsReqList').innerHTML = '';
    document.getElementById('analyticsChecklistContainer').classList.add('hidden');
    document.getElementById('analyticsProgressBar').style.width = '0%';

    try {
        const res = await fetch(`../api/get_task_analytics.php?task_id=${id}&project_id=${currentProjectId}`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById('analyticsTitle').textContent = "Fehler: " + data.error;
            return;
        }

        const a = data.analytics;
        document.getElementById('analyticsTitle').textContent = (a.wbs_code ? a.wbs_code + ' - ' : '') + a.task_title;

        // ALLES IN EINEN TOPF WERFEN
        const totalItems = (a.has_checklist ? a.subtasks.length : 0) + a.total_reqs + a.total_issues;
        const doneItems = (a.has_checklist ? a.subtasks.filter(s => s.progress_pct == 100).length : 0) + a.approved_reqs + a.closed_issues;
        const combinedProgress = totalItems > 0 ? Math.round((doneItems / totalItems) * 100) : 0;

        let textParts = [];
        if (a.has_checklist) textParts.push(`${a.subtasks.filter(s => s.progress_pct == 100).length}/${a.subtasks.length} Check`);
        if (a.total_reqs > 0) textParts.push(`${a.approved_reqs}/${a.total_reqs} Reqs`);
        if (a.total_issues > 0) textParts.push(`${a.closed_issues}/${a.total_issues} Issues`);

        document.getElementById('analyticsReqCount').textContent = textParts.length > 0 ? textParts.join(' & ') + ' erledigt' : '0 Elemente verknüpft';
        document.getElementById('analyticsTotalProgress').textContent = `${combinedProgress}%`;
        document.getElementById('analyticsProgressBar').style.width = `${combinedProgress}%`;

        // CHECKLISTE RENDERN
        if (a.has_checklist) {
            document.getElementById('analyticsChecklistContainer').classList.remove('hidden');
            let checkHtml = '';
            a.subtasks.forEach(st => {
                const checked = st.progress_pct == 100 ? 'checked' : '';
                checkHtml += `
                    <label class="flex items-start gap-3 p-3 bg-white border border-slate-200 rounded hover:bg-sky-50 cursor-pointer shadow-sm transition">
                        <input type="checkbox" ${checked} onchange="window.toggleSubtask(${st.id}, this.checked, ${id})" class="mt-0.5 w-5 h-5 text-sky-600 rounded">
                        <span class="text-sm font-semibold text-slate-800 ${checked ? 'line-through text-slate-400' : ''}">${st.title}</span>
                    </label>
                `;
            });
            document.getElementById('analyticsChecklist').innerHTML = checkHtml;
        }

        // CONTRIBUTORS RENDERN
        const contDiv = document.getElementById('analyticsContributors');
        if (Object.keys(a.contributors).length === 0) {
            contDiv.innerHTML = '<div class="text-xs font-semibold text-slate-400 italic bg-slate-100 p-3 rounded">Noch keine Zuweisungen / Freigaben.</div>';
        } else {
            let html = '';
            // Um die Balken korrekt zu zeichnen, nehmen wir die Gesamtzahl (Requirements + Issues)
            const totalWorkable = a.total_reqs + a.total_issues;
            for (const [user, count] of Object.entries(a.contributors)) {
                const userPct = totalWorkable > 0 ? Math.round((count / totalWorkable) * 100) : 0;
                html += `
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-slate-800 font-mono"><span class="text-blue-600">@</span>${user}</span>
                            <span class="text-xs font-extrabold text-blue-900">${userPct}% (${count} erledigt)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5"><div class="bg-blue-600 h-1.5 rounded-full" style="width: ${userPct}%"></div></div>
                    </div>
                `;
            }
            contDiv.innerHTML = html;
        }

        // DETAIL-LOG (REQS & ISSUES)
        const logDiv = document.getElementById('analyticsReqList');
        if (a.req_details.length === 0 && a.issue_details.length === 0) {
            logDiv.innerHTML = '<div class="text-xs text-slate-500 italic">Keine Anforderungen oder Issues verknüpft.</div>';
        } else {
            let logHtml = '';
            
            // Requirements einfügen
            a.req_details.forEach(r => {
                const isAppr = r.status === 'Geprüft & Freigegeben';
                const statusColor = isAppr ? 'text-indigo-700 bg-indigo-50 border-indigo-200' : 'text-slate-600 bg-white border-slate-200';
                const icon = isAppr
                    ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>'
                    : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

                logHtml += `
                    <div class="border ${statusColor} rounded-md p-3 text-sm mb-2 shadow-sm">
                        <div class="flex justify-between font-bold mb-1">
                            <span class="font-mono text-indigo-900">${r.req_key}</span>
                            <span class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider">${icon} ${r.status}</span>
                        </div>
                        <div class="font-semibold text-slate-700 leading-tight mb-2">${r.title}</div>
                        ${isAppr ? `<div class="text-[10px] text-indigo-800 border-t border-indigo-200/50 pt-2 mt-1 font-mono uppercase tracking-wide">Freigegeben von: <b>${r.approved_by}</b> <span class="opacity-70">am ${r.date}</span></div>` : ''}
                    </div>
                `;
            });

            // Issues einfügen
            a.issue_details.forEach(i => {
                const isClosed = ['closed', 'approved', 'rejected'].includes(i.status);
                const statusColor = isClosed ? 'text-rose-700 bg-rose-50 border-rose-200' : 'text-slate-600 bg-white border-slate-200';
                const icon = isClosed
                    ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>'
                    : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

                logHtml += `
                    <div class="border ${statusColor} rounded-md p-3 text-sm mb-2 shadow-sm">
                        <div class="flex justify-between font-bold mb-1">
                            <span class="font-mono text-rose-900">${i.issue_key}</span>
                            <span class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider">${icon} ${i.status}</span>
                        </div>
                        <div class="font-semibold text-slate-700 leading-tight mb-2">${i.title}</div>
                        <div class="text-[10px] ${isClosed ? 'text-rose-800 border-rose-200/50' : 'text-slate-500 border-slate-200'} border-t pt-2 mt-1 font-mono uppercase tracking-wide">Zuständig: <b>${i.assignee}</b></div>
                    </div>
                `;
            });

            logDiv.innerHTML = logHtml;
        }

    } catch (e) {
        console.error(e);
        document.getElementById('analyticsTitle').textContent = "Netzwerk- oder Scriptfehler";
    }
};

window.closeAnalyticsPanel = function () {
    document.getElementById('analyticsPanel').classList.add('translate-x-full');
    setTimeout(() => { document.getElementById('analyticsPanelOverlay').classList.add('hidden'); }, 300);
};

window.toggleSubtask = async function (subtaskId, isChecked, parentTaskId) {
    try {
        await fetch('../api/toggle_subtask.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: subtaskId, completed: isChecked })
        });
        await loadProjectPlan();
        window.viewTaskAnalytics(parentTaskId);
    } catch (e) { console.error(e); }
};

window.deleteTask = async function (id) {
    if (!confirm("Möchtest du diese Aufgabe wirklich löschen?")) return;
    try {
        const res = await fetch('../api/delete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, project_id: currentProjectId })
        });
        const data = await res.json();
        if (data.success) { loadProjectPlan(); } else { alert("Fehler beim Löschen: " + data.error); }
    } catch (e) { console.error(e); }
};