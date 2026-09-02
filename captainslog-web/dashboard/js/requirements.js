// dashboard/js/requirements.js
import { currentProjectId } from './state.js';
const A='../api/';let editor=null,docs=[],types=[],templates=[],current=null,tab='meta',dirty=false,events=false,toolsLoaded=false,relations=[],entities=[],tests=[],coverage=null,pickerAction=null,editMode=false,editorInitializing=false,lastLoadedData=null;const $=x=>document.getElementById(x),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(url,opt={}){const r=await fetch(url,{...opt,headers:{Accept:'application/json',...(opt.body&&!opt.form?{'Content-Type':'application/json'}:{})}}),t=await r.text();let d;try{d=JSON.parse(t)}catch{throw Error(`API ${r.status}: ${t.slice(0,180)}`)}if(!r.ok||!(d.success===true||d.success===1))throw Error(d.error||`HTTP ${r.status}`);return d}function state(v){$('reqSaveState').textContent=v}function draftKey(){return current?`captainslog:draft:${current.id}`:null}function changed(){if(!editMode||editorInitializing)return;dirty=true;state('Ungespeichert');clearTimeout(changed.t);changed.t=setTimeout(saveDraft,500)}async function saveDraft(){if(!editor||!current)return;try{localStorage.setItem(draftKey(),JSON.stringify({savedAt:Date.now(),title:$('v4Title').value,data:await editor.save()}))}catch{}}
function script(src,key){if(window[key])return Promise.resolve();return new Promise((ok,no)=>{const s=document.createElement('script');s.src=src;s.onload=ok;s.onerror=no;document.head.append(s)})}async function loadTools(){if(toolsLoaded)return;await script('https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.31.0','EditorJS');await script('https://cdn.jsdelivr.net/npm/@editorjs/list@2.0.8','EditorjsList');await script('https://cdn.jsdelivr.net/npm/@editorjs/table@2.4.5','Table');toolsLoaded=true}
function acUuid(){
    if(window.crypto?.randomUUID)return window.crypto.randomUUID();
    return 'ac-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,10);
}
function acPlainLines(value){
    const box=document.createElement('div');
    box.innerHTML=String(value??'').replace(/<br\s*\/?>/gi,'\n').replace(/<\/p>/gi,'\n').replace(/<\/div>/gi,'\n');
    return(box.textContent||'').split(/\r?\n/).map(x=>x.replace(/^\s*[-*•–—☐☑◇]\s*/u,'').trim()).filter(Boolean);
}
class Text{
    constructor({data={}}){this.d=data}
    static get toolbox(){return{title:'Text',icon:'T'}}
    static get conversionConfig(){return{export:data=>data.text||'',import:value=>({text:Array.isArray(value)?value.join('<br>'):String(value??'')})}}
    render(){this.e=document.createElement('div');this.e.className='ce-paragraph';this.e.contentEditable='true';this.e.innerHTML=this.d.text||'';return this.e}
    save(){return{...this.d,text:this.e.innerHTML}}
    static get sanitize(){return{text:{b:true,i:true,u:true,br:true,a:{href:true}}}}
}
class Heading{
    constructor({data={}}){this.d={...data,level:Math.max(3,Math.min(5,+data.level||3))}}
    static get toolbox(){return{title:'Überschrift',icon:'H'}}
    static get conversionConfig(){return{export:data=>data.text||'',import:value=>({text:String(value??''),level:3})}}
    render(){this.e=document.createElement(`h${this.d.level}`);this.e.className='ce-header';this.e.contentEditable='true';this.e.innerHTML=this.d.text||'Überschrift';return this.e}
    save(){return{...this.d,text:this.e.innerHTML,level:this.d.level}}
    renderSettings(){return[3,4,5].map(n=>({icon:`H${n}`,label:`Überschrift ${n}`,onActivate:()=>{const el=document.createElement(`h${n}`);el.className='ce-header';el.contentEditable='true';el.innerHTML=this.e.innerHTML;this.e.replaceWith(el);this.e=el;this.d.level=n;changed()}}))}
    static get sanitize(){return{text:{b:true,i:true,u:true,br:true}}}
}
class ChecklistTool{
    constructor({data={}}){const src=Array.isArray(data.items)?data.items:acPlainLines(data.text||'');this.items=(src.length?src:['']).map(x=>typeof x==='string'?{text:x,checked:false}:{text:String(x.text??''),checked:Boolean(x.checked)})}
    static get toolbox(){return{title:'Checkliste',icon:'☑'}}
    static get conversionConfig(){return{export:data=>(data.items||[]).map(x=>x.text||'').filter(Boolean).join('\n'),import:value=>({items:(Array.isArray(value)?value:acPlainLines(value)).map(text=>({text:String(text),checked:false}))})}}
    render(){this.root=document.createElement('div');this.root.className='ds-checklist';this.items.forEach(x=>this.add(x));return this.root}
    add(item,after=null){const row=document.createElement('div');row.className='ds-checklist-row';const check=document.createElement('input');check.type='checkbox';check.checked=!!item.checked;const text=document.createElement('div');text.className='ds-checklist-text';text.contentEditable='true';text.innerHTML=item.text||'';check.onchange=()=>{row.classList.toggle('is-checked',check.checked);changed()};text.onkeydown=e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();this.add({text:'',checked:false},row).querySelector('.ds-checklist-text').focus();changed()}else if(e.key==='Backspace'&&!text.textContent&&this.root.children.length>1){e.preventDefault();const target=row.previousElementSibling||row.nextElementSibling;row.remove();target?.querySelector('.ds-checklist-text')?.focus();changed()}};row.classList.toggle('is-checked',check.checked);row.append(check,text);after?after.insertAdjacentElement('afterend',row):this.root.append(row);return row}
    save(){return{items:[...this.root.querySelectorAll('.ds-checklist-row')].map(row=>({text:row.querySelector('.ds-checklist-text').innerHTML.trim(),checked:row.querySelector('input').checked})).filter(x=>x.text||x.checked)}}
    static get sanitize(){return{items:{},text:{b:true,i:true,u:true,br:true,a:{href:true}}}}
}
class AcceptanceCriteriaTool{
    constructor({data={}}){
        const source=Array.isArray(data.items)?data.items:acPlainLines(data.text||'');
        this.title=String(data.title||'Akzeptanzkriterien');
        this.rule=['ALL','ANY','THRESHOLD'].includes(data.rule)?data.rule:'ALL';
        this.threshold=Math.max(1,Number(data.threshold)||1);
        this.items=(source.length?source:['']).map(item=>typeof item==='string'?{id:acUuid(),text:item,revision:1,status:'open'}:{id:String(item.id||acUuid()),text:String(item.text??''),revision:Math.max(1,Number(item.revision)||1),status:['open','covered','passed','failed','outdated'].includes(item.status)?item.status:'open'});
    }
    static get toolbox(){return{title:'Akzeptanzkriterien',icon:'◇'}}
    static get conversionConfig(){
        return{
            export:data=>(data.items||[]).map(item=>item.text||'').filter(Boolean).join('\n'),
            import:value=>({title:'Akzeptanzkriterien',rule:'ALL',threshold:1,items:(Array.isArray(value)?value:acPlainLines(value)).map(text=>({id:acUuid(),text:String(text),revision:1,status:'open'}))})
        };
    }
    render(){
        this.root=document.createElement('section');this.root.className='ds-acceptance';
        const head=document.createElement('div');head.className='ds-acceptance-head';
        const title=document.createElement('div');title.className='ds-acceptance-title';title.contentEditable='true';title.textContent=this.title;
        const badge=document.createElement('span');badge.className='ds-acceptance-badge';badge.textContent='SOLL-KRITERIEN';
        head.append(title,badge);this.root.append(head);
        const info=document.createElement('p');info.className='ds-acceptance-info';info.textContent='Status wird durch verknüpfte Test Runs bestimmt. Hier werden nur Kriterien definiert.';this.root.append(info);
        const rows=document.createElement('div');rows.className='ds-acceptance-rows';this.rows=rows;this.root.append(rows);this.items.forEach(item=>this.add(item));
        const foot=document.createElement('div');foot.className='ds-acceptance-foot';foot.innerHTML='<span>Erfüllungsregel</span>';
        const rule=document.createElement('select');rule.className='ds-acceptance-rule';rule.innerHTML='<option value="ALL">Alle Kriterien</option><option value="ANY">Mindestens ein Kriterium</option><option value="THRESHOLD">Mindestanzahl</option>';rule.value=this.rule;
        const threshold=document.createElement('input');threshold.type='number';threshold.min='1';threshold.className='ds-acceptance-threshold';threshold.value=String(this.threshold);threshold.hidden=this.rule!=='THRESHOLD';
        rule.onchange=()=>{threshold.hidden=rule.value!=='THRESHOLD';changed()};threshold.oninput=changed;foot.append(rule,threshold);this.root.append(foot);return this.root;
    }
    add(item,after=null){
        const row=document.createElement('div');row.className='ds-acceptance-row';row.dataset.id=item.id;row.dataset.revision=String(item.revision||1);row.dataset.originalText=item.text||'';
        const state=document.createElement('span');state.className=`ds-acceptance-state is-${item.status||'open'}`;state.title=this.label(item.status);state.textContent=this.symbol(item.status);
        const text=document.createElement('div');text.className='ds-acceptance-text';text.contentEditable='true';text.innerHTML=item.text||'';
        const meta=document.createElement('span');meta.className='ds-acceptance-meta';meta.textContent=`R${item.revision||1}`;
        text.onkeydown=e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();this.add({id:acUuid(),text:'',revision:1,status:'open'},row).querySelector('.ds-acceptance-text').focus();changed()}else if(e.key==='Backspace'&&!text.textContent&&this.rows.children.length>1){e.preventDefault();const target=row.previousElementSibling||row.nextElementSibling;row.remove();target?.querySelector('.ds-acceptance-text')?.focus();changed()}};
        row.append(state,text,meta);after?after.insertAdjacentElement('afterend',row):this.rows.append(row);return row;
    }
    symbol(status){return({passed:'✓',failed:'×',covered:'◐',outdated:'!',open:'◇'})[status]||'◇'}
    label(status){return({passed:'Bestanden',failed:'Fehlgeschlagen',covered:'Mit Testfall abgedeckt',outdated:'Testnachweis veraltet',open:'Noch nicht abgedeckt'})[status]||'Offen'}
    save(){
        const title=this.root.querySelector('.ds-acceptance-title').textContent.trim()||'Akzeptanzkriterien';
        const rule=this.root.querySelector('.ds-acceptance-rule').value;
        const threshold=Math.max(1,Number(this.root.querySelector('.ds-acceptance-threshold').value)||1);
        const items=[...this.rows.querySelectorAll('.ds-acceptance-row')].map(row=>{const text=row.querySelector('.ds-acceptance-text').innerHTML.trim();const old=row.dataset.originalText||'';let revision=Math.max(1,Number(row.dataset.revision)||1);let status=row.querySelector('.ds-acceptance-state').className.match(/is-(open|covered|passed|failed|outdated)/)?.[1]||'open';if(old&&text!==old){revision++;status='outdated'}return{id:row.dataset.id||acUuid(),text,revision,status}}).filter(item=>item.text);
        return{schemaVersion:1,title,rule,threshold,items};
    }
    validate(data){return Array.isArray(data.items)&&data.items.every(item=>item.id&&typeof item.text==='string')}
    static get sanitize(){return{title:{},items:{},text:{b:true,i:true,u:true,br:true,a:{href:true}}}}
}
class LegacyRequirement extends Text{static get toolbox(){return{title:'Text (Legacy)',icon:'T'}}}
class Upload{constructor({data={},config}){this.d=data;this.kind=config.kind}render(){this.e=document.createElement('div');this.e.style.cssText='border:2px dashed #ccd6e2;padding:15px;text-align:center';this.paint();return this.e}paint(){if(this.d.fileId){const url=`../api/document_file.php?id=${encodeURIComponent(this.d.fileId)}`;this.e.innerHTML=this.kind==='image'?`<img src="${url}" style="max-width:100%"><input value="${esc(this.d.caption||'')}" placeholder="Bildunterschrift">`:`<a href="${url}" target="_blank">${esc(this.d.name||'Datei öffnen')}</a>`;return}this.e.textContent=this.kind==='image'?'Bild ablegen oder klicken':'Datei ablegen oder klicken';const i=document.createElement('input');i.type='file';i.style.display='none';i.accept=this.kind==='image'?'image/*':'*/*';i.onchange=()=>this.send(i.files[0]);this.e.onclick=()=>i.click();this.e.ondragover=e=>e.preventDefault();this.e.ondrop=e=>{e.preventDefault();this.send(e.dataTransfer.files[0])};this.e.append(i)}async send(file){if(!file)return;const f=new FormData();f.append('document_id',current.id);f.append('file',file);const d=await api(`${A}upload_document_file.php`,{method:'POST',body:f,form:true});this.d={fileId:d.file.id,name:d.file.name,mime:d.file.mime,size:d.file.size};this.paint();changed()}save(){if(this.kind==='image'){const i=this.e.querySelector('input');if(i)this.d.caption=i.value}return this.d}}
class ImageTool extends Upload{constructor(a){super({...a,config:{kind:'image'}})}static get toolbox(){return{title:'Bild',icon:'▧'}}}class FileTool extends Upload{constructor(a){super({...a,config:{kind:'file'}})}static get toolbox(){return{title:'Datei',icon:'📎'}}}
function editorTools(){return{text:Text,requirement:LegacyRequirement,heading:Heading,checklist:{class:ChecklistTool,inlineToolbar:true},acceptanceCriteria:{class:AcceptanceCriteriaTool,inlineToolbar:true},list:{class:window.EditorjsList,inlineToolbar:true,config:{defaultStyle:'unordered'}},table:{class:window.Table,inlineToolbar:true,config:{rows:2,cols:3,withHeadings:true}},image:ImageTool,file:FileTool}}
async function loadLists(){const[a,b,c]=await Promise.all([api(`${A}document_studio_v4.php?action=list&project_id=${encodeURIComponent(currentProjectId)}`),api(`${A}document_types.php?project_id=${encodeURIComponent(currentProjectId)}`),api(`${A}document_templates_v4.php?project_id=${encodeURIComponent(currentProjectId)}`)]);docs=a.documents||[];types=b.types||[];templates=c.templates||[];$('v4CreateType').innerHTML=types.map(x=>`<option value="${x.id}">${esc(x.type_key)} · ${esc(x.name)}</option>`).join('');$('v4CreateTemplate').innerHTML='<option value="">Leeres Dokument</option>'+templates.map(x=>`<option value="${x.id}" data-type="${esc(x.requirement_type)}">${esc(x.name)}</option>`).join('');tree()}
function depth(id,seen=new Set()){if(seen.has(id))return 0;seen.add(id);const d=docs.find(x=>x.id===id),p=d?.parent_ids?.[0];return p?1+depth(p,seen):0}
function tree(){
 const q=($('v4Search')?.value||'').toLowerCase();
 $('v4Tree').innerHTML=docs.filter(d=>`${d.requirement_key} ${d.title}`.toLowerCase().includes(q)).map(d=>{
  const coverage=`${Number(d.coverage_percent)}%`;
  const tooltip=`${d.requirement_key} · ${d.title}\nStatus: ${d.status}\nVerifikation: ${d.verification_status}\nCoverage: ${coverage}`;
  return `<button type="button" class="node ${current?.id===d.id?'active':''}" data-doc="${d.id}" title="${esc(tooltip)}" style="--tree-depth:${depth(d.id)}"><span class="node-key">${esc(d.requirement_key)}</span><span class="node-title">${esc(d.title)}</span><span class="node-coverage">${coverage}</span></button>`;
 }).join('')||'<i>Keine Dokumente</i>';
}
function applyDocumentMode(){
 const root=$('v4');if(!root)return;root.classList.toggle('is-editing',editMode);root.classList.toggle('is-reading',!editMode);
 $('v4Title').readOnly=!editMode;
 $('reqSave').classList.toggle('hidden',!editMode);
 $('v4CancelEdit')?.classList.toggle('hidden',!editMode);
 $('v4Edit')?.classList.toggle('hidden',editMode);
 $('v4Delete')?.classList.toggle('hidden',!editMode);
 document.querySelectorAll('#requirementsEditor [contenteditable]').forEach(el=>el.contentEditable=editMode?'true':'false');
 document.querySelectorAll('#requirementsEditor input,#requirementsEditor select,#requirementsEditor textarea').forEach(el=>el.disabled=!editMode);
 document.querySelectorAll('#v4Panel input,#v4Panel select,#v4Panel textarea').forEach(el=>el.disabled=!editMode);
 state(editMode?(dirty?'Ungespeichert':'Bearbeitungsmodus'):'Lesemodus');
}
function enterEditMode(){editMode=true;dirty=false;applyDocumentMode();setTimeout(()=>applyDocumentMode(),50)}
async function cancelEditMode(){
 if(!current)return;
 if(dirty&&!confirm('Ungespeicherte Änderungen verwerfen?'))return;
 localStorage.removeItem(draftKey());dirty=false;editMode=false;await openDoc(current.id,true);
}

async function loadSide(){const[r,e,t,c]=await Promise.all([api(`${A}document_links.php?kind=relation&document_id=${current.id}`),api(`${A}document_links.php?kind=entity&document_id=${current.id}`),api(`${A}document_test_links.php?document_id=${current.id}`),api(`${A}document_coverage.php?document_id=${current.id}`)]);relations=r.items||[];entities=e.items||[];tests=t.links||[];coverage=c.coverage;renderInspector();renderMargin()}
function renderMargin(){$('v4Margin').innerHTML=`<span>${esc(current.status)}</span><span>${esc(current.priority)}</span><span>${esc(coverage?.verification_status||'Not Covered')} ${coverage?.coverage_percent||0}%</span>`}function cards(items,kind){return items.map(x=>`<div class="card"><span><b>${esc(x.requirement_key||x.item_key||'')}</b> ${esc(x.title||x.label||x.test_case_id||x.test_run_id||'Eintrag')}</span><button data-remove="${x.id}" data-kind="${kind}" title="Entfernen">×</button></div>`).join('')||'<i>Keine Einträge</i>'}
function renderInspector(){const p=$('v4Panel');if(tab==='meta'){p.innerHTML=`<label class="field">Status<select id="iStatus"><option>Open</option><option>In Progress</option><option>Reviewed</option><option>Closed</option></select></label><label class="field">Priorität<select id="iPriority"><option>Low</option><option>Medium</option><option>High</option><option>Critical</option></select></label><label class="field">Relevanz<select id="iRelevance"><option>Must</option><option>Should</option><option>Could</option><option>Won't</option></select></label><label class="field">Review<select id="iReview"><option>New</option><option>In Review</option><option>Approved</option><option>Rejected</option></select></label><p>Revision: <b>${current.revision}</b></p>`;for(const[k,id]of[['status','iStatus'],['priority','iPriority'],['relevance','iRelevance'],['review_status','iReview']]){$(id).value=current[k];$(id).onchange=()=>{current[k]=$(id).value;changed();renderMargin()}}}else if(tab==='trace'){p.innerHTML=`<h3>Parents</h3>${cards(relations.filter(x=>x.role==='parent'),'relation')}<button data-pick-relation="parent">+ Parent auswählen</button><div class="section"><h3>Children</h3>${cards(relations.filter(x=>x.role==='child'),'relation')}<button data-pick-relation="child">+ Child auswählen</button></div><div class="section"><h3>Fachobjekte</h3>${cards(entities,'entity')}<button data-pick-entity="risk">+ Risiko</button> <button data-pick-entity="issue">+ Issue</button> <button data-pick-entity="task">+ Aufgabe</button> <button data-pick-entity="person">+ Person</button></div>`}else{p.innerHTML=`<h3>${esc(coverage.verification_status)}</h3><div class="coverage"><i style="width:${coverage.coverage_percent}%"></i></div><p>${coverage.executed_count} von ${coverage.leaf_count} Leaf-Anforderungen ausgeführt</p><p>Bestanden: ${coverage.passed_count}<br>Fehlgeschlagen: ${coverage.failed_count}<br>Veraltet: ${coverage.outdated_count}</p><div class="section"><h3>Testlinks</h3>${cards(tests,'test')}<button id="pickTest">+ Test auswählen</button></div>`}}
async function openDoc(id,force=false){
 if(!force&&editMode&&dirty&&!confirm('Ungespeicherte Änderungen verwerfen?'))return;
 editorInitializing=true;editMode=false;dirty=false;
 const d=await api(`${A}document_studio_v4.php?action=get&id=${id}`);current=d.document;lastLoadedData=d.editor;
 $('v4Empty').classList.add('hidden');$('v4Work').classList.remove('hidden');$('v4Key').textContent=current.requirement_key;$('v4Title').value=current.title;
 if(editor){try{await editor.isReady;editor.destroy?.()}catch{}}
 let data=d.editor;let restored=false;const raw=localStorage.getItem(`captainslog:draft:${id}`);
 if(raw&&!force){const draft=JSON.parse(raw);if(confirm('Lokalen Entwurf wiederherstellen?')){data=draft.data;$('v4Title').value=draft.title;restored=true}}
 editor=new window.EditorJS({holder:'requirementsEditor',tools:editorTools(),defaultBlock:'text',data,onChange:changed});await editor.isReady;
 editorInitializing=false;editMode=restored;dirty=restored;applyDocumentMode();await loadSide();applyDocumentMode();tree();
}
export async function saveRequirements(){if(!editor||!current)return;try{state('Speichern ...');const out=await editor.save(),r=await api(`${A}document_studio_v4.php?action=save`,{method:'POST',body:JSON.stringify({id:current.id,title:$('v4Title').value,status:current.status,priority:current.priority,relevance:current.relevance,review_status:current.review_status,metadata:current.metadata||{},...out})});current.revision=r.revision;localStorage.removeItem(draftKey());dirty=false;editMode=false;state('Gespeichert');await loadLists();await loadSide();applyDocumentMode()}catch(e){state('Fehler');alert(e.message)}}
function showPicker(title,items,action){pickerAction=action;$('v4PickerTitle').textContent=title;$('v4PickerSearch').value='';$('v4PickerList').dataset.items=JSON.stringify(items);paintPicker(items);$('v4PickerDialog').showModal()}function paintPicker(items){const q=($('v4PickerSearch')?.value||'').toLowerCase();$('v4PickerList').innerHTML=items.filter(x=>`${x.item_key||x.requirement_key||''} ${x.title||''} ${x.status||''}`.toLowerCase().includes(q)).map((x,i)=>`<button type="button" class="picker-item" data-pick-index="${i}" data-pick-id="${esc(x.id)}"><b>${esc(x.item_key||x.requirement_key||x.id)}</b> · ${esc(x.title||'')}<small>${esc(x.status||'')}</small></button>`).join('')||'<p>Keine Treffer.</p>';}
async function pickRelation(role){showPicker(role==='parent'?'Parent auswählen':'Child auswählen',docs.filter(d=>d.id!==current.id),async item=>{await api(`${A}document_links.php?kind=relation`,{method:'POST',body:JSON.stringify({document_id:current.id,related_id:item.id,role})});await loadLists();await loadSide()})}async function pickEntity(type){const d=await api(`${A}document_entity_catalog.php?project_id=${encodeURIComponent(currentProjectId)}&type=${type}`);showPicker(`${type} auswählen`,d.items||[],async item=>{await api(`${A}document_links.php?kind=entity`,{method:'POST',body:JSON.stringify({document_id:current.id,entity_type:type,entity_id:String(item.id),label:`${item.item_key||''} ${item.title}`,relation_type:'relates_to'})});await loadSide()})}
async function pickTest(){const[tc,tr,td]=await Promise.all([api(`${A}document_entity_catalog.php?project_id=${encodeURIComponent(currentProjectId)}&type=test_case`),api(`${A}document_entity_catalog.php?project_id=${encodeURIComponent(currentProjectId)}&type=test_run`),api(`${A}document_entity_catalog.php?project_id=${encodeURIComponent(currentProjectId)}&type=test_document`)]);const items=[...(tc.items||[]),...(tr.items||[]),...(td.items||[])];showPicker('Testfall, Testlauf oder Testdokument auswählen',items,async item=>{const result=item.source_type==='test_run'?(item.status==='passed'?'passed':item.status==='failed'?'failed':item.status==='blocked'?'blocked':'not_run'):'not_run';await api(`${A}document_test_links.php`,{method:'POST',body:JSON.stringify({document_id:current.id,test_case_id:item.source_type==='requirement'?String(item.id):null,test_run_id:item.source_type==='test_run'?String(item.id):null,result,metadata:{source_type:item.source_type,source_document_id:item.source_type==='document'?item.id:null,label:`${item.item_key} ${item.title}`}})});await loadSide();await loadLists()})}
async function createDoc(){const title=$('v4CreateTitle').value.trim();if(!title)throw Error('Titel fehlt.');const d=await api(`${A}document_studio_v4.php?action=create`,{method:'POST',body:JSON.stringify({project_id:currentProjectId,document_type_id:$('v4CreateType').value,template_id:$('v4CreateTemplate').value,title})});$('v4CreateDialog').close();$('v4CreateTitle').value='';await loadLists();await openDoc(d.id)}
export async function loadRequirements(){if(!$('v4')||!currentProjectId)return;try{await loadTools();await loadLists()}catch(e){console.error(e);alert(e.message)}finally{window.hideLoader?.()}}
export function initRequirementEvents(){if(events)return;events=true;document.addEventListener('click',async e=>{try{const d=e.target.closest('[data-doc]');if(d)return openDoc(d.dataset.doc);if(e.target.closest('#v4Edit'))return enterEditMode();if(e.target.closest('#v4CancelEdit'))return cancelEditMode();if(e.target.closest('#v4New'))return $('v4CreateDialog').showModal();if(e.target.closest('#v4CreateConfirm')){e.preventDefault();return createDoc()}const t=e.target.closest('[data-tab]');if(t){tab=t.dataset.tab;renderInspector();return}const pr=e.target.closest('[data-pick-relation]');if(pr)return pickRelation(pr.dataset.pickRelation);const pe=e.target.closest('[data-pick-entity]');if(pe)return pickEntity(pe.dataset.pickEntity);if(e.target.closest('#pickTest'))return pickTest();const chosen=e.target.closest('[data-pick-id]');if(chosen&&pickerAction){const all=JSON.parse($('v4PickerList').dataset.items||'[]'),item=all.find(x=>String(x.id)===chosen.dataset.pickId);if(item){await pickerAction(item);$('v4PickerDialog').close()}return}const rm=e.target.closest('[data-remove]');if(rm&&confirm('Eintrag entfernen?')){const endpoint=rm.dataset.kind==='test'?'document_test_links.php':`document_links.php?kind=${rm.dataset.kind}`;await api(`${A}${endpoint}`,{method:'DELETE',body:JSON.stringify({id:rm.dataset.remove})});await loadSide();await loadLists();return}if(e.target.closest('#reqSave'))return saveRequirements()}catch(x){console.error(x);alert(x.message)}});document.addEventListener('input',e=>{if(e.target.matches('#v4Search'))tree();if(e.target.matches('#v4PickerSearch'))paintPicker(JSON.parse($('v4PickerList').dataset.items||'[]'));if(e.target.matches('#v4Title'))changed()});document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&editor){e.preventDefault();saveRequirements()}});window.addEventListener('beforeunload',e=>{if(editMode&&dirty){e.preventDefault();e.returnValue=''}})}
