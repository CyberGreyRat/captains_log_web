import {
    currentProjectId,
    currentRequirements
} from './state.js';

const element = id => document.getElementById(id);

function currentRequirement() {
    const editId = Number(
        element('reqForm')?.dataset.editId || 0
    );

    return currentRequirements.find(
        requirement => Number(requirement.id) === editId
    ) || null;
}

function formatKey(type, serialNumber) {
    return `${type}-${String(serialNumber).padStart(3, '0')}`;
}

function updateHeading() {
    const form = element('reqForm');
    const heading = element('reqHeading');
    const type = element('type')?.value || '';

    if (!form || !heading) {
        return;
    }

    const requirement = currentRequirement();

    if (form.dataset.editId && requirement) {
        heading.textContent =
            `Eintrag bearbeiten (${formatKey(
                type,
                requirement.serial_number
            )})`;
    } else {
        heading.textContent = type
            ? `Neues Element (${type})`
            : 'Neues Element anlegen';
    }
}

function ensureSourceFields() {
    const titleInput = element('title');

    if (!titleInput || element('requirementSourceFields')) {
        return;
    }

    const titleContainer = titleInput.closest('label, div');

    if (!titleContainer) {
        return;
    }

    titleContainer.insertAdjacentHTML(
        'afterend',
        `<div id="requirementSourceFields" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <label class="cl-label">
                Quellenreferenz
                <input id="source_reference" class="cl-input" placeholder="z.B. 11.004 oder LH-27">
            </label>
            <label class="cl-label">
                Quelldokument
                <input id="source_document" class="cl-input" placeholder="z.B. Lastenheft.pdf">
            </label>
            <label class="cl-label">
                Quellseite
                <input id="source_page" type="number" min="1" class="cl-input" placeholder="z.B. 14">
            </label>
        </div>`
    );
}

function fillSourceFields() {
    ensureSourceFields();

    const requirement = currentRequirement();

    if (!requirement) {
        if (element('source_reference')) {
            element('source_reference').value = '';
            element('source_document').value = '';
            element('source_page').value = '';
        }
        return;
    }

    element('source_reference').value =
        requirement.source_reference || '';
    element('source_document').value =
        requirement.source_document || '';
    element('source_page').value =
        requirement.source_page || '';
}

function refreshCriteriaForType() {
    const modal = element('acceptanceCriteriaModal');

    if (modal && !modal.classList.contains('hidden')) {
        /* Schließen und mit dem aktuellen Typ neu öffnen. */
        modal.classList.add('hidden');
        element('btnSuggestAcceptanceCriteria')?.click();
    }
}

function activateTypeFilter(type) {
    const checkboxes = [
        ...document.querySelectorAll(
            '#reqFilterCheckboxes input[type="checkbox"]'
        )
    ];

    const matchingCheckbox = checkboxes.find(
        checkbox => checkbox.value === type
    );

    if (matchingCheckbox) {
        matchingCheckbox.checked = true;
    }
}

function patchPayload() {
    const form = element('reqForm');

    if (!form || form.dataset.sourceUiPayloadBound === '1') {
        return;
    }

    form.dataset.sourceUiPayloadBound = '1';

    form.addEventListener(
        'submit',
        event => {
            const sourceReference =
                element('source_reference')?.value || '';
            const sourceDocument =
                element('source_document')?.value || '';
            const sourcePage =
                element('source_page')?.value || '';
            const type = element('type')?.value || '';

            /*
             * requirements.js baut den Payload erst später im selben Event.
             * Die Werte werden deshalb zusätzlich als Dataset bereitgestellt.
             */
            form.dataset.sourceReference = sourceReference;
            form.dataset.sourceDocument = sourceDocument;
            form.dataset.sourcePage = sourcePage;

            activateTypeFilter(type);
        },
        true
    );
}

function patchFetchForRequirementSource() {
    if (window.__captainsRequirementFetchPatched) {
        return;
    }

    window.__captainsRequirementFetchPatched = true;
    const originalFetch = window.fetch.bind(window);

    window.fetch = async function patchedFetch(resource, options = {}) {
        const url = typeof resource === 'string'
            ? resource
            : resource?.url || '';

        if (
            url.includes('../api/set_requirements.php') &&
            options.body &&
            typeof options.body === 'string'
        ) {
            try {
                const payload = JSON.parse(options.body);
                const form = element('reqForm');

                payload.source_reference =
                    element('source_reference')?.value ||
                    form?.dataset.sourceReference ||
                    '';
                payload.source_document =
                    element('source_document')?.value ||
                    form?.dataset.sourceDocument ||
                    '';
                payload.source_page =
                    element('source_page')?.value ||
                    form?.dataset.sourcePage ||
                    '';

                const current = currentRequirement();

                if (current) {
                    payload.parent_ids = current.parent_ids || [];
                    payload.child_ids = current.child_ids || [];
                }

                options = {
                    ...options,
                    body: JSON.stringify(payload)
                };
            } catch (error) {
                console.error('Requirement payload patch:', error);
            }
        }

        return originalFetch(resource, options);
    };
}

function renderSourceInDetail() {
    const detail = element('detail');

    if (!detail) {
        return;
    }

    const headingText = detail.textContent || '';
    const requirement = currentRequirements.find(item =>
        headingText.includes(item.req_key)
    );

    detail.querySelector('#requirementSourceInformation')?.remove();

    if (
        !requirement ||
        !(
            requirement.source_reference ||
            requirement.source_document ||
            requirement.source_page
        )
    ) {
        return;
    }

    const panel = document.createElement('section');
    panel.id = 'requirementSourceInformation';
    panel.className =
        'mb-5 rounded-md border border-blue-200 bg-blue-50 p-4';
    panel.innerHTML = `
        <p class="cl-panel-eyebrow">Quellennachweis</p>
        <div class="mt-2 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
            <div><span class="block text-xs font-bold text-slate-500">Referenz</span><strong class="text-blue-950">${requirement.source_reference || '-'}</strong></div>
            <div><span class="block text-xs font-bold text-slate-500">Dokument</span><strong class="text-blue-950">${requirement.source_document || '-'}</strong></div>
            <div><span class="block text-xs font-bold text-slate-500">Seite</span><strong class="text-blue-950">${requirement.source_page || '-'}</strong></div>
        </div>`;

    detail.prepend(panel);
}

function bindEvents() {
    ensureSourceFields();
    patchPayload();
    patchFetchForRequirementSource();

    element('type')?.addEventListener('change', event => {
        updateHeading();
        refreshCriteriaForType();
        activateTypeFilter(event.currentTarget.value);
    });

    element('new')?.addEventListener('click', () => {
        setTimeout(() => {
            updateHeading();
            fillSourceFields();
        }, 0);
    });

    document.addEventListener('click', event => {
        if (
            event.target.closest('[onclick*="editRequirement"]') ||
            event.target.closest('#items button') ||
            event.target.closest('#items [data-id]')
        ) {
            setTimeout(() => {
                updateHeading();
                fillSourceFields();
                renderSourceInDetail();
            }, 50);
        }
    });

    const detail = element('detail');

    if (detail) {
        let scheduled = false;
        const observer = new MutationObserver(() => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(() => {
                scheduled = false;
                renderSourceInDetail();
            });
        });

        observer.observe(detail, {
            childList: true,
            subtree: true
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindEvents);
} else {
    bindEvents();
}
