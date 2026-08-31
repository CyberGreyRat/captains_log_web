// dashboard/js/risks.js
import { currentProjectId } from './state.js';

let pendingArchiveRiskId = null;
let loadedRisks = [];
let stakeholders = [];
let context = { requirements: [], verification: [], tasks: [], issues: [], links: {} };
const selected = { requirements: new Set(), verification: new Set(), tasks: new Set(), issues: new Set() };

const esc = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));
const parseAttrs = risk => typeof risk.attributes === 'object' && risk.attributes !== null ? risk.attributes : (() => { try { return JSON.parse(risk.attributes || '{}') || {}; } catch { return {}; } })();
const value = id => document.getElementById(id)?.value || '';
const setValue = (id, newValue) => { const element=document.getElementById(id); if(element) element.value=newValue ?? ''; };

// =============================================================================
// RISIKEN UND REFERENZDATEN LADEN
// =============================================================================
export async function loadRisks() {
    if (!currentProjectId) return;
    try {
        const [stakeholderResponse, requirementResponse] = await Promise.all([
            fetch(`../api/get_stakeholders.php?project_id=${encodeURIComponent(currentProjectId)}`),
            fetch(`../api/get_requirements.php?project_id=${encodeURIComponent(currentProjectId)}&t=${Date.now()}`)
        ]);
        const stakeholderData = await stakeholderResponse.json();
        const requirementData = await requirementResponse.json();
        stakeholders = stakeholderData.success ? stakeholderData.stakeholders || [] : [];
        const responsible = document.getElementById('risk_responsible');
        if (responsible) responsible.innerHTML='<option value="">-- Niemand --</option>'+stakeholders.map(item=>`<option value="${Number(item.id)}">${esc(item.name)}</option>`).join('');
        if (!requirementData.success) throw new Error(requirementData.error || 'Risiken konnten nicht geladen werden.');
        loadedRisks=(requirementData.requirements||[]).filter(item=>item.type==='RISK'&&item.review_status!=='Archiviert');
        renderRiskTable();
    } catch(error) { console.error('Fehler beim Laden der Risiken:',error); }
}

function renderRiskTable() {
    const tbody=document.getElementById('riskTableBody'); const map=document.getElementById('riskMapPoints');
    if(!tbody||!map)return;
    tbody.innerHTML=''; map.innerHTML='';
    if(!loadedRisks.length){tbody.innerHTML='<tr><td colspan="12" class="p-6 text-center italic text-slate-400">Keine aktiven Risiken erfasst.</td></tr>';return;}
    loadedRisks.forEach((risk,index)=>{
        const a=parseAttrs(risk), w=Number(a.w)||1, e=Number(a.e)||1, score=w*e;
        const color=score>=15?'bg-red-600 text-white':score>=10?'bg-orange-500 text-white':score>=5?'bg-amber-400 text-slate-900':'bg-emerald-500 text-white';
        const person=stakeholders.find(item=>Number(item.id)===Number(risk.source_contact))?.name||a.responsible||'';
        tbody.insertAdjacentHTML('beforeend',`<tr class="border-b border-slate-200 hover:bg-slate-50"><td class="p-3 text-xs">${new Date(risk.created_at||Date.now()).toLocaleDateString('de-DE')}</td><td class="p-3 font-mono text-xs font-bold">${esc(risk.req_key)}</td><td class="p-3"><div class="font-bold">${esc(risk.title)}</div><div class="text-[10px] uppercase text-slate-400">${esc(a.risk_type||'technical_product')} · ${esc(a.workflow_status||'open')}</div></td><td class="p-3 text-center">${w}</td><td class="p-3 text-center">${e}</td><td class="p-3 text-center"><span class="inline-flex h-7 min-w-7 items-center justify-center rounded ${color}">${score}</span></td><td class="p-3 text-xs">${esc(person)}</td><td class="p-3 text-xs">${a.review_date?new Date(a.review_date).toLocaleDateString('de-DE'):'-'}</td><td class="max-w-xs truncate p-3 text-xs" title="${esc(a.mitigation_plan||'')}">${esc(a.mitigation_plan||'')}</td><td class="max-w-xs truncate p-3 text-xs">${esc(a.decision||'')}</td><td class="max-w-xs truncate p-3 text-xs">${esc(a.effect||'')}</td><td class="p-3 text-right"><div class="flex justify-end gap-2"><button type="button" onclick="window.editRisk(${Number(risk.id)})" class="text-blue-700 text-xs font-bold hover:underline">Bearbeiten</button><button type="button" onclick="window.archiveRisk(${Number(risk.id)})" class="text-red-600 text-xs font-bold hover:underline">Archivieren</button></div></td></tr>`);
        const x=((w-1)*20)+10, y=100-(((e-1)*20)+10); map.insertAdjacentHTML('beforeend',`<button type="button" onclick="window.editRisk(${Number(risk.id)})" class="absolute flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full ${color} text-xs font-bold shadow" style="left:${x}%;top:${y}%" title="${esc(risk.req_key+' '+risk.title)}">${index+1}</button>`);
    });
}

// =============================================================================
// INLINE-LISTEN FÜR TRACEABILITY
// =============================================================================
async function loadContext(riskId=0) {
    const response=await fetch(`../api/get_risk_context.php?project_id=${encodeURIComponent(currentProjectId)}&risk_id=${Number(riskId)}`);
    const data=await response.json(); if(!response.ok||!data.success)throw new Error(data.error||'Traceability konnte nicht geladen werden.');
    context=data; ['requirements','verification','tasks','issues'].forEach(group=>selected[group]=new Set((data.links?.[group]||[]).map(Number))); renderAllLists();
}
function listRow(group,id,key,title,meta){return `<label class="flex cursor-pointer items-start gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0 hover:bg-blue-50"><input type="checkbox" class="risk-link-cb mt-0.5 h-4 w-4" data-group="${group}" value="${Number(id)}" ${selected[group].has(Number(id))?'checked':''}><span class="min-w-0 text-xs"><strong class="font-mono text-blue-950">${esc(key)}</strong><span class="ml-2 text-[10px] font-bold uppercase text-slate-400">${esc(meta||'')}</span><span class="mt-0.5 block truncate text-slate-700" title="${esc(title)}">${esc(title)}</span></span></label>`;}
function renderGroup(group,listId,searchId,mapper){const list=document.getElementById(listId);if(!list)return;const query=value(searchId).trim().toLowerCase();const rows=(context[group]||[]).filter(item=>!query||Object.values(item).filter(Boolean).join(' ').toLowerCase().includes(query));list.innerHTML=rows.length?rows.map(mapper).join(''):'<div class="p-4 text-sm italic text-slate-400">Keine passenden Einträge.</div>';}
function renderAllLists(){renderGroup('requirements','riskReqList','riskReqSearch',item=>listRow('requirements',item.id,item.req_key,item.title,item.type));renderGroup('verification','riskVerificationList','riskVerificationSearch',item=>listRow('verification',item.id,item.req_key,item.title,item.type));renderGroup('tasks','riskTaskList','riskTaskSearch',item=>listRow('tasks',item.id,item.wbs_code||item.id,item.title,item.category));renderGroup('issues','riskIssueList','riskIssueSearch',item=>listRow('issues',item.id,item.issue_key,item.title,item.status));}

// =============================================================================
// FORMULAR ÖFFNEN, BEWERTEN UND SPEICHERN
// =============================================================================
function updateScores(){document.getElementById('risk_initial_score').textContent=String((Number(value('risk_w'))||1)*(Number(value('risk_e'))||1));document.getElementById('risk_residual_score').textContent=String((Number(value('risk_residual_w'))||1)*(Number(value('risk_residual_e'))||1));}
async function openRisk(risk=null){document.getElementById('formRisk').reset();setValue('risk_id',risk?.id||'');const a=risk?parseAttrs(risk):{};setValue('risk_type',a.risk_type||'technical_product');setValue('risk_workflow_status',a.workflow_status||'open');setValue('risk_title',risk?.title||'');setValue('risk_cause',a.cause||'');setValue('risk_malfunction',a.malfunction||'');setValue('risk_effect',a.effect||'');setValue('risk_w',a.w||1);setValue('risk_e',a.e||1);setValue('risk_responsible',risk?.source_contact||'');setValue('risk_date',a.review_date||'');setValue('risk_implementation_status',a.implementation_status||'open');setValue('risk_mitigation',a.mitigation_plan||'');setValue('risk_decision',a.decision||'');setValue('risk_residual_w',a.residual_w||a.w||1);setValue('risk_residual_e',a.residual_e||a.e||1);document.getElementById('risk_residual_accepted').checked=Boolean(a.residual_accepted);setValue('risk_residual_reason',a.residual_reason||'');document.getElementById('riskModalTitle').textContent=risk?`Risiko bearbeiten (${risk.req_key})`:'Neues Risiko erfassen';updateScores();document.getElementById('modalRisk').classList.remove('hidden');try{await loadContext(risk?.id||0);}catch(error){alert(error.message);}}
function closeRisk(){document.getElementById('modalRisk').classList.add('hidden');}
async function saveRisk(event){event.preventDefault();const button=event.submitter;button.disabled=true;const payload={id:value('risk_id'),project_id:currentProjectId,title:value('risk_title'),risk_type:value('risk_type'),cause:value('risk_cause'),malfunction:value('risk_malfunction'),effect:value('risk_effect'),w:Number(value('risk_w')),e:Number(value('risk_e')),responsible:value('risk_responsible'),review_date:value('risk_date'),workflow_status:value('risk_workflow_status'),implementation_status:value('risk_implementation_status'),mitigation_plan:value('risk_mitigation'),decision:value('risk_decision'),residual_w:Number(value('risk_residual_w')),residual_e:Number(value('risk_residual_e')),residual_accepted:document.getElementById('risk_residual_accepted').checked,residual_reason:value('risk_residual_reason'),requirement_ids:[...selected.requirements],verification_ids:[...selected.verification],task_ids:[...selected.tasks],issue_ids:[...selected.issues]};try{const response=await fetch('../api/set_risk.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.error||'Risiko konnte nicht gespeichert werden.');closeRisk();await loadRisks();}catch(error){alert('Fehler: '+error.message);}finally{button.disabled=false;}}

// =============================================================================
// EVENTS, BEARBEITUNG UND ARCHIVIERUNG
// =============================================================================
export function initRiskEvents(){document.getElementById('btnNewRisk')?.addEventListener('click',()=>{if(!currentProjectId)return alert('Bitte zuerst ein Projekt auswählen!');openRisk();});document.getElementById('formRisk')?.addEventListener('submit',saveRisk);document.getElementById('riskModalClose')?.addEventListener('click',closeRisk);document.getElementById('riskModalCancel')?.addEventListener('click',closeRisk);['risk_w','risk_e','risk_residual_w','risk_residual_e'].forEach(id=>document.getElementById(id)?.addEventListener('input',updateScores));[['riskReqSearch',renderAllLists],['riskVerificationSearch',renderAllLists],['riskTaskSearch',renderAllLists],['riskIssueSearch',renderAllLists]].forEach(([id,handler])=>document.getElementById(id)?.addEventListener('input',handler));['riskReqList','riskVerificationList','riskTaskList','riskIssueList'].forEach(id=>document.getElementById(id)?.addEventListener('change',event=>{const box=event.target.closest('.risk-link-cb');if(!box)return;const group=box.dataset.group,target=Number(box.value);box.checked?selected[group].add(target):selected[group].delete(target);}));document.getElementById('modalRiskCancelBtn')?.addEventListener('click',()=>{pendingArchiveRiskId=null;document.getElementById('riskArchiveModal').classList.add('hidden');});document.getElementById('modalRiskConfirmBtn')?.addEventListener('click',archiveConfirmed);}
window.editRisk=id=>{const risk=loadedRisks.find(item=>Number(item.id)===Number(id));if(risk)openRisk(risk);};
window.archiveRisk=id=>{const risk=loadedRisks.find(item=>Number(item.id)===Number(id));if(!risk)return;pendingArchiveRiskId=Number(id);document.getElementById('modalArchiveRiskName').textContent=`"${risk.req_key}"`;document.getElementById('riskArchiveModal').classList.remove('hidden');};
async function archiveConfirmed(){if(!pendingArchiveRiskId)return;const risk=loadedRisks.find(item=>Number(item.id)===pendingArchiveRiskId);if(!risk)return;const attrs=parseAttrs(risk);document.getElementById('riskArchiveModal').classList.add('hidden');pendingArchiveRiskId=null;const response=await fetch('../api/set_requirements.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:risk.id,project_id:currentProjectId,type:'RISK',title:risk.title,description:risk.description||risk.title,source_contact:risk.source_contact||'',review_status:'Archiviert',attributes:attrs})});const data=await response.json();if(data.success)loadRisks();else alert('Fehler beim Archivieren: '+data.error);}
