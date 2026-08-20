<!-- dashboard/views/sbom.php -->
<div class="cl-panel">
    <div class="cl-panel-header">
        <div><p class="cl-panel-eyebrow">Komponenten · Versionen · Lizenzen</p><h2 class="cl-panel-title">Software Bill of Materials</h2></div>
    </div>

    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">
        <div class="cl-kpi"><div class="cl-kpi-label">Komponentenbestand</div><div class="mt-1 text-sm font-semibold text-slate-700">Alle erkannten Softwarepakete und Abhängigkeiten.</div></div>
        <div class="cl-kpi border-l-amber-500"><div class="cl-kpi-label">Lizenzprüfung</div><div class="mt-1 text-sm font-semibold text-slate-700">Fehlende oder ungeklärte Lizenzangaben sichtbar machen.</div></div>
        <div class="cl-kpi border-l-emerald-500"><div class="cl-kpi-label">SPDX</div><div class="mt-1 text-sm font-semibold text-slate-700">Metadaten, Erstellungszeitpunkt und Scanner nachvollziehen.</div></div>
    </div>

    <div id="sbomContainer" class="min-h-0 flex-1 overflow-auto p-5">
        <div class="cl-empty-state">Bitte zuerst ein Projekt auswählen.</div>
    </div>

    <div class="cl-panel-footer"><span>Software Bill of Materials</span><span>Pakete · Versionen · Lizenzen · Lieferanten</span></div>
</div>
