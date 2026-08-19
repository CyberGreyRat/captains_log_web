import { currentProjectId } from './state.js';

let team = [];
let availableUsers = [];
let roles = [];

const esc = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[character]));

export async function loadProjectTeam() {
    const body = document.getElementById('projectTeamTableBody');
    if (!body) return;

    if (!currentProjectId) {
        team = [];
        body.innerHTML = '<tr><td colspan="7" class="p-8 text-center italic text-slate-400">Bitte zuerst ein Projekt auswählen.</td></tr>';
        updateKpis();
        return;
    }

    body.innerHTML = '<tr><td colspan="7" class="p-8 text-center italic text-slate-500">Projektteam wird geladen...</td></tr>';

    try {
        const response = await fetch(`../api/get_project_team.php?project_id=${encodeURIComponent(currentProjectId)}`);
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Projektteam konnte nicht geladen werden.');

        team = data.team || [];
        availableUsers = data.available_users || [];
        roles = data.roles || [];
        fillRoleControls();
        renderProjectTeam();
    } catch (error) {
        body.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-red-600">${esc(error.message)}</td></tr>`;
    }
}

function fillRoleControls() {
    const datalist = document.getElementById('projectRoleOptions');
    const filter = document.getElementById('projectTeamRoleFilter');
    if (datalist) datalist.innerHTML = roles.map(role => `<option value="${esc(role.role_name)}">${esc(role.description || '')}</option>`).join('');
    if (filter) {
        const selected = filter.value;
        const names = [...new Set(team.map(member => member.project_role).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'de'));
        filter.innerHTML = '<option value="">Alle Projektrollen</option>' + names.map(name => `<option value="${esc(name)}">${esc(name)}</option>`).join('');
        if (names.includes(selected)) filter.value = selected;
    }
}

function renderProjectTeam() {
    const body = document.getElementById('projectTeamTableBody');
    const query = String(document.getElementById('projectTeamSearch')?.value || '').trim().toLowerCase();
    const roleFilter = document.getElementById('projectTeamRoleFilter')?.value || '';

    const filtered = team.filter(member => {
        const matchesRole = !roleFilter || member.project_role === roleFilter;
        const text = [member.username, member.project_role, member.expertise, member.availability, member.system_role].filter(Boolean).join(' ').toLowerCase();
        return matchesRole && (!query || text.includes(query));
    });

    if (!filtered.length) {
        body.innerHTML = '<tr><td colspan="7" class="p-8 text-center italic text-slate-400">Keine Projektmitglieder gefunden.</td></tr>';
    } else {
        body.innerHTML = filtered.map(member => {
            const active = Number(member.is_active) === 1;
            return `<tr class="border-b border-slate-200 ${active ? 'bg-white hover:bg-blue-50/40' : 'bg-slate-100 text-slate-400'}">
                <td class="p-3 align-top"><div class="font-bold text-slate-900">${esc(member.username)}</div><div class="mt-1 text-[10px] uppercase text-slate-400">User-ID ${Number(member.user_id)}</div></td>
                <td class="p-3 align-top"><span class="inline-flex border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-bold text-blue-900">${esc(member.project_role || '-')}</span></td>
                <td class="p-3 align-top">${esc(member.expertise || '-')}</td>
                <td class="p-3 align-top">${esc(member.availability || '-')}</td>
                <td class="p-3 align-top"><span class="text-xs font-bold uppercase text-slate-600">${esc(member.system_role)}</span></td>
                <td class="p-3 align-top"><span class="inline-flex border px-2 py-1 text-xs font-bold ${active ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-slate-300 bg-slate-200 text-slate-600'}">${active ? 'Aktiv' : 'Inaktiv'}</span></td>
                <td class="p-3 align-top text-right whitespace-nowrap"><button type="button" onclick="window.editProjectMember(${Number(member.user_id)})" class="font-bold text-blue-700 hover:text-blue-950">Bearbeiten</button><button type="button" onclick="window.removeProjectMember(${Number(member.user_id)})" class="ml-3 font-bold text-red-600 hover:text-red-800">Entfernen</button></td>
            </tr>`;
        }).join('');
    }

    document.getElementById('projectTeamResultCount').textContent = `${filtered.length} von ${team.length} Mitgliedern`;
    updateKpis();
}

function updateKpis() {
    const total = team.length;
    const active = team.filter(member => Number(member.is_active) === 1).length;
    if (document.getElementById('teamMemberCount')) document.getElementById('teamMemberCount').textContent = total;
    if (document.getElementById('teamActiveCount')) document.getElementById('teamActiveCount').textContent = active;
}

function openMemberModal(member = null) {
    const form = document.getElementById('projectMemberForm');
    form.reset();
    document.getElementById('project_member_original_user_id').value = member?.user_id || '';
    const userSelect = document.getElementById('project_member_user_id');

    if (member) {
        userSelect.innerHTML = `<option value="${Number(member.user_id)}">${esc(member.username)}</option>`;
        userSelect.value = member.user_id;
        userSelect.disabled = true;
    } else {
        userSelect.disabled = false;
        userSelect.innerHTML = '<option value="">-- Nutzer auswählen --</option>' + availableUsers.map(user => `<option value="${Number(user.id)}">${esc(user.username)} (${esc(user.role)})</option>`).join('');
    }

    document.getElementById('project_member_role').value = member?.project_role || '';
    document.getElementById('project_member_expertise').value = member?.expertise || '';
    document.getElementById('project_member_availability').value = member?.availability || '';
    document.getElementById('project_member_active').checked = member ? Number(member.is_active) === 1 : true;
    document.getElementById('projectMemberModalTitle').textContent = member ? `${member.username} bearbeiten` : 'Projektmitglied zuweisen';
    document.getElementById('projectMemberModal').classList.remove('hidden');
}

function closeMemberModal() {
    document.getElementById('projectMemberModal')?.classList.add('hidden');
}

export function initProjectTeamEvents() {
    document.getElementById('btnNewProjectMember')?.addEventListener('click', () => {
        if (!currentProjectId) return alert('Bitte zuerst ein Projekt auswählen.');
        if (!availableUsers.length) return alert('Alle vorhandenen Nutzer sind diesem Projekt bereits zugewiesen.');
        openMemberModal();
    });
    document.getElementById('projectTeamSearch')?.addEventListener('input', renderProjectTeam);
    document.getElementById('projectTeamRoleFilter')?.addEventListener('change', renderProjectTeam);
    document.getElementById('projectMemberCancel')?.addEventListener('click', closeMemberModal);
    document.getElementById('projectMemberModalClose')?.addEventListener('click', closeMemberModal);
    document.getElementById('projectMemberForm')?.addEventListener('submit', saveProjectMember);
}

async function saveProjectMember(event) {
    event.preventDefault();
    const originalUserId = document.getElementById('project_member_original_user_id').value;
    const payload = {
        project_id: currentProjectId,
        user_id: originalUserId || document.getElementById('project_member_user_id').value,
        project_role: document.getElementById('project_member_role').value.trim(),
        expertise: document.getElementById('project_member_expertise').value.trim(),
        availability: document.getElementById('project_member_availability').value.trim(),
        is_active: document.getElementById('project_member_active').checked
    };

    try {
        const response = await fetch('../api/set_project_member.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Projektmitglied konnte nicht gespeichert werden.');
        closeMemberModal();
        await loadProjectTeam();
    } catch (error) {
        alert(`Fehler: ${error.message}`);
    }
}

window.editProjectMember = userId => {
    const member = team.find(item => Number(item.user_id) === Number(userId));
    if (member) openMemberModal(member);
};

window.removeProjectMember = async userId => {
    const member = team.find(item => Number(item.user_id) === Number(userId));
    if (!member || !confirm(`${member.username} wirklich aus dem Projektteam entfernen?`)) return;
    try {
        const response = await fetch('../api/remove_project_member.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: currentProjectId, user_id: userId }) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Mitglied konnte nicht entfernt werden.');
        await loadProjectTeam();
    } catch (error) {
        alert(`Fehler: ${error.message}`);
    }
};
