import { currentProjectId } from './state.js';

let goals = [];
let stakeholders = [];

const esc = value =>
    String(value ?? '').replace(/[&<>'"]/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[character]));

export async function loadGoals() {
    const container = document.getElementById('goalCardContainer');

    if (!container) {
        return;
    }

    if (!currentProjectId) {
        goals = [];

        container.innerHTML = `
            <div class="col-span-full border border-slate-300 bg-white p-8 text-center italic text-slate-400">
                Bitte zuerst ein Projekt auswählen.
            </div>
        `;

        renderGoalCount(0);
        return;
    }

    container.innerHTML = `
        <div class="col-span-full border border-slate-300 bg-white p-8 text-center italic text-slate-500">
            Ziele werden geladen...
        </div>
    `;

    try {
        const [requirementResponse, stakeholderResponse] =
            await Promise.all([
                fetch(
                    `../api/get_requirements.php?project_id=${encodeURIComponent(currentProjectId)}`
                ),
                fetch(
                    `../api/get_stakeholders.php?project_id=${encodeURIComponent(currentProjectId)}`
                )
            ]);

        const requirementData =
            await requirementResponse.json();

        const stakeholderData =
            await stakeholderResponse.json();

        if (!requirementData.success) {
            throw new Error(
                requirementData.error ||
                'Ziele konnten nicht geladen werden.'
            );
        }

        goals = (requirementData.requirements || [])
            .filter(item => item.type === 'GOAL')
            .sort((a, b) =>
                String(a.req_key).localeCompare(
                    String(b.req_key),
                    'de',
                    { numeric: true }
                )
            );

        stakeholders =
            stakeholderData.success
                ? stakeholderData.stakeholders || []
                : [];

        fillGoalStakeholders();
        renderGoals();
    } catch (error) {
        console.error('Fehler beim Laden der Ziele:', error);

        container.innerHTML = `
            <div class="col-span-full border border-red-300 bg-red-50 p-8 text-center text-red-700">
                ${esc(error.message)}
            </div>
        `;
    }
}

function fillGoalStakeholders() {
    const select = document.getElementById('goal_source_contact');

    if (!select) {
        return;
    }

    select.innerHTML = `
        <option value="">-- Niemand zugewiesen --</option>
        ${stakeholders.map(stakeholder => `
            <option value="${Number(stakeholder.id)}">
                ${esc(stakeholder.name)}
            </option>
        `).join('')}
    `;
}

function getStakeholderName(id) {
    if (!id) {
        return '-';
    }

    const stakeholder = stakeholders.find(
        item => Number(item.id) === Number(id)
    );

    return stakeholder?.name || String(id);
}

function renderGoals() {
    const container =
        document.getElementById('goalCardContainer');

    const query = String(
        document.getElementById('goalSearch')?.value || ''
    ).trim().toLowerCase();

    const selectedStatus =
        document.getElementById('goalStatusFilter')?.value || '';

    const filteredGoals = goals.filter(goal => {
        const statusMatches =
            !selectedStatus ||
            goal.review_status === selectedStatus;

        const searchableText = [
            goal.req_key,
            goal.title,
            goal.description,
            goal.rationale,
            goal.acceptance_criteria,
            getStakeholderName(goal.source_contact)
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return (
            statusMatches &&
            (!query || searchableText.includes(query))
        );
    });

    if (!filteredGoals.length) {
        container.innerHTML = `
            <div class="col-span-full border border-slate-300 bg-white p-8 text-center italic text-slate-400">
                Keine Ziele gefunden.
            </div>
        `;

        renderGoalCount(0);
        return;
    }

    container.innerHTML = filteredGoals.map(goal => {
        const approved =
            goal.review_status === 'Geprüft & Freigegeben';

        const rejected =
            goal.review_status === 'Abgelehnt';

        const cardClasses = approved
            ? 'border-emerald-300 border-l-4 border-l-emerald-500 bg-emerald-50/40'
            : rejected
                ? 'border-red-300 border-l-4 border-l-red-500 bg-red-50/40'
                : 'border-slate-300 border-l-4 border-l-blue-950 bg-white';

        const statusClasses = approved
            ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
            : rejected
                ? 'border-red-300 bg-red-100 text-red-700'
                : 'border-slate-300 bg-slate-100 text-slate-700';

        return `
            <article class="border p-5 shadow-sm ${cardClasses}">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-mono text-xs font-extrabold text-blue-950">
                            ${esc(goal.req_key)}
                        </div>

                        <h3 class="mt-1 text-lg font-extrabold text-slate-900">
                            ${esc(goal.title)}
                        </h3>
                    </div>

                    <span class="inline-flex whitespace-nowrap border px-2 py-1 text-xs font-bold ${statusClasses}">
                        ${esc(goal.review_status || 'Neu')}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">
                    ${esc(goal.description || 'Keine Beschreibung vorhanden.')}
                </p>

                ${
                    goal.rationale
                        ? `
                            <div class="mt-4 border-l-2 border-amber-400 bg-amber-50 p-3 text-sm text-slate-700">
                                <strong>Nutzen:</strong>
                                ${esc(goal.rationale)}
                            </div>
                        `
                        : ''
                }

                <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-3 text-xs text-slate-500">

                    <span>
                        Zuständig:
                        <strong class="text-slate-700">
                            ${esc(getStakeholderName(goal.source_contact))}
                        </strong>
                    </span>

                    <button
                        type="button"
                        onclick="window.editGoal(${Number(goal.id)})"
                        class="font-bold text-blue-700 hover:text-blue-950">
                        Bearbeiten
                    </button>
                </div>
            </article>
        `;
    }).join('');

    renderGoalCount(filteredGoals.length);
}

function renderGoalCount(count) {
    const element = document.getElementById('goalResultCount');

    if (element) {
        element.textContent = `${count} von ${goals.length} Ziele`;
    }
}

function openGoalModal(goal = null) {
    const form = document.getElementById('goalForm');

    if (!form) {
        return;
    }

    form.reset();

    document.getElementById('goal_id').value =
        goal?.id || '';

    document.getElementById('goal_title').value =
        goal?.title || '';

    document.getElementById('goal_description').value =
        goal?.description || '';

    document.getElementById('goal_rationale').value =
        goal?.rationale || '';

    document.getElementById('goal_source_contact').value =
        goal?.source_contact || '';

    document.getElementById('goal_effort').value =
        goal?.effort || '';

    document.getElementById('goal_acceptance_criteria').value =
        goal?.acceptance_criteria || '';

    document.getElementById('goal_review_status').value =
        goal?.review_status || 'Neu';

    document.getElementById('goalModalTitle').textContent =
        goal
            ? `${goal.req_key} bearbeiten`
            : 'Neues Ziel';

    document.getElementById('goalModal').classList.remove('hidden');
}

function closeGoalModal() {
    document.getElementById('goalModal')?.classList.add('hidden');
}

export function initGoalEvents() {
    document.getElementById('btnNewGoal')?.addEventListener(
        'click',
        () => {
            if (!currentProjectId) {
                alert('Bitte zuerst ein Projekt wählen.');
                return;
            }

            openGoalModal();
        }
    );

    document.getElementById('goalSearch')?.addEventListener(
        'input',
        renderGoals
    );

    document.getElementById('goalStatusFilter')?.addEventListener(
        'change',
        renderGoals
    );

    document.getElementById('goalCancel')?.addEventListener(
        'click',
        closeGoalModal
    );

    document.getElementById('goalModalClose')?.addEventListener(
        'click',
        closeGoalModal
    );

    document.getElementById('goalForm')?.addEventListener(
        'submit',
        saveGoal
    );
}

async function saveGoal(event) {
    event.preventDefault();

    const payload = {
        id: document.getElementById('goal_id').value,
        project_id: currentProjectId,
        type: 'GOAL',
        title:
            document.getElementById('goal_title').value.trim(),
        description:
            document.getElementById('goal_description').value.trim(),
        rationale:
            document.getElementById('goal_rationale').value.trim(),
        source_contact:
            document.getElementById('goal_source_contact').value,
        effort:
            document.getElementById('goal_effort').value.trim(),
        acceptance_criteria:
            document
                .getElementById('goal_acceptance_criteria')
                .value
                .trim(),
        review_status:
            document.getElementById('goal_review_status').value,
        status: 'open',
        parents: [],
        children: [],
        attributes: {}
    };

    try {
        const response = await fetch('../api/set_requirements.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.error ||
                'Ziel konnte nicht gespeichert werden.'
            );
        }

        closeGoalModal();
        await loadGoals();
    } catch (error) {
        console.error(error);
        alert(`Fehler: ${error.message}`);
    }
}

window.editGoal = function (goalId) {
    const goal = goals.find(
        item => Number(item.id) === Number(goalId)
    );

    if (goal) {
        openGoalModal(goal);
    }
};