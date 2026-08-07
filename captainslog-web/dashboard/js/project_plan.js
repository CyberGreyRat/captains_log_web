// dashboard/js/project_plan.js
import { currentProjectId } from './state.js';

let loadedTasks = [];
let loadedTemplates = [];
let allRequirements = [];

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

                // NUR NOCH HAUPTAUFGABEN RENDERN (!task.parent_id)
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

    } catch (e) {
        console.error("Fehler beim Laden des Plans:", e);
    }
}

function renderTaskRow(task, tbody) {
    const sDate = task.start_date ? new Date(task.start_date).toLocaleDateString('de-DE') : '-';
    const eDate = task.end_date ? new Date(task.end_date).toLocaleDateString('de-DE') : '-';

    let tagsHtml = '';
    let autoIcon = '';

    // WENN CHECKLISTE VORHANDEN IST, REQUIREMENTS IGNORIEREN
    if (task.has_checklist) {
        tagsHtml = `<span class="inline-block bg-sky-100 text-sky-900 text-[10px] px-2 py-0.5 rounded border border-sky-300 mr-1 mt-1 font-bold shadow-sm">📋 Checkliste: ${task.checklist_done}/${task.checklist_total} erledigt</span>`;
        autoIcon = `<span class="text-[9px] bg-sky-50 border border-sky-300 text-sky-700 px-1 rounded font-bold mr-1">LIST</span>`;
    } else {
        // Sonst Requirements zeigen
        try {
            const reqs = JSON.parse(task.linked_reqs || '[]');
            reqs.forEach(r => {
                tagsHtml += `<span class="inline-block bg-indigo-100 text-indigo-900 text-[10px] px-1.5 py-0.5 rounded border border-indigo-300 mr-1 mt-1 font-mono font-bold">${r}</span>`;
            });
        } catch (e) { }
        if (task.is_auto_progress) {
            autoIcon = `<span class="text-[9px] bg-indigo-50 border border-indigo-300 text-indigo-700 px-1 rounded font-bold mr-1">AUTO</span>`;
        }
    }

    const progress = task.progress_pct || 0;
    const barColor = progress === 100 ? 'bg-emerald-500' : 'bg-blue-500';
    const effortVal = task.effort_mt ? task.effort_mt + ' h' : '-';

    // Keine Einrückung mehr nötig, da Unterpunkte komplett versteckt sind!
    const indentClass = 'font-bold bg-white hover:bg-blue-50/50 border-t border-slate-200 transition-colors';

    const iconEye = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>`;
    const iconEdit = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>`;
    const iconTrash = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;

    tbody.innerHTML += `
        <tr class="${indentClass}">
            <td class="p-4 font-mono text-sm font-extrabold text-slate-800 border-r border-slate-100">
                ${task.wbs_code || ''}
            </td>
            <td class="p-4 border-r border-slate-100">
                <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-0.5">${task.category || 'Allgemein'}</div>
                <div class="text-blue-950 text-base font-extrabold">${task.title}</div>
                <div>${tagsHtml}</div>
            </td>
            <td class="p-4 text-xs font-semibold text-slate-700 border-r border-slate-100">${task.assignee || '-'}</td>
            <td class="p-4 text-center text-sm font-mono font-semibold text-slate-800 border-r border-slate-100">${effortVal}</td>
            <td class="p-4 text-center text-xs whitespace-nowrap font-medium text-slate-600 border-r border-slate-100">${sDate} <br> ${eDate}</td>
            <td class="p-4 border-r border-slate-100">
                <div class="flex justify-between items-end mb-1 text-xs">
                    <div>${autoIcon}</div>
                    <span class="font-extrabold text-slate-800">${progress}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 border border-slate-300">
                    <div class="${barColor} h-2.5 rounded-full transition-all duration-500" style="width: ${progress}%"></div>
                </div>
            </td>
            <td class="p-4 text-right">
                <div class="flex justify-end gap-2">
                    <button onclick="window.viewTaskAnalytics(${task.id})" class="text-blue-600 hover:text-white hover:bg-blue-600 transition p-2 bg-blue-50 border border-blue-200 rounded shadow-sm" title="Analyse & Checkliste">${iconEye}</button>
                    <button onclick="window.editTask(${task.id})" class="text-slate-500 hover:text-blue-600 transition p-2 bg-slate-50 border border-slate-200 rounded shadow-sm" title="Bearbeiten">${iconEdit}</button>
                    <button onclick="window.deleteTask(${task.id})" class="text-slate-500 hover:text-red-600 transition p-2 bg-slate-50 border border-slate-200 rounded shadow-sm" title="Löschen">${iconTrash}</button>
                </div>
            </td>
        </tr>
    `;
}

function generateAutoId() {
    const mainTasks = loadedTasks.filter(t => !t.parent_id);
    document.getElementById('task_wbs').value = String(mainTasks.length + 1);
}

async function fetchRequirementsForMenu() {
    if (!currentProjectId) return;
    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        if (data.success) {
            allRequirements = data.requirements;
            renderReqMenu();
        }
    } catch (e) { console.error(e); }
}

function renderReqMenu() {
    const container = document.getElementById('reqMenuContainer');
    if (!container) return;

    const grouped = {};
    allRequirements.forEach(req => {
        if (!grouped[req.type]) grouped[req.type] = [];
        grouped[req.type].push(req);
    });

    let html = `
    <div class="relative w-full">
        <button type="button" id="btnReqDropdown" class="w-full text-left px-4 py-3 bg-slate-100 hover:bg-slate-200 font-bold text-sm flex justify-between items-center transition border border-slate-300 rounded shadow-sm">
            <span class="text-blue-900">+ Anforderung aus Liste hinzufügen</span>
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
        
        <!-- Das Menü (Standardmäßig nach unten, JS flippt es bei Bedarf) -->
        <div id="reqDropdownMenu" class="absolute left-0 mt-1 w-64 bg-white border border-slate-300 shadow-2xl rounded hidden z-[160]">
    `;

    for (const type in grouped) {
        html += `
            <div class="relative group/sub">
                <div class="px-4 py-3 hover:bg-blue-50 cursor-default flex justify-between items-center text-sm font-bold text-slate-800 border-b border-slate-100 last:border-0">
                    Gruppe: ${type}
                    <span class="text-slate-400 text-xs">▶</span>
                </div>
                <!-- Flyout Level 3 -->
                <div class="absolute left-full top-0 hidden group-hover/sub:block bg-white shadow-2xl border border-slate-300 rounded w-[450px] max-h-[300px] overflow-y-auto z-[170]">
        `;
        grouped[type].forEach(req => {
            html += `
                    <label class="flex items-start gap-3 p-3 hover:bg-blue-100 cursor-pointer transition border-b border-slate-100 last:border-0">
                        <input type="checkbox" value="${req.req_key}" class="req-picker-cb mt-0.5 w-4 h-4 text-blue-700 font-bold shadow-sm rounded">
                        <div class="flex flex-col">
                            <span class="text-xs font-extrabold text-slate-900 font-mono">${req.req_key}</span>
                            <span class="text-xs font-medium text-slate-700 leading-tight">${req.title}</span>
                        </div>
                    </label>
            `;
        });
        html += `</div></div>`;
    }

    html += `</div></div>`;
    container.innerHTML = html;

    // KOLLISIONSSCHUTZ: Prüfen, ob das Dropdown nach unten genug Platz hat
    const btn = document.getElementById('btnReqDropdown');
    const menu = document.getElementById('reqDropdownMenu');

    if (btn && menu) {
        btn.addEventListener('click', () => {
            // Toggle Sichtbarkeit
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');

                // Messen!
                const rect = btn.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;

                // Wenn weniger als 250px Platz nach unten sind, öffne es nach Oben!
                if (spaceBelow < 250) {
                    menu.classList.remove('mt-1', 'top-full');
                    menu.classList.add('mb-1', 'bottom-full');
                } else {
                    menu.classList.remove('mb-1', 'bottom-full');
                    menu.classList.add('mt-1', 'top-full');
                }
            } else {
                menu.classList.add('hidden');
            }
        });
    }

    document.querySelectorAll('.req-picker-cb').forEach(cb => {
        cb.addEventListener('change', updateSelectedReqsTags);
    });
}

function updateSelectedReqsTags() {
    const checked = Array.from(document.querySelectorAll('.req-picker-cb:checked')).map(cb => cb.value);
    document.getElementById('task_linked_reqs').value = checked.join(',');

    const container = document.getElementById('task_selected_reqs_container');
    if (checked.length === 0) {
        container.innerHTML = '<span class="text-slate-500 text-sm font-medium italic mt-1">Keine ausgewählt...</span>';
        return;
    }

    container.innerHTML = checked.map(key => `
        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-950 px-2.5 py-1 rounded text-xs font-extrabold font-mono border border-indigo-300 shadow-sm">
            ${key}
            <button type="button" onclick="window.removeReqTag('${key}')" class="text-indigo-500 hover:text-red-600 transition-colors ml-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </span>
    `).join('');
}

window.removeReqTag = function (key) {
    const cb = document.querySelector(`.req-picker-cb[value="${key}"]`);
    if (cb) {
        cb.checked = false;
        updateSelectedReqsTags();
    }
};

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
            autoToggle.dispatchEvent(new Event('change'));

            if (tplCat) tplCat.value = '';
            if (tplItem) {
                tplItem.innerHTML = '<option value="">-- Zuerst Hauptgruppe wählen --</option>';
                tplItem.disabled = true;
                tplItem.classList.add('bg-slate-100', 'cursor-not-allowed');
                tplItem.classList.remove('bg-white');
            }

            document.querySelectorAll('.req-picker-cb').forEach(cb => cb.checked = false);
            updateSelectedReqsTags();
            generateAutoId();

            // Menü sicher verstecken beim Öffnen des Modals
            const menu = document.getElementById('reqDropdownMenu');
            if (menu) menu.classList.add('hidden');

            document.getElementById('modalTask').classList.remove('hidden');
        });
    }

    if (tplCat && tplItem) {
        tplCat.addEventListener('change', (e) => {
            const cat = e.target.value;
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
            document.getElementById('container_manual_progress').style.display = isAuto ? 'none' : 'block';
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: document.getElementById('task_id').value,
                project_id: currentProjectId,
                parent_id: null, // Da wir das Feld entfernt haben, senden wir standardmäßig null (Backend fängt "--" ab)
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
                linked_reqs: document.getElementById('task_linked_reqs').value,
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

    const tplCat = document.getElementById('tpl_category');
    const tplItem = document.getElementById('tpl_item');
    if (tplCat) tplCat.value = '';
    if (tplItem) {
        tplItem.innerHTML = '<option value="">-- Zuerst Hauptgruppe wählen --</option>';
        tplItem.disabled = true;
        tplItem.classList.add('bg-slate-100', 'cursor-not-allowed');
        tplItem.classList.remove('bg-white');
    }

    document.querySelectorAll('.req-picker-cb').forEach(cb => cb.checked = false);
    try {
        const reqs = JSON.parse(task.linked_reqs || '[]');
        reqs.forEach(reqKey => {
            const cb = document.querySelector(`.req-picker-cb[value="${reqKey}"]`);
            if (cb) cb.checked = true;
        });
    } catch (e) { }

    updateSelectedReqsTags();

    const menu = document.getElementById('reqDropdownMenu');
    if (menu) menu.classList.add('hidden');

    document.getElementById('task_is_auto').dispatchEvent(new Event('change'));
    document.getElementById('taskModalTitle').textContent = 'Aufgabe bearbeiten (ID: ' + (task.wbs_code || 'Neu') + ')';
    document.getElementById('modalTask').classList.remove('hidden');
};

// --- NEUE / ANGEPASSTE ANALYTICS & CHECKLISTEN LOGIK ---

window.viewTaskAnalytics = async function (id) {
    document.getElementById('analyticsPanelOverlay').classList.remove('hidden');
    setTimeout(() => { document.getElementById('analyticsPanel').classList.remove('translate-x-full'); }, 10);

    document.getElementById('analyticsTitle').textContent = "Lade Analyse...";
    document.getElementById('analyticsContributors').innerHTML = '<div class="text-xs text-slate-500 animate-pulse">Lade...</div>';
    document.getElementById('analyticsReqList').innerHTML = '';
    document.getElementById('analyticsChecklistContainer').classList.add('hidden');
    document.getElementById('analyticsProgressBar').style.width = '0%';

    try {
        const res = await fetch(`../api/get_task_analytics.php?task_id=${id}&project_id=${currentProjectId}`);
        const data = await res.json();
        if (!data.success) return;

        const a = data.analytics;
        document.getElementById('analyticsTitle').textContent = (a.wbs_code ? a.wbs_code + ' - ' : '') + a.task_title;

        // FORTSCHRITT: Checkliste überschreibt Requirements
        const pct = a.has_checklist ? a.checklist_progress : (a.total_reqs > 0 ? Math.round((a.approved_reqs / a.total_reqs) * 100) : 0);
        const countTxt = a.has_checklist
            ? `${a.subtasks.filter(s => s.progress_pct == 100).length} von ${a.subtasks.length} Checklisten-Punkten erledigt`
            : `${a.approved_reqs} von ${a.total_reqs} Anforderungen geprüft`;

        document.getElementById('analyticsReqCount').textContent = countTxt;
        document.getElementById('analyticsTotalProgress').textContent = `${pct}%`;
        document.getElementById('analyticsProgressBar').style.width = `${pct}%`;

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

        // Contributors und Requirements rendern... (Hier bleibt dein alter Code aus der vorherigen Nachricht)
        const contDiv = document.getElementById('analyticsContributors');
        if (Object.keys(a.contributors).length === 0) {
            contDiv.innerHTML = '<div class="text-xs font-semibold text-slate-400 italic bg-slate-100 p-3 rounded">Niemand bisher.</div>';
        } else {
            let html = '';
            for (const [user, count] of Object.entries(a.contributors)) {
                const userPct = Math.round((count / a.total_reqs) * 100);
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

    } catch (e) { console.error(e); }
};

window.closeAnalyticsPanel = function () {
    document.getElementById('analyticsPanel').classList.add('translate-x-full');
    setTimeout(() => { document.getElementById('analyticsPanelOverlay').classList.add('hidden'); }, 300);
};

// CHECKLISTEN-PUNKT ABHAKEN
window.toggleSubtask = async function (subtaskId, isChecked, parentTaskId) {
    try {
        await fetch('../api/toggle_subtask.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: subtaskId, completed: isChecked })
        });
        // Im Hintergrund die Haupttabelle updaten
        await loadProjectPlan();
        // Das Analyse-Fenster ebenfalls frisch laden, damit der Fortschrittsbalken springt!
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
        if (data.success) { loadProjectPlan(); } else { alert("Fehler: " + data.error); }
    } catch (e) { console.error(e); }
};


// Löschen-Funktion
window.deleteTask = async function (id) {
    if (!confirm("Möchtest du diese Aufgabe wirklich löschen?")) return;

    try {
        const res = await fetch('../api/delete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, project_id: currentProjectId })
        });
        const data = await res.json();
        if (data.success) {
            loadProjectPlan();
        } else {
            alert("Fehler beim Löschen: " + data.error);
        }
    } catch (e) {
        console.error(e);
    }
};

// Dummy-Funktion für das Auge
window.viewTaskAnalytics = function (id) {
    alert("Das Analytics-Modul wird bald hinzugefügt! (ID: " + id + ")");
};


// ANALYTICS PANEL ÖFFNEN
window.viewTaskAnalytics = async function (id) {
    // Panel rein sliden
    document.getElementById('analyticsPanelOverlay').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('analyticsPanel').classList.remove('translate-x-full');
    }, 10);

    document.getElementById('analyticsTitle').textContent = "Lade Analyse...";
    document.getElementById('analyticsContributors').innerHTML = '<div class="text-xs text-slate-500 animate-pulse">Lade Beiträge...</div>';
    document.getElementById('analyticsReqList').innerHTML = '';
    document.getElementById('analyticsProgressBar').style.width = '0%';

    try {
        const res = await fetch(`../api/get_task_analytics.php?task_id=${id}&project_id=${currentProjectId}`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById('analyticsTitle').textContent = "Fehler beim Laden";
            return;
        }

        const a = data.analytics;
        document.getElementById('analyticsTitle').textContent = (a.wbs_code ? a.wbs_code + ' - ' : '') + a.task_title;

        // Gesamtfortschritt rendern
        const pct = a.total_reqs > 0 ? Math.round((a.approved_reqs / a.total_reqs) * 100) : 0;
        document.getElementById('analyticsReqCount').textContent = `${a.approved_reqs} von ${a.total_reqs} Anforderungen geprüft`;
        document.getElementById('analyticsTotalProgress').textContent = `${pct}%`;
        document.getElementById('analyticsProgressBar').style.width = `${pct}%`;

        // Contributors (Wer hat wie viel erledigt?)
        const contDiv = document.getElementById('analyticsContributors');
        if (Object.keys(a.contributors).length === 0) {
            contDiv.innerHTML = '<div class="text-xs font-semibold text-slate-400 italic bg-slate-100 p-3 rounded">Noch keine Anforderungen freigegeben.</div>';
        } else {
            let html = '';
            for (const [user, count] of Object.entries(a.contributors)) {
                const userPct = Math.round((count / a.total_reqs) * 100);
                html += `
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-slate-800 font-mono"><span class="text-blue-600">@</span>${user}</span>
                            <span class="text-xs font-extrabold text-blue-900">${userPct}% (${count} erledigt)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: ${userPct}%"></div>
                        </div>
                    </div>
                `;
            }
            contDiv.innerHTML = html;
        }

        // Detailliste der Requirements
        const reqDiv = document.getElementById('analyticsReqList');
        if (a.req_details.length === 0) {
            reqDiv.innerHTML = '<div class="text-xs text-slate-500 italic">Keine Anforderungen verknüpft.</div>';
        } else {
            let reqHtml = '';
            a.req_details.forEach(r => {
                const isAppr = r.status === 'Geprüft & Freigegeben';
                const statusColor = isAppr ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-slate-500 bg-slate-50 border-slate-200';
                const icon = isAppr
                    ? '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>'
                    : '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

                reqHtml += `
                    <div class="border ${statusColor} rounded p-3 text-xs mb-2">
                        <div class="flex justify-between font-bold mb-1">
                            <span class="font-mono">${r.req_key}</span>
                            <span class="flex items-center gap-1">${icon} ${r.status}</span>
                        </div>
                        <div class="font-medium text-slate-700 truncate mb-2">${r.title}</div>
                        ${isAppr ? `<div class="text-[10px] text-slate-500 border-t border-emerald-200/50 pt-1 mt-1 font-mono">Freigegeben von: ${r.approved_by} @ ${r.hostname}</div>` : ''}
                    </div>
                `;
            });
            reqDiv.innerHTML = reqHtml;
        }

    } catch (e) {
        console.error(e);
        document.getElementById('analyticsTitle').textContent = "Netzwerkfehler";
    }
};

// ANALYTICS PANEL SCHLIESSEN
window.closeAnalyticsPanel = function () {
    document.getElementById('analyticsPanel').classList.add('translate-x-full');
    setTimeout(() => {
        document.getElementById('analyticsPanelOverlay').classList.add('hidden');
    }, 300); // Warten bis die CSS-Animation fertig ist
};