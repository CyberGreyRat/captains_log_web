import { currentProjectId } from './state.js';

let identityById = new Map();
let identityByLegacyKey = new Map();
let activeRequirementId = null;
let replacementScheduled = false;

function element(id) {
    return document.getElementById(id);
}

async function loadIdentityMap() {
    if (!currentProjectId) {
        identityById.clear();
        identityByLegacyKey.clear();
        return;
    }

    try {
        const response = await fetch(
            `../api/get_requirement_identity_map.php?project_id=${encodeURIComponent(currentProjectId)}`
        );
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Identitäten konnten nicht geladen werden.');
        }

        identityById = new Map(
            data.items.map(item => [String(item.id), item])
        );

        identityByLegacyKey = new Map(
            data.items.map(item => [String(item.req_key), item])
        );

        scheduleVisibleLabelReplacement();
    } catch (error) {
        console.error('Requirement identity:', error);
    }
}

function currentIdentity() {
    const form = element('reqForm');
    const editId = form?.dataset.editId || activeRequirementId;

    if (editId && identityById.has(String(editId))) {
        return identityById.get(String(editId));
    }

    const legacyKey = form?.dataset.reqKey || '';

    if (legacyKey && identityByLegacyKey.has(legacyKey)) {
        return identityByLegacyKey.get(legacyKey);
    }

    return null;
}

function updateModalHeading() {
    const heading = element('reqHeading');
    const form = element('reqForm');
    const type = element('type')?.value || '';

    if (!heading || !form) {
        return;
    }

    const identity = currentIdentity();

    if (form.dataset.editId) {
        const displayId = identity?.display_id || '---';
        heading.textContent = `Eintrag bearbeiten (${displayId} - ${type})`;
    } else {
        heading.textContent = type
            ? `Neues Element (${type})`
            : 'Neues Element anlegen';
    }
}

function refreshOpenAcceptanceSuggestions() {
    const acceptanceModal = element('acceptanceCriteriaModal');

    if (
        acceptanceModal &&
        !acceptanceModal.classList.contains('hidden')
    ) {
        // Der vorhandene Assistent liest beim Klick immer den aktuellen Typ.
        element('btnSuggestAcceptanceCriteria')?.click();
    }
}

function scheduleVisibleLabelReplacement() {
    if (replacementScheduled) {
        return;
    }

    replacementScheduled = true;

    requestAnimationFrame(() => {
        replacementScheduled = false;
        replaceVisibleLegacyLabels(document.body);
    });
}

function replacementPairs() {
    return [...identityByLegacyKey.entries()]
        .filter(([legacyKey, item]) => legacyKey && item?.display_label)
        .sort((first, second) => second[0].length - first[0].length);
}

function replaceVisibleLegacyLabels(root) {
    if (!root || !identityByLegacyKey.size) {
        return;
    }

    const ignoredTags = new Set([
        'SCRIPT',
        'STYLE',
        'TEXTAREA',
        'INPUT',
        'SELECT',
        'OPTION',
        'CODE',
        'PRE'
    ]);

    const walker = document.createTreeWalker(
        root,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode(node) {
                const parent = node.parentElement;

                if (
                    !parent ||
                    ignoredTags.has(parent.tagName) ||
                    parent.closest('#acceptanceCriteriaModal')
                ) {
                    return NodeFilter.FILTER_REJECT;
                }

                return node.nodeValue?.trim()
                    ? NodeFilter.FILTER_ACCEPT
                    : NodeFilter.FILTER_REJECT;
            }
        }
    );

    const pairs = replacementPairs();
    const nodes = [];
    let node;

    while ((node = walker.nextNode())) {
        nodes.push(node);
    }

    nodes.forEach(textNode => {
        let value = textNode.nodeValue;

        pairs.forEach(([legacyKey, item]) => {
            if (value.includes(legacyKey)) {
                value = value.split(legacyKey).join(item.display_label);
            }
        });

        if (value !== textNode.nodeValue) {
            textNode.nodeValue = value;
        }
    });
}

function captureActiveRequirementFromForm() {
    const form = element('reqForm');

    if (!form) {
        return;
    }

    activeRequirementId = form.dataset.editId || null;
    updateModalHeading();
}

function bindTypeChange() {
    const typeSelect = element('type');

    if (!typeSelect || typeSelect.dataset.identityTypeBound === '1') {
        return;
    }

    typeSelect.dataset.identityTypeBound = '1';

    typeSelect.addEventListener('change', () => {
        const identity = currentIdentity();

        if (identity) {
            identity.type = typeSelect.value;
            identity.display_label =
                `${identity.display_id} - ${typeSelect.value}`;
        }

        updateModalHeading();
        refreshOpenAcceptanceSuggestions();
        scheduleVisibleLabelReplacement();
    });
}

function observeApplication() {
    const observer = new MutationObserver(mutations => {
        let relevant = false;

        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(addedNode => {
                if (
                    addedNode.nodeType === Node.ELEMENT_NODE ||
                    addedNode.nodeType === Node.TEXT_NODE
                ) {
                    relevant = true;
                }
            });
        });

        if (relevant) {
            captureActiveRequirementFromForm();
            bindTypeChange();
            scheduleVisibleLabelReplacement();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

function init() {
    bindTypeChange();
    observeApplication();
    loadIdentityMap();

    document.addEventListener('click', event => {
        if (
            event.target.closest('#new') ||
            event.target.closest('[onclick*="editRequirement"]') ||
            event.target.closest('[data-requirement-id]')
        ) {
            setTimeout(captureActiveRequirementFromForm, 0);
        }
    });

    element('projectSelect')?.addEventListener('change', () => {
        setTimeout(loadIdentityMap, 700);
    });

    element('reqForm')?.addEventListener('submit', () => {
        setTimeout(loadIdentityMap, 900);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
