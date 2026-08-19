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


<!-- Asset-Formular -->
<div
    id="assetModal"
    class="fixed inset-0 z-[220] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

    <form
        id="assetForm"
        class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden border border-slate-400 bg-white shadow-2xl">

        <div class="flex items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    Systemkontext
                </p>

                <h2 id="assetModalTitle" class="text-xl font-extrabold text-blue-950">
                    Asset bearbeiten
                </h2>
            </div>

            <button
                id="assetModalClose"
                type="button"
                class="border border-slate-300 bg-white p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>
        </div>

        <input type="hidden" id="asset_id">

        <div class="flex-1 space-y-5 overflow-y-auto p-6">

            <label class="block text-sm font-bold text-slate-700">
                Name des Assets

                <input
                    id="asset_title"
                    required
                    maxlength="255"
                    class="mt-1 w-full border border-slate-400 p-2.5 text-base font-semibold outline-none focus:border-blue-950">
            </label>

            <label class="block text-sm font-bold text-slate-700">
                Beschreibung

                <textarea
                    id="asset_description"
                    rows="4"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal outline-none focus:border-blue-950"></textarea>
            </label>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <label class="block text-sm font-bold text-slate-700">
                    Asset-Kategorie

                    <select
                        id="asset_type"
                        class="mt-1 w-full border border-slate-300 bg-white p-2 outline-none focus:border-blue-950">

                        <option value="">-- Kategorie wählen --</option>

                        <optgroup label="Digital und IT">
                            <option value="Daten / Informationen">
                                Daten / Informationen
                            </option>

                            <option value="Geheimnis / Key">
                                Zertifikate / Krypto-Keys
                            </option>

                            <option value="Code / Firmware">
                                Code / Firmware / Betriebssystem
                            </option>

                            <option value="Service / Funktion">
                                Service / API / Update-Dienst
                            </option>
                        </optgroup>

                        <optgroup label="Hardware und Mechanik">
                            <option value="Elektronik / PCB">
                                Elektronik / PCB / Controller
                            </option>

                            <option value="Physisches Gehäuse / Mechanik">
                                Gehäuse / Mechanik
                            </option>

                            <option value="Schnittstelle / HMI">
                                Schnittstelle / HMI
                            </option>

                            <option value="Infrastruktur / Befestigung">
                                Infrastruktur / Befestigung
                            </option>
                        </optgroup>
                    </select>
                </label>

                <label class="block text-sm font-bold text-slate-700">
                    Physischer Zugang

                    <select
                        id="asset_exposure"
                        class="mt-1 w-full border border-slate-300 bg-white p-2 outline-none focus:border-blue-950">

                        <option value="">-- Zugang wählen --</option>
                        <option value="Öffentlich zugänglich (Public)">
                            Öffentlich zugänglich
                        </option>
                        <option value="Eingeschränkter Zugang (Restricted)">
                            Eingeschränkter Zugang
                        </option>
                        <option value="Streng gesichert (Secure)">
                            Streng gesichert
                        </option>
                        <option value="Isoliert im Gehäuse (Internal)">
                            Isoliert im Gehäuse
                        </option>
                    </select>
                </label>
            </div>

            <label class="block text-sm font-bold text-slate-700">
                Begründung / Schutzbedarf

                <textarea
                    id="asset_rationale"
                    rows="3"
                    placeholder="Warum ist dieses Asset schützenswert?"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal outline-none focus:border-blue-950"></textarea>
            </label>

            <label class="block text-sm font-bold text-slate-700">
                Prüfstatus

                <select
                    id="asset_review_status"
                    class="mt-1 w-full border border-slate-300 bg-white p-2 outline-none focus:border-blue-950">

                    <option value="Neu">Neu</option>
                    <option value="Wartet auf Überprüfung">
                        Wartet auf Überprüfung
                    </option>
                    <option value="Geprüft & Freigegeben">
                        Geprüft & Freigegeben
                    </option>
                    <option value="Abgelehnt">Abgelehnt</option>
                </select>
            </label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-300 bg-slate-50 px-6 py-4">
            <button
                id="assetCancel"
                type="button"
                class="border border-slate-300 bg-white px-5 py-2 text-xs font-bold uppercase hover:bg-slate-100">
                Abbrechen
            </button>

            <button
                type="submit"
                class="bg-blue-950 px-6 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">
                Asset speichern
            </button>
        </div>
    </form>
</div>