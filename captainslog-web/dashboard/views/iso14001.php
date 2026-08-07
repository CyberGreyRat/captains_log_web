<!-- dashboard/views/iso14001.php -->
<div class="flex flex-col h-full overflow-hidden p-6 bg-slate-50">
    <div class="flex justify-between items-center mb-6 shrink-0">
        <div>
            <h2 class="text-2xl font-bold text-emerald-900">ISO 14001 - Umweltmanagement</h2>
            <p class="text-sm text-slate-500">Lebenszyklus-Analyse, Umweltaspekte und Maßnahmen</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.openIsoImportModal()"
                class="bg-white border border-emerald-300 text-emerald-700 px-4 py-2 font-bold shadow-sm hover:bg-emerald-50 transition rounded">
                Aus anderem Projekt importieren
            </button>
            <button onclick="window.openIsoModal()"
                class="bg-emerald-600 px-4 py-2 font-bold text-white shadow-md hover:bg-emerald-700 transition rounded">
                + Neuer Umweltaspekt
            </button>
        </div>
    </div>

    <!-- Tabelle der Umweltaspekte -->
    <div class="flex-1 overflow-auto bg-white border border-slate-200 shadow-sm rounded">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-emerald-50 text-emerald-900 uppercase font-bold border-b border-emerald-200 text-xs">
                <tr>
                    <th class="p-3 w-1/3">Umweltaspekt</th>
                    <th class="p-3 w-1/2">Auswirkung & Maßnahme</th>
                    <th class="p-3 w-28 text-center">Relevanz</th>
                    <th class="p-3 w-32 text-center">Status</th>
                    <th class="p-3 w-24 text-right">Aktionen</th>
                </tr>
            </thead>
           <tbody id="isoTableBody" class="divide-y divide-slate-100">
                <!-- Der einheitliche System-Ladebalken -->
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500 italic">
                        <div class="inline-flex items-center gap-2">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Daten werden geladen...
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- SPEZIAL-MODAL: ISO 14001 BEARBEITEN (Schlank & Fokussiert) -->
<div id="modalIsoEdit"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formIsoEdit" class="w-full max-w-2xl bg-white p-8 rounded shadow-2xl space-y-5">
        <h2 class="text-xl font-bold text-emerald-900 border-b border-emerald-100 pb-2">Umweltaspekt definieren</h2>
        <input type="hidden" id="iso_id">
        <!-- NEU: Excel-Vorlagen Lader -->
        <div class="bg-emerald-50 border border-emerald-200 p-4 -mx-8 -mt-2 mb-6">
            <label class="block text-sm font-bold text-emerald-900 mb-1">Aus Norm-Katalog laden (Standard Excel)</label>
            <select id="iso_template_selector"
                class="w-full border border-emerald-300 p-2 font-bold text-emerald-800 bg-white outline-none focus:border-emerald-500 shadow-sm cursor-pointer">
                <option value="">-- Frei ausfüllen oder Excel-Vorlage wählen --</option>
            </select>
        </div>


        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Lebenszyklus-Phase</label>
                <select id="iso_phase"
                    class="w-full border border-slate-300 p-2 font-medium bg-slate-50 outline-none focus:border-emerald-500">
                    <option value="Entwurf">Entwurf & Design</option>
                    <option value="Entwicklung">Entwicklung</option>
                    <option value="Rohstoffe">Rohstoffe & Beschaffung</option>
                    <option value="Produktion">Produktion & Bestückung</option>
                    <option value="Lieferung">Verpackung & Lieferung</option>
                    <option value="Installation/Wartung">Installation & Wartung</option>
                    <option value="Betrieb">Betrieb & Verwendung</option>
                    <option value="EOL">End of Life (Entsorgung/Recycling)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Relevanz / Signifikanz</label>
                <select id="iso_relevance"
                    class="w-full border border-slate-300 p-2 font-medium bg-slate-50 outline-none focus:border-emerald-500">
                    <option value="Gering">Gering (Beobachten)</option>
                    <option value="Mittel" selected>Mittel (Steuern)</option>
                    <option value="Signifikant" class="font-bold text-red-600">Signifikant (Zwingender Handlungsbedarf!)
                    </option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Titel des Umweltaspekts (Ursache)</label>
            <input id="iso_title" required
                class="w-full border border-slate-300 p-2 font-semibold outline-none focus:border-emerald-500"
                placeholder="z.B. Standby-Verbrauch der Leiterplatte">
        </div>

        <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Umweltauswirkung (Wirkung)</label>
            <!-- Smarte Ausfüllhilfe / Norm-Kategorien -->
            <select id="iso_impact_helper"
                onchange="document.getElementById('iso_impact').value = this.value; this.value='';"
                class="w-full border border-slate-300 p-2 text-sm bg-emerald-50 text-emerald-800 mb-2 font-medium cursor-pointer">
                <option value="">-- Textvorlage aus Norm wählen (optional) --</option>
                <option value="Veränderung des Klimas (durch hohen Energie-/CO2-Verbrauch)">Klimaänderung / CO2-Ausstoß
                </option>
                <option value="Ressourcenerschöpfung (durch Verbrauch seltener Erden/Metalle)">Ressourcenerschöpfung
                </option>
                <option value="Luftverschmutzung (durch Emissionen/Transport)">Luftverschmutzung</option>
                <option value="Gewässer- und Bodenverschmutzung (durch Chemikalien/Abfall)">Gewässer- &
                    Bodenverschmutzung</option>
                <option value="Abfallerzeugung (durch nicht recycelbare Materialien)">Abfallerzeugung</option>
            </select>
            <textarea id="iso_impact" required rows="2"
                class="w-full border border-slate-300 p-2 font-normal outline-none focus:border-emerald-500"></textarea>
        </div>

        <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Getroffene Maßnahme (Mitigation)</label>
            <textarea id="iso_measure" required rows="3"
                class="w-full border border-slate-300 p-2 font-normal outline-none focus:border-emerald-500"
                placeholder="Was tun wir konkret dagegen? z.B. Einsatz von Deep-Sleep Modus..."></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
            <button type="button" onclick="document.getElementById('modalIsoEdit').classList.add('hidden')"
                class="border px-4 py-2 hover:bg-slate-50 font-bold transition rounded text-slate-600">Abbrechen</button>
            <button type="submit"
                class="bg-emerald-600 px-6 py-2 font-bold text-white shadow hover:bg-emerald-700 transition rounded">Speichern</button>
        </div>
    </form>
</div>

<!-- MODAL: PROJEKT IMPORT -->
<div id="modalIsoImport"
    class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
    <form id="formIsoImport" class="w-full max-w-md bg-white p-6 rounded shadow-2xl">
        <h2 class="text-lg font-bold text-emerald-900 border-b pb-2 mb-4">Aus altem Projekt importieren</h2>
        <p class="text-xs text-slate-500 mb-4">Kopiert alle Umweltaspekte eines vorherigen Projekts in dieses Projekt.
            Du kannst sie danach individuell anpassen.</p>

        <label class="block text-sm font-bold text-slate-700 mb-2">Quell-Projekt wählen</label>
        <select id="iso_import_source" required
            class="w-full border border-slate-300 p-2 bg-slate-50 font-medium"></select>

        <div class="flex justify-end gap-3 mt-6">
            <button type="button" onclick="document.getElementById('modalIsoImport').classList.add('hidden')"
                class="border px-4 py-2 hover:bg-slate-50 text-sm font-bold transition rounded">Abbrechen</button>
            <button type="submit"
                class="bg-emerald-600 px-4 py-2 font-bold text-white text-sm shadow hover:bg-emerald-700 transition rounded">Importieren</button>
        </div>
    </form>
</div>

<script type="module" src="js/iso14001.js"></script>