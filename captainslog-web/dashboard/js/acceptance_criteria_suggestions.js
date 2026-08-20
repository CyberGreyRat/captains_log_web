let selectedTemplateIds = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
}

function field(id) { return document.getElementById(id); }

function ensureUi() {
    const textarea=field('acceptance_criteria');
    if(!textarea || field('acceptanceCriteriaAssistant')) return;
    const box=document.createElement('div');
    box.id='acceptanceCriteriaAssistant';
    box.className='mt-3 flex flex-wrap gap-2';
    box.innerHTML=`
      <button id="btnSuggestAcceptanceCriteria" type="button" class="cl-button cl-button-secondary">Kriterien vorschlagen</button>
      <button id="btnSaveAcceptanceCriteriaTemplate" type="button" class="cl-button cl-button-secondary">Als Vorlage speichern</button>
      <span class="self-center text-xs text-slate-500">Vorschläge bleiben vollständig bearbeitbar.</span>`;
    textarea.insertAdjacentElement('afterend',box);

    document.body.insertAdjacentHTML('beforeend',`
      <div id="acceptanceCriteriaModal" class="cl-modal-overlay hidden z-[260]">
        <div class="cl-modal max-w-3xl">
          <div class="cl-modal-header"><div><p class="cl-panel-eyebrow">Formulierungshilfe</p><h2 class="cl-modal-title">Akzeptanzkriterien vorschlagen</h2></div><button id="acceptanceCriteriaClose" type="button" class="cl-button cl-button-secondary">✕</button></div>
          <div class="cl-modal-body">
            <div id="acceptanceCriteriaContext" class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-3 text-xs text-blue-950"></div>
            <div id="acceptanceCriteriaSuggestions" class="space-y-2"><div class="cl-empty-state">Vorschläge werden geladen...</div></div>
          </div>
          <div class="cl-modal-footer"><button id="acceptanceCriteriaCancel" type="button" class="cl-button cl-button-secondary">Abbrechen</button><button id="acceptanceCriteriaInsert" type="button" class="cl-button cl-button-primary">Auswahl übernehmen</button></div>
        </div>
      </div>`);

    field('btnSuggestAcceptanceCriteria').addEventListener('click',openSuggestions);
    field('btnSaveAcceptanceCriteriaTemplate').addEventListener('click',saveCurrentCriteria);
    field('acceptanceCriteriaClose').addEventListener('click',closeModal);
    field('acceptanceCriteriaCancel').addEventListener('click',closeModal);
    field('acceptanceCriteriaInsert').addEventListener('click',insertSelected);
}

function closeModal(){ field('acceptanceCriteriaModal')?.classList.add('hidden'); }

async function openSuggestions(){
    const type=field('type')?.value||'';
    const title=field('title')?.value||'';
    const description=field('text')?.value||'';
    const rationale=field('rationale')?.value||'';
    const query=[title,description,rationale].join(' ').slice(0,1800);
    field('acceptanceCriteriaContext').textContent=`Typ ${type || '-'} · ${title || 'ohne Titel'}`;
    field('acceptanceCriteriaSuggestions').innerHTML='<div class="cl-empty-state">Vorschläge werden geladen...</div>';
    field('acceptanceCriteriaModal').classList.remove('hidden');
    try{
        const params=new URLSearchParams({type,query,limit:'30'});
        const response=await fetch(`../api/get_acceptance_criteria_suggestions.php?${params}`);
        const data=await response.json();
        if(!response.ok||!data.success) throw new Error(data.error||'Vorschläge konnten nicht geladen werden.');
        field('acceptanceCriteriaSuggestions').innerHTML=data.suggestions.map((item,index)=>`
          <label class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 bg-white p-3 hover:border-blue-300 hover:bg-blue-50/40">
            <input type="checkbox" class="acceptance-suggestion mt-1" value="${item.id}" data-text="${escapeHtml(item.criterion_text)}" ${index<5?'checked':''}>
            <span><span class="block text-[10px] font-extrabold uppercase text-blue-950">${escapeHtml(item.category)} · ${escapeHtml(item.source_type)}</span><span class="mt-1 block text-sm text-slate-700">${escapeHtml(item.criterion_text)}</span></span>
          </label>`).join('')||'<div class="cl-empty-state">Keine passenden Vorlagen gefunden.</div>';
    }catch(error){field('acceptanceCriteriaSuggestions').innerHTML=`<div class="cl-empty-state text-red-600">${escapeHtml(error.message)}</div>`;}
}

function insertSelected(){
    const textarea=field('acceptance_criteria');
    const checked=[...document.querySelectorAll('.acceptance-suggestion:checked')];
    const existing=textarea.value.trim();
    const lines=checked.map(input=>`- ${input.dataset.text}`);
    selectedTemplateIds=checked.map(input=>Number(input.value));
    textarea.value = [existing, ...lines].filter(Boolean).join('\n');
    textarea.dispatchEvent(new Event('input',{bubbles:true}));
    closeModal();
    if(selectedTemplateIds.length) fetch('../api/use_acceptance_criteria_template.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids:selectedTemplateIds})}).catch(()=>{});
}

async function saveCurrentCriteria(){
    const criteria=field('acceptance_criteria')?.value.trim();
    if(!criteria){ alert('Bitte zuerst mindestens ein Akzeptanzkriterium eintragen.'); return; }
    const type=field('type')?.value||'';
    try{
      const response=await fetch('../api/save_acceptance_criteria_template.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type,category:'Benutzerdefiniert',criteria,keywords:[field('title')?.value,field('text')?.value].join(' ').slice(0,500)})});
      const data=await response.json();
      if(!response.ok||!data.success) throw new Error(data.error||'Speichern fehlgeschlagen.');
      alert(`${data.saved} Kriterium/Kriterien wurden in den Vorlagenkatalog übernommen.`);
    }catch(error){alert(error.message);}
}

function autoLearnOnSubmit(){
    const form=field('reqForm');
    if(!form||form.dataset.acceptanceLearningBound==='1') return;
    form.dataset.acceptanceLearningBound='1';
    form.addEventListener('submit',()=>{
      const criteria=field('acceptance_criteria')?.value.trim();
      const type=field('type')?.value||'';
      if(!criteria||!type) return;
      setTimeout(()=>fetch('../api/save_acceptance_criteria_template.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type,category:'Aus bearbeiteter Anforderung gelernt',criteria,keywords:[field('title')?.value,field('text')?.value].join(' ').slice(0,500)})}).catch(()=>{}),250);
    });
}

function init(){ ensureUi(); autoLearnOnSubmit(); }
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
