<div class="cl-panel flex h-full flex-col overflow-hidden">
    <div class="cl-panel-header">
        <div><p class="cl-panel-eyebrow">Dokumentation · Auswahl · Layout</p><h2 class="cl-panel-title">Berichte & Exporte</h2></div>
        <button id="btnReportLayout" type="button" class="cl-button cl-button-secondary">Logo & Layout</button>
    </div>
    <div class="flex-1 overflow-y-auto bg-slate-50 p-6">
        <div class="grid gap-5 md:grid-cols-3">
            <button type="button" onclick="window.openExportConfig('specification')" class="cl-card p-5 text-left"><span class="cl-badge cl-badge-info">Dokument</span><h3 class="mt-3 text-lg font-extrabold text-blue-950">Pflichtenheft</h3><p class="mt-2 text-sm text-slate-600">PDF oder Word im einheitlichen Projektlayout.</p></button>
            <button type="button" onclick="window.openExportConfig('status_report')" class="cl-card p-5 text-left"><span class="cl-badge cl-badge-neutral">Fortschritt</span><h3 class="mt-3 text-lg font-extrabold text-blue-950">Aufgaben-Protokoll</h3><p class="mt-2 text-sm text-slate-600">Aufgaben wählen, Checklisten und Traceability steuern.</p></button>
            <button type="button" onclick="window.openExportConfig('issue_report')" class="cl-card p-5 text-left"><span class="cl-badge cl-badge-warning">Qualität</span><h3 class="mt-3 text-lg font-extrabold text-blue-950">Issue-Report</h3><p class="mt-2 text-sm text-slate-600">Issues wählen, Kommunikation und interne Inhalte steuern.</p></button>
        </div>
    </div>
</div>

<div id="modalExportConfig" class="cl-modal-overlay hidden">
<form id="formExport" class="cl-modal max-w-5xl">
    <div class="cl-modal-header"><div><p class="cl-panel-eyebrow">Exportdesigner</p><h2 id="exportTitle" class="cl-modal-title">Bericht</h2></div><button type="button" class="cl-button cl-button-secondary" data-report-close>✕</button></div>
    <input id="export_type" type="hidden">
    <div class="cl-modal-body space-y-5">
        <div class="grid gap-4 md:grid-cols-3">
            <label class="cl-label">Format<select id="export_format" class="cl-select"><option value="pdf">PDF</option><option value="docx">Word DOCX</option></select></label>
            <label class="cl-label">Version<input id="export_version" value="1.0.0" class="cl-input"></label>
            <label class="cl-label">Statusfilter<select id="export_status" class="cl-select"><option value="all">Alle</option><option value="open_only">Nur offene</option><option value="closed_only">Nur erledigte</option></select></label>
        </div>
        <div id="specificationMeta" class="hidden grid gap-4 md:grid-cols-3">
            <label class="cl-label">Bearbeiter<input id="export_author" class="cl-input"></label>
            <label class="cl-label">Auftraggeber<input id="export_customer" class="cl-input"></label>
            <label class="cl-label">Projektleiter<input id="export_manager" class="cl-input"></label>
        </div>
        <fieldset id="reportSelectionBox" class="cl-fieldset"><legend id="reportSelectionTitle" class="cl-legend">Auswahl</legend><div class="cl-fieldset-body"><div class="mb-3 flex gap-2"><button id="reportSelectAll" type="button" class="cl-button cl-button-secondary">Alle</button><button id="reportSelectNone" type="button" class="cl-button cl-button-secondary">Keine</button></div><div id="reportEntitySelection" class="grid max-h-56 gap-2 overflow-y-auto md:grid-cols-2"></div></div></fieldset>
        <fieldset id="reportContentBox" class="cl-fieldset"><legend class="cl-legend">Inhalte</legend><div id="reportContentOptions" class="cl-fieldset-body grid gap-3 md:grid-cols-3"></div></fieldset>
    </div>
    <div class="cl-modal-footer"><button type="button" class="cl-button cl-button-secondary" data-report-close>Abbrechen</button><button id="exportSubmitButton" class="cl-button cl-button-primary">Dokument generieren</button></div>
</form>
</div>

<div id="modalReportLayout" class="cl-modal-overlay hidden">
<form id="formReportLayout" class="cl-modal max-w-2xl">
    <div class="cl-modal-header"><div><p class="cl-panel-eyebrow">Für alle Berichte</p><h2 class="cl-modal-title">Logo, Kopf- und Fußzeile</h2></div><button type="button" class="cl-button cl-button-secondary" data-layout-close>✕</button></div>
    <div class="cl-modal-body space-y-4">
        <label class="cl-label">Logo, maximal 2 MB<input id="reportLogo" type="file" accept=".png,.jpg,.jpeg,.webp" class="cl-input"></label><div id="reportLogoState" class="text-xs text-slate-500"></div>
        <label class="cl-label">Firmenname<input id="reportCompany" class="cl-input"></label>
        <label class="cl-label">Kopfzeile<input id="reportHeader" class="cl-input" placeholder="{company} | {project} | {report}"></label>
        <label class="cl-label">Fußzeile<input id="reportFooter" class="cl-input" placeholder="{classification} | {date} | Seite {page} von {pages}"></label>
        <div class="grid gap-4 md:grid-cols-2"><label class="cl-label">Akzentfarbe<input id="reportColor" type="color" value="#1f4e79" class="cl-input h-11"></label><label class="cl-label">Klassifizierung<input id="reportClassification" class="cl-input"></label></div>
        <p class="text-xs text-slate-500">Platzhalter: {company}, {project}, {report}, {date}, {page}, {pages}</p>
    </div>
    <div class="cl-modal-footer"><button type="button" class="cl-button cl-button-secondary" data-layout-close>Abbrechen</button><button class="cl-button cl-button-primary">Speichern</button></div>
</form>
</div>
