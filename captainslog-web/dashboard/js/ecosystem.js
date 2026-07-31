import { currentRequirements } from './state.js';

export function drawEcosystem() {
    const nodesContainer = document.getElementById('ecosystem-nodes');
    const svg = document.getElementById('ecosystem-lines');
    if (!nodesContainer || !svg) return;

    const stks = currentRequirements.filter(r => r.type === 'STK');
    const ucs = currentRequirements.filter(r => r.type === 'UC');
    const uss = currentRequirements.filter(r => r.type === 'US');

    if (stks.length === 0 && ucs.length === 0 && uss.length === 0) {
        nodesContainer.innerHTML = `<div class="text-slate-500 italic text-center mt-20">Lege zuerst Stakeholder (STK), Use Cases (UC) oder User Stories (US) an.</div>`;
        svg.innerHTML = '';
        return;
    }

    // 3 Spalten (Stakeholder -> Use Cases -> User Stories)
    let html = `<div class="flex w-full justify-between gap-10">`;
    
    // Spalte 1: Stakeholder
    html += `<div class="flex-1 flex flex-col gap-6"><h3 class="font-bold text-slate-400 uppercase text-center border-b border-slate-300 pb-2">Stakeholder</h3>`;
    stks.forEach(r => html += buildCard(r, 'purple'));
    html += `</div>`;

    // Spalte 2: Use Cases
    html += `<div class="flex-1 flex flex-col gap-6"><h3 class="font-bold text-slate-400 uppercase text-center border-b border-slate-300 pb-2">Use Cases</h3>`;
    ucs.forEach(r => html += buildCard(r, 'blue'));
    html += `</div>`;

    // Spalte 3: User Stories
    html += `<div class="flex-1 flex flex-col gap-6"><h3 class="font-bold text-slate-400 uppercase text-center border-b border-slate-300 pb-2">User Stories</h3>`;
    uss.forEach(r => html += buildCard(r, 'emerald'));
    html += `</div>`;

    html += `</div>`;
    nodesContainer.innerHTML = html;

    // Pfeile zeichnen (verzögert, damit der Browser das HTML erst rendern kann)
    setTimeout(() => drawLines(), 100);
}

function buildCard(req, color) {
    let attrs = req.attributes || {};
    if (typeof attrs === 'string') { try { attrs = JSON.parse(attrs); } catch(e) { attrs = {}; } }
    
    let extra = '';
    if(req.type === 'STK') {
        extra = `<div class="mt-2 text-xs text-slate-500 truncate"><span class="font-semibold text-slate-600">${attrs.role || 'Rolle unbekannt'}</span><br>${attrs.email || ''}<br>${attrs.organization || ''}</div>`;
    }

    return `
        <div id="card-${req.req_key}" class="bg-white border-l-4 border-${color}-400 shadow-md rounded-md p-4 z-10 relative hover:-translate-y-1 hover:shadow-lg transition cursor-pointer">
            <div class="text-[10px] font-bold text-${color}-600 mb-1">${req.req_key}</div>
            <div class="font-bold text-sm text-slate-800 leading-tight">${req.title}</div>
            ${extra}
        </div>
    `;
}

function drawLines() {
    const svg = document.getElementById('ecosystem-lines');
    const container = document.getElementById('ecosystem-canvas');
    svg.innerHTML = '';
    
    const canvasRect = container.getBoundingClientRect();

    // Definiere die Pfeilspitze (Marker) für das SVG
    const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
    defs.innerHTML = `
        <marker id="arrow" viewBox="0 0 10 10" refX="10" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#94a3b8" />
        </marker>
    `;
    svg.appendChild(defs);

    currentRequirements.forEach(req => {
        if(!['STK','US','UC'].includes(req.type)) return;
        const fromCard = document.getElementById(`card-${req.req_key}`);
        if(!fromCard) return;

        let children = req.children || [];
        if(typeof children === 'string') { try { children = JSON.parse(children); } catch(e) { children = []; } }

        children.forEach(childKey => {
            const toCard = document.getElementById(`card-${childKey}`);
            if(!toCard) return;

            const fRect = fromCard.getBoundingClientRect();
            const tRect = toCard.getBoundingClientRect();

            // Koordinaten berechnen (unter Berücksichtigung des Scroll-Offsets)
            const startX = fRect.right - canvasRect.left + container.scrollLeft;
            const startY = fRect.top + (fRect.height/2) - canvasRect.top + container.scrollTop;
            const endX = tRect.left - canvasRect.left + container.scrollLeft;
            const endY = tRect.top + (tRect.height/2) - canvasRect.top + container.scrollTop;

            // Bezier-Kurve zeichnen
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", `M ${startX} ${startY} C ${startX + 50} ${startY}, ${endX - 50} ${endY}, ${endX} ${endY}`);
            path.setAttribute("fill", "transparent");
            path.setAttribute("stroke", "#94a3b8");
            path.setAttribute("stroke-width", "2");
            path.setAttribute("marker-end", "url(#arrow)");
            
            svg.appendChild(path);
        });
    });
}