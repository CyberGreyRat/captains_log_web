<!-- dashboard/views/assets.php -->

<div class="flex h-full flex-col overflow-hidden border border-slate-300 bg-white shadow-sm">

    <div class="flex shrink-0 items-center justify-between border-b border-slate-300 bg-white px-5 py-4">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                Systemkontext · Schutzbedarf · Angriffsfläche
            </p>

            <h2 class="text-2xl font-extrabold text-blue-950">
                Assets
            </h2>
        </div>

        <button
            id="btnNewAsset"
            type="button"
            class="whitespace-nowrap border border-blue-950 bg-blue-950 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-blue-900">
            + Neues Asset
        </button>
    </div>

    <div class="flex shrink-0 gap-2 border-b border-slate-300 bg-slate-50 px-5 py-3">
        <input
            id="assetSearch"
            type="search"
            placeholder="Asset, Kategorie oder Beschreibung suchen"
            class="h-10 flex-1 border border-slate-300 bg-white px-3 text-sm outline-none focus:border-blue-950">

        <select
            id="assetCategoryFilter"
            class="h-10 min-w-[220px] border border-slate-300 bg-white px-3 text-sm font-semibold outline-none focus:border-blue-950">
            <option value="">Alle Asset-Kategorien</option>
        </select>
    </div>

    <div class="min-h-0 flex-1 overflow-auto">
        <table class="w-full min-w-[1000px] border-collapse text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-slate-300 bg-[#eef2f6] text-blue-950">
                <tr>
                    <th class="w-[120px] px-3 py-3 text-xs font-extrabold uppercase">
                        Key
                    </th>

                    <th class="min-w-[300px] px-3 py-3 text-xs font-extrabold uppercase">
                        Asset
                    </th>

                    <th class="w-[220px] px-3 py-3 text-xs font-extrabold uppercase">
                        Kategorie
                    </th>

                    <th class="w-[220px] px-3 py-3 text-xs font-extrabold uppercase">
                        Physischer Zugang
                    </th>

                    <th class="w-[170px] px-3 py-3 text-xs font-extrabold uppercase">
                        Prüfstatus
                    </th>

                    <th class="w-[120px] px-3 py-3 text-right text-xs font-extrabold uppercase">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="assetTableBody" class="divide-y divide-slate-200">
                <tr>
                    <td colspan="6" class="p-8 text-center italic text-slate-400">
                        Bitte zuerst ein Projekt auswählen.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="flex h-9 shrink-0 items-center justify-between border-t border-slate-300 bg-[#eef2f6] px-4 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
        <span id="assetResultCount">0 Assets</span>
        <span>Schützenswerte Güter des Projekts</span>
    </div>
</div>


<!-- Asset-Formular: einheitlicher Captain's-Log-Modal-Standard -->
<div id="assetModal" class="cl-modal-overlay hidden">
    <form id="assetForm" class="mx-auto flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded bg-white shadow-2xl">

        <div class="cl-modal-header shrink-0">
            <div>
                <p class="cl-panel-eyebrow">Systemkontext</p>
                <h2 id="assetModalTitle" class="cl-modal-title">Asset bearbeiten</h2>
            </div>

            <button id="assetModalClose" type="button"
                class="cl-button cl-button-secondary min-h-0 rounded-md px-3 py-2"
                aria-label="Fenster schließen" title="Fenster schließen">
                ✕
            </button>
        </div>

        <input type="hidden" id="asset_id">

        <div class="cl-modal-body min-h-0 flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

                <label class="cl-label lg:col-span-2">
                    Name des Assets
                    <input id="asset_title" required maxlength="255"
                        class="cl-input mt-2 min-h-11 w-full rounded-md"
                        placeholder="Bezeichnung des schützenswerten Assets">
                </label>

                <label class="cl-label lg:col-span-2">
                    Beschreibung
                    <textarea id="asset_description" rows="5"
                        class="cl-textarea mt-2 min-h-32 w-full rounded-md"
                        placeholder="Asset, Funktion und Bedeutung im System beschreiben"></textarea>
                </label>

                <fieldset class="cl-fieldset rounded-md border border-slate-300 p-5">
                    <legend class="cl-legend px-2 text-xs font-extrabold uppercase tracking-wide text-blue-950">
                        Klassifikation
                    </legend>

                    <div class="grid gap-5">
                        <label class="cl-label">
                            Asset-Kategorie
                            <select id="asset_type" class="cl-select mt-2 min-h-11 w-full rounded-md">
                                <option value="">-- Kategorie wählen --</option>
                                <optgroup label="Logische Assets">
                                    <option value="Daten / Informationen">Daten / Informationen</option>
                                    <option value="Geheimnis / Key">Geheimnis / Key</option>
                                    <option value="Code / Firmware">Code / Firmware</option>
                                    <option value="Service / Funktion">Service / Funktion</option>
                                </optgroup>
                                <optgroup label="Physische Assets">
                                    <option value="Elektronik / PCB">Elektronik / PCB</option>
                                    <option value="Physisches Gehäuse / Mechanik">Physisches Gehäuse / Mechanik</option>
                                    <option value="Schnittstelle / HMI">Schnittstelle / HMI</option>
                                    <option value="Infrastruktur / Befestigung">Infrastruktur / Befestigung</option>
                                </optgroup>
                            </select>
                        </label>

                        <label class="cl-label">
                            Zugänglichkeit / Exposition
                            <select id="asset_exposure" class="cl-select mt-2 min-h-11 w-full rounded-md">
                                <option value="">-- Zugang wählen --</option>
                                <option value="Öffentlich zugänglich (Public)">Öffentlich zugänglich</option>
                                <option value="Eingeschränkter Zugang (Restricted)">Eingeschränkter Zugang</option>
                                <option value="Streng gesichert (Secure)">Streng gesichert</option>
                                <option value="Isoliert im Gehäuse (Internal)">Isoliert im Gehäuse</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="cl-fieldset rounded-md border border-slate-300 p-5">
                    <legend class="cl-legend px-2 text-xs font-extrabold uppercase tracking-wide text-blue-950">
                        Bewertung
                    </legend>

                    <div class="grid gap-5">
                        <label class="cl-label">
                            Prüfstatus
                            <select id="asset_review_status" class="cl-select mt-2 min-h-11 w-full rounded-md">
                                <option value="Neu">Neu</option>
                                <option value="Wartet auf Überprüfung">Wartet auf Überprüfung</option>
                                <option value="Geprüft & Freigegeben">Geprüft & Freigegeben</option>
                                <option value="Abgelehnt">Abgelehnt</option>
                            </select>
                        </label>

                        <div class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                            <strong class="block text-blue-950">Hinweis</strong>
                            Der Schutzbedarf sollte die Bedeutung des Assets, mögliche Angriffsflächen und erforderliche Schutzmaßnahmen nachvollziehbar beschreiben.
                        </div>
                    </div>
                </fieldset>

                <label class="cl-label lg:col-span-2">
                    Begründung / Schutzbedarf
                    <textarea id="asset_rationale" rows="6"
                        class="cl-textarea mt-2 min-h-40 w-full rounded-md"
                        placeholder="Warum ist dieses Asset schützenswert? Welche Auswirkungen hätte Verlust, Manipulation oder Offenlegung?"></textarea>
                </label>
            </div>
        </div>

        <div class="cl-modal-footer flex shrink-0 justify-end gap-3">
            <button id="assetCancel" type="button"
                class="cl-button cl-button-secondary rounded-md">
                Abbrechen
            </button>

            <button type="submit"
                class="cl-button cl-button-primary rounded-md">
                Asset speichern
            </button>
        </div>
    </form>
</div>
