<div class="cl-panel">
  <div class="cl-panel-header">
    <div><p class="cl-panel-eyebrow">Dateien · Nachweise · Projektstände</p><h2 class="cl-panel-title">Anhänge & Artefakte</h2></div>
    <button id="btnNewAttachment" type="button" class="cl-button cl-button-primary">📎 Neuer Anhang</button>
  </div>
  <div class="flex gap-3 border-b border-slate-200 p-4">
    <input id="attachmentSearch" type="search" placeholder="Titel, ATT-ID, Kategorie, Dateiname oder Verknüpfung suchen..." class="cl-input flex-1">
    <select id="attachmentCategoryFilter" class="cl-select max-w-60"><option value="">Alle Kategorien</option></select>
  </div>
  <div id="attachmentList" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4"><div class="cl-empty-state">Bitte zuerst ein Projekt auswählen.</div></div>
</div>

<div id="attachmentModal" class="fixed inset-0 z-[350] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
 <form id="attachmentForm" enctype="multipart/form-data" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
  <div class="flex items-start justify-between border-b border-slate-300 bg-slate-100 px-5 py-4">
   <div><p class="cl-panel-eyebrow">Projektartefakt</p><h2 class="text-xl font-extrabold text-blue-950">Anhang hinzufügen</h2></div>
   <button id="attachmentClose" type="button" class="cl-button cl-button-secondary">✕</button>
  </div>
  <div class="flex-1 space-y-5 overflow-y-auto p-5">
   <div id="attachmentContext" class="hidden rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-950"></div>
   <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <label class="cl-label md:col-span-2">Titel<input id="attachmentTitle" name="title" required class="cl-input" placeholder="z.B. Erstes bestücktes PCB"></label>
    <label class="cl-label">Kategorie<select id="attachmentCategory" name="category" class="cl-select"><option>Bild / Foto</option><option>Schaltplan</option><option>PCB-Layout</option><option>Fertigungsdaten</option><option>Mechanik / CAD</option><option>Datenblatt</option><option>Prüfbericht</option><option>Messprotokoll</option><option>Stückliste</option><option>Software-Artefakt</option><option>Meilensteinnachweis</option><option>Sonstiges</option></select></label>
    <label class="cl-label">Version / Revision<input id="attachmentVersion" name="version_label" class="cl-input" placeholder="z.B. Rev. B oder 1.2"></label>
    <label class="cl-label">Status<select id="attachmentStatus" name="status" class="cl-select"><option value="working">Arbeitsstand</option><option value="review">In Prüfung</option><option value="released">Freigegeben</option><option value="obsolete">Veraltet</option></select></label>
    <fieldset class="md:col-span-2 rounded border border-slate-300 p-4"><legend class="px-2 text-xs font-bold uppercase text-blue-950">Quelle</legend>
      <div class="mb-4 flex gap-6"><label><input type="radio" name="storage_type" value="upload" checked> Datei hochladen</label><label><input type="radio" name="storage_type" value="link"> Projektpfad verlinken</label></div>
      <label id="attachmentFileBox" class="cl-label">Datei, maximal 25 MB<input id="attachmentFile" name="file" type="file" class="cl-input"></label>
      <label id="attachmentPathBox" class="cl-label hidden">Projekt- oder Netzwerkpfad<input id="attachmentPath" name="relative_path" class="cl-input" placeholder="hardware/pcb/controller_rev_b.kicad_pcb"></label>
    </fieldset>
    <label class="cl-label md:col-span-2">Beschreibung<textarea id="attachmentDescription" name="description" rows="4" class="cl-textarea"></textarea></label>
   </div>
  </div>
  <div class="flex justify-end gap-3 border-t border-slate-300 bg-slate-100 p-4"><button id="attachmentCancel" type="button" class="cl-button cl-button-secondary">Abbrechen</button><button type="submit" class="cl-button cl-button-primary">Anhang speichern</button></div>
 </form>
</div>
