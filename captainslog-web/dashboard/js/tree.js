import { currentRequirements } from './state.js';
import { showDetail } from './modals.js';

export function drawSidebar() {
    const itemsDiv = document.getElementById('items');
    itemsDiv.innerHTML = '';

    if (currentRequirements.length === 0) {
        itemsDiv.innerHTML = '<div class="p-4 text-sm text-slate-500">Noch keine Anforderungen vorhanden.</div>';
        return;
    }

    currentRequirements.forEach(req => {
        if (typeof req.parents === 'string') {
            try { req.parents = JSON.parse(req.parents || '[]'); } catch (e) { req.parents = []; }
        }
        if (!Array.isArray(req.parents)) req.parents = [];
    });

    const rendered = new Set();

    function renderNode(req, level) {
        if (rendered.has(req.req_key)) return;
        rendered.add(req.req_key);

        const btn = document.createElement('button');
        const indentRem = level * 1.5;
        const prefix = level > 0 ? `<span class="text-slate-400 mr-1 font-normal">└</span>` : '';
        const bgClass = level > 0 ? 'bg-slate-50/50' : 'bg-white';

        btn.className = `flex w-full items-center gap-2 border-b py-2 pr-3 text-left text-xs hover:bg-blue-50 focus:bg-blue-100 transition-colors ${bgClass}`;
        btn.style.paddingLeft = `calc(0.75rem + ${indentRem}rem)`;

        btn.innerHTML = `
            <b class="font-mono text-blue-900 min-w-[70px]">${prefix}${req.req_key}</b>
            <span class="truncate flex-1">${req.title}</span>
            <span class="text-slate-300 text-[10px]">●</span>
        `;
        btn.onclick = () => showDetail(req);
        itemsDiv.appendChild(btn);

        const children = currentRequirements.filter(r => r.parents.includes(req.req_key));
        children.forEach(child => renderNode(child, level + 1));
    }

    const roots = currentRequirements.filter(req =>
        req.parents.length === 0 ||
        !req.parents.some(parentKey => currentRequirements.find(r => r.req_key === parentKey))
    );

    roots.forEach(root => renderNode(root, 0));

    currentRequirements.forEach(req => {
        if (!rendered.has(req.req_key)) {
            renderNode(req, 0);
        }
    });
}


export function populateParentChildDropdowns(selectedParents = [], selectedChildren = []) {
    const parentContainer = document.getElementById('parentsCheckboxList');
    const childContainer = document.getElementById('childrenCheckboxList');
    if (!parentContainer || !childContainer) return;

    if (currentRequirements.length === 0) {
        parentContainer.innerHTML = '<span class="text-slate-400 italic">Keine Anforderungen verfügbar</span>';
        childContainer.innerHTML = '<span class="text-slate-400 italic">Keine Anforderungen verfügbar</span>';
        return;
    }

    const generateCheckboxes = (selectedValues) => {
        return currentRequirements.map(r => {
            const isChecked = selectedValues.includes(r.req_key) ? 'checked' : '';
            return `
                <label class="flex items-center gap-2 p-1 hover:bg-slate-50 rounded cursor-pointer checkbox-item">
                    <input type="checkbox" value="${r.req_key}" ${isChecked} class="rounded border-slate-300 text-blue-900 focus:ring-blue-900">
                    <span class="font-mono font-bold text-blue-900">${r.req_key}</span>
                    <span class="truncate text-slate-700">${r.title}</span>
                </label>
            `;
        }).join('');
    };

    parentContainer.innerHTML = generateCheckboxes(selectedParents);
    childContainer.innerHTML = generateCheckboxes(selectedChildren);
}

// Globale Suchfunktion für die Checkbox-Listen
window.filterCheckboxes = function(inputId, containerId) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.checkbox-item');

    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? 'flex' : 'none';
    });
};