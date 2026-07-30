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

export function populateParentChildDropdowns() {
    const options = currentRequirements.map(r => `<option value="${r.req_key}">${r.req_key} - ${r.title}</option>`).join('');
    const pEl = document.getElementById('parents');
    const cEl = document.getElementById('children');
    if (pEl) pEl.innerHTML = options;
    if (cEl) cEl.innerHTML = options;
}