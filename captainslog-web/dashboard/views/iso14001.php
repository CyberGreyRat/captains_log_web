<!-- dashboard/views/iso14001.php -->
<div class="cl-panel">
    <div class="cl-panel-header">
        <div>
            <p class="cl-panel-eyebrow">Lebenszyklus · Umweltaspekte · Maßnahmen</p>
            <h2 class="cl-panel-title">ISO 14001 Umweltmanagement</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.openIsoImportModal()" class="cl-button cl-button-secondary">Aus Projekt importieren</button>
            <button type="button" onclick="window.openIsoModal()" class="cl-button cl-button-primary">+ Neuer Umweltaspekt</button>
        </div>
    </div>

    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">
        <div class="cl-kpi border-l-emerald-500"><div class="cl-kpi-label">Lebenszyklus</div><div class="mt-1 text-sm font-semibold text-slate-700">Von Entwurf und Beschaffung bis Entsorgung und Recycling.</div></div>
        <div class="cl-kpi border-l-blue-500"><div class="cl-kpi-label">Umweltwirkung</div><div class="mt-1 text-sm font-semibold text-slate-700">Klima, Ressourcen, Luft, Boden, Gewässer und Abfall.</div></div>
        <div class="cl-kpi border-l-amber-500"><div class="cl-kpi-label">Maßnahmen</div><div class="mt-1 text-sm font-semibold text-slate-700">Konkrete Vermeidung, Reduzierung und Überwachung.</div></div>
    </div>

    <div class="cl-table-container">
        <table class="cl-table min-w-[1100px]">
            <thead><tr><th class="min-w-[310px]">Umweltaspekt</th><th class="min-w-[480px]">Auswirkung & Maßnahme</th><th class="w-[130px] text-center">Relevanz</th><th class="w-[150px] text-center">Status</th><th class="w-[135px] text-right">Aktionen</th></tr></thead>
            <tbody id="isoTableBody"><tr><td colspan="5" class="cl-empty-state">Daten werden geladen...</td></tr></tbody>
        </table>
    </div>

    <div class="cl-panel-footer"><span>ISO 14001 Umweltmanagement</span><span>Lebenszyklus · Relevanz · Auswirkungen · Maßnahmen</span></div>
</div>

<div id="modalIsoEdit" class="cl-modal-overlay hidden">
    <form id="formIsoEdit" class="cl-modal max-w-3xl">
        <div class="cl-modal-header">
            <div><p class="cl-panel-eyebrow">Umweltmanagement</p><h2 class="cl-modal-title">Umweltaspekt definieren</h2></div>
            <button type="button" onclick="document.getElementById('modalIsoEdit').classList.add('hidden')" class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" aria-label="Fenster schließen">✕</button>
        </div>
        <input id="iso_id" type="hidden">
        <div class="cl-modal-body">
            <fieldset class="cl-fieldset border-emerald-200 bg-emerald-50/40">
                <legend class="cl-legend bg-emerald-50 text-emerald-900">Norm-Katalog</legend>
                <div class="cl-fieldset-body"><label class="cl-label">Aus ISO-Vorlage laden<select id="iso_template_selector" class="cl-select border-emerald-300 font-semibold"><option value="">-- Frei ausfüllen oder Vorlage wählen --</option></select><span class="cl-help">Übernimmt vordefinierte Angaben aus dem Umweltkatalog.</span></label></div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Einordnung</legend>
                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">
                    <label class="cl-label">Lebenszyklusphase<select id="iso_phase" class="cl-select"><option value="Entwurf">Entwurf & Design</option><option value="Entwicklung">Entwicklung</option><option value="Rohstoffe">Rohstoffe & Beschaffung</option><option value="Produktion">Produktion & Bestückung</option><option value="Lieferung">Verpackung & Lieferung</option><option value="Installation/Wartung">Installation & Wartung</option><option value="Betrieb">Betrieb & Verwendung</option><option value="EOL">End of Life / Recycling</option></select></label>
                    <label class="cl-label">Relevanz / Signifikanz<select id="iso_relevance" class="cl-select font-semibold"><option value="Gering">Gering: beobachten</option><option value="Mittel" selected>Mittel: steuern</option><option value="Signifikant">Signifikant: Handlungsbedarf</option></select></label>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Umweltaspekt</legend>
                <div class="cl-fieldset-body"><label class="cl-label">Titel / Ursache<input id="iso_title" required maxlength="255" placeholder="z.B. Standby-Verbrauch der Leiterplatte" class="cl-input text-base font-semibold"></label></div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Umweltauswirkung</legend>
                <div class="cl-fieldset-body space-y-4">
                    <label class="cl-label">Textvorlage<select id="iso_impact_helper" class="cl-select border-emerald-200 bg-emerald-50" onchange="document.getElementById('iso_impact').value=this.value;this.value='';"><option value="">-- Optionale Textvorlage wählen --</option><option value="Veränderung des Klimas (durch hohen Energie-/CO2-Verbrauch)">Klimaänderung / CO₂-Ausstoß</option><option value="Ressourcenerschöpfung (durch Verbrauch seltener Erden/Metalle)">Ressourcenerschöpfung</option><option value="Luftverschmutzung (durch Emissionen/Transport)">Luftverschmutzung</option><option value="Gewässer- und Bodenverschmutzung (durch Chemikalien/Abfall)">Gewässer- und Bodenverschmutzung</option><option value="Abfallerzeugung (durch nicht recycelbare Materialien)">Abfallerzeugung</option></select></label>
                    <label class="cl-label">Beschreibung der Umweltwirkung<textarea id="iso_impact" required rows="4" class="cl-textarea"></textarea></label>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Maßnahme</legend>
                <div class="cl-fieldset-body"><label class="cl-label">Getroffene Maßnahme / Mitigation<textarea id="iso_measure" required rows="5" placeholder="Was wird konkret zur Vermeidung oder Reduzierung umgesetzt?" class="cl-textarea"></textarea></label></div>
            </fieldset>
        </div>
        <div class="cl-modal-footer"><button type="button" onclick="document.getElementById('modalIsoEdit').classList.add('hidden')" class="cl-button cl-button-secondary">Abbrechen</button><button type="submit" class="cl-button cl-button-primary">Umweltaspekt speichern</button></div>
    </form>
</div>

<div id="modalIsoImport" class="cl-modal-overlay hidden z-[230]">
    <form id="formIsoImport" class="cl-modal max-w-lg">
        <div class="cl-modal-header"><div><p class="cl-panel-eyebrow">Datenübernahme</p><h2 class="cl-modal-title">Umweltaspekte importieren</h2></div><button type="button" onclick="document.getElementById('modalIsoImport').classList.add('hidden')" class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" aria-label="Fenster schließen">✕</button></div>
        <div class="cl-modal-body"><div class="mb-5 rounded-md border border-blue-200 bg-blue-50 p-4 text-xs leading-5 text-blue-900">Alle Umweltaspekte des Quellprojekts werden kopiert und können danach angepasst werden.</div><label class="cl-label">Quellprojekt<select id="iso_import_source" required class="cl-select font-semibold"><option value="">-- Quellprojekt auswählen --</option></select></label></div>
        <div class="cl-modal-footer"><button type="button" onclick="document.getElementById('modalIsoImport').classList.add('hidden')" class="cl-button cl-button-secondary">Abbrechen</button><button type="submit" class="cl-button cl-button-primary">Importieren</button></div>
    </form>
</div>
