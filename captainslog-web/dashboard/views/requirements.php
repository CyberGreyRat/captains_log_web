<!-- dashboard/views/requirements.php -->

<div class="cl-panel">

    <!-- =====================================================
         KOPFBEREICH
    ====================================================== -->
    <div class="cl-panel-header">

        <div>
            <p class="cl-panel-eyebrow">
                Requirements · Traceability · Verification
            </p>

            <h2 id="reqMainTitle" class="cl-panel-title">
                Anforderungen
            </h2>
        </div>

        <button id="new" type="button" class="cl-button cl-button-primary">

            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neues Element
        </button>
    </div>


    <!-- =====================================================
         SUCHE & FILTERLEISTE
    ====================================================== -->
    <div class="shrink-0 border-b border-slate-200 bg-slate-50 px-5 py-3 flex flex-col gap-4">

        <!-- NEU: Volltextsuche -->
        <div class="relative w-full">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
            </svg>
            <input id="reqSearchInput" type="search" autocomplete="off"
                placeholder="Anforderungen durchsuchen (ID, Titel, Beschreibung)..."
                class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
        </div>

        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-wide text-blue-950">
                    Anforderungstypen
                </div>
                <div class="mt-0.5 text-[11px] text-slate-500">
                    Wähle aus, welche Elemente in der Baumstruktur angezeigt werden.
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button id="selectAllFilters" type="button"
                    class="cl-button cl-button-secondary min-h-0 px-3 py-1.5 text-[10px]">
                    Alle auswählen
                </button>
                <button id="clearAllFilters" type="button"
                    class="cl-button cl-button-secondary min-h-0 px-3 py-1.5 text-[10px]">
                    Auswahl löschen
                </button>
            </div>
        </div>

        <div id="reqFilterCheckboxes" class="flex flex-wrap gap-2">
            <?php
            $filterTypes = ['USR', 'SYS', 'SEC', 'SRS', 'HRS', 'SWC', 'TC', 'TR'];
            foreach ($filterTypes as $type):
                ?>
                <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">
                    <input type="checkbox" value="<?= htmlspecialchars($type) ?>" checked
                        class="h-3.5 w-3.5 cursor-pointer rounded border-slate-300 text-blue-950 focus:ring-blue-900">
                    <span class="font-mono text-[11px] font-extrabold text-slate-700">
                        <?= htmlspecialchars($type) ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- =====================================================
         HAUPTBEREICH
    ====================================================== -->
    <div class="grid min-h-0 flex-1 gap-4 overflow-hidden p-4 lg:grid-cols-[360px_minmax(0,1fr)]">

        <!-- Baumstruktur -->
        <aside class="cl-card flex h-full min-h-0 flex-col overflow-hidden">

            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-[#eef2f6] px-4 py-3">

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wide text-blue-950">
                        Struktur
                    </h3>

                    <p class="mt-0.5 text-[10px] text-slate-500">
                        Anforderungen und Beziehungen
                    </p>
                </div>

                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </div>

            <div id="items" class="min-h-0 flex-1 overflow-y-auto p-3 text-sm text-slate-500">

                <div class="cl-empty-state">
                    Bitte zuerst ein Projekt auswählen.
                </div>
            </div>
        </aside>


        <!-- Detailansicht -->
        <article id="detail" class="cl-card relative h-full min-h-0 overflow-y-auto p-6">

            <div class="flex h-full min-h-[300px] flex-col items-center justify-center text-center text-slate-400">

                <div
                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-lg border border-slate-200 bg-slate-50">

                    <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                            d="M9 12h6m-6 4h6M9 8h6M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z">
                        </path>
                    </svg>
                </div>

                <p class="font-semibold text-slate-500">
                    Kein Element ausgewählt
                </p>

                <p class="mt-1 max-w-sm text-sm">
                    Wähle links eine Anforderung aus, um Details,
                    Beziehungen, Akzeptanzkriterien und Historie anzuzeigen.
                </p>
            </div>
        </article>
    </div>


    <!-- =====================================================
         FUSSLEISTE
    ====================================================== -->
    <div class="cl-panel-footer">

        <span>
            Requirements Management
        </span>

        <span>
            Anforderungen · Testfälle · Traceability
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: ANFORDERUNG ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="reqModal" class="cl-modal-overlay hidden">

    <form id="reqForm" class="cl-modal max-w-3xl">

        <!-- Modal-Kopf -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Requirements Engineering
                </p>

                <h2 id="reqHeading" class="cl-modal-title">
                    Neues Element anlegen
                </h2>
            </div>

            <button type="button" onclick="document.getElementById('reqModal').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>


        <!-- Modal-Inhalt -->
        <div class="cl-modal-body">

            <!-- Basisinformationen -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Basisinformationen
                </legend>

                <div class="cl-fieldset-body space-y-5">

                    <!-- Typ -->
                    <label class="cl-label">
                        Typ

                        <select id="type" class="cl-select font-medium">

                            <optgroup label="System und Architektur">
                                <option value="USR">
                                    User Requirement (USR)
                                </option>

                                <option value="SYS">
                                    System Requirement (SYS)
                                </option>

                                <option value="SEC">
                                    Security & Cyber Resilience (SEC)
                                </option>

                                <option value="SRS">
                                    Software Requirement (SRS)
                                </option>

                                <option value="HRS">
                                    Hardware Requirement (HRS)
                                </option>

                                <option value="SWC">
                                    Komponente / Modul (SWC)
                                </option>
                            </optgroup>

                            <optgroup label="Verifikation und Test">
                                <option value="TC">
                                    Test Case / Specification (TC)
                                </option>

                                <option value="TR">
                                    Test Result / Protocol (TR)
                                </option>
                            </optgroup>
                        </select>
                    </label>

                    <!-- Titel -->
                    <label class="cl-label">
                        Titel / Name

                        <input id="title" type="text" required maxlength="255"
                            placeholder="Kurze und eindeutige Bezeichnung" class="cl-input text-base font-semibold">
                    </label>

                    <!-- Beschreibung -->
                    <label class="cl-label">
                        Beschreibung

                        <textarea id="text" required rows="4"
                            placeholder="Beschreibe die Anforderung eindeutig und prüfbar."
                            class="cl-textarea"></textarea>
                    </label>

                    <!-- Rationale -->
                    <label class="cl-label">
                        Begründung / Rationale

                        <textarea id="rationale" rows="3" placeholder="Warum ist diese Anforderung notwendig?"
                            class="cl-textarea"></textarea>
                    </label>
                </div>
            </fieldset>


            <!-- Dynamische Attribute -->
            <fieldset id="dynamicAttributes" class="cl-fieldset hidden border-indigo-200 bg-indigo-50/40">

                <legend class="cl-legend bg-indigo-50 text-indigo-900">
                    Spezifische Attribute
                </legend>

                <div class="cl-fieldset-body">
                    <div id="attributeFields" class="grid gap-4 md:grid-cols-2">
                    </div>
                </div>
            </fieldset>


            <!-- Verantwortung und Planung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Verantwortung und Planung
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">

                    <!-- NEU: Die Dokumenten-Quelle -->
                    <label class="cl-label">
                        Quelle / Dokument
                        <input id="source_document" type="text" placeholder="z.B. Lastenheft Rev 1.1 oder PDF-Name"
                            class="cl-input text-blue-900 font-semibold">
                        <span class="cl-help">
                            Ursprung der Anforderung (wird beim Import automatisch befüllt).
                        </span>
                    </label>

                    <!-- BESTEHEND: Der Stakeholder -->
                    <label class="cl-label">
                        Zuständigkeit / Stakeholder
                        <select id="source_contact" class="cl-select">
                            <option value="">
                                -- Niemand zugewiesen --
                            </option>
                        </select>
                        <span class="cl-help">
                            Zuständige oder fachlich verantwortliche Person im Team.
                        </span>
                    </label>

                    <label class="cl-label">
                        Aufwandschätzung

                        <input id="effort" type="text" placeholder="z.B. 3 Tage oder 5 Story Points" class="cl-input">

                        <span class="cl-help">
                            Freie Aufwandsangabe für Planung und Bewertung.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Akzeptanzkriterien -->
            <fieldset id="criteria_container" class="cl-fieldset transition-all">

                <legend class="cl-legend">
                    Akzeptanzkriterien
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Prüfkriterien

                        <textarea id="acceptance_criteria" rows="5" placeholder="- Kriterium 1&#10;- Kriterium 2"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Ein prüfbares Kriterium pro Zeile. Kriterien
                            können später einzeln verifiziert werden.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Workflow -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Review und Workflow
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Prüfstatus

                        <select id="review_status" class="cl-select font-semibold">

                            <option value="Neu">
                                Neu
                            </option>

                            <option value="Wartet auf Überprüfung">
                                Wartet auf Überprüfung
                            </option>

                            <option value="Geprüft & Freigegeben">
                                Geprüft & Freigegeben
                            </option>

                            <option value="Abgelehnt">
                                Abgelehnt
                            </option>
                        </select>
                    </label>
                </div>
            </fieldset>


            <!-- Beziehungen -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Traceability und Beziehungen
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-6 md:grid-cols-2">

                    <!-- Parents -->
                    <div>
                        <label for="parentSearch" class="cl-label">
                            Parents
                        </label>

                        <p class="cl-help mb-2">
                            Übergeordnete Elemente, die durch dieses Element
                            erfüllt oder unterstützt werden.
                        </p>

                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z">
                                </path>
                            </svg>

                            <input id="parentSearch" type="search" placeholder="Parents suchen"
                                class="cl-input mt-0 pl-8 text-xs"
                                oninput="window.filterCheckboxes('parentSearch', 'parentsCheckboxList')">
                        </div>

                        <div id="parentsCheckboxList"
                            class="mt-2 h-44 space-y-1 overflow-y-auto rounded-md border border-slate-200 bg-slate-50 p-2 text-xs">
                        </div>
                    </div>


                    <!-- Children -->
                    <div>
                        <label for="childSearch" class="cl-label">
                            Children
                        </label>

                        <p class="cl-help mb-2">
                            Untergeordnete Elemente, durch die dieses Element
                            umgesetzt oder nachgewiesen wird.
                        </p>

                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z">
                                </path>
                            </svg>

                            <input id="childSearch" type="search" placeholder="Children suchen"
                                class="cl-input mt-0 pl-8 text-xs"
                                oninput="window.filterCheckboxes('childSearch', 'childrenCheckboxList')">
                        </div>

                        <div id="childrenCheckboxList"
                            class="mt-2 h-44 space-y-1 overflow-y-auto rounded-md border border-slate-200 bg-slate-50 p-2 text-xs">
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>


        <!-- Modal-Fuß -->
        <div class="cl-modal-footer">

            <button type="button" onclick="document.getElementById('reqModal').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button type="submit" class="cl-button cl-button-primary">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>

                Speichern
            </button>
        </div>
    </form>
</div>


<!-- =========================================================
     MODAL: AKZEPTANZKRITERIUM VERIFIZIEREN
========================================================== -->
<div id="verifyModal" class="cl-modal-overlay hidden z-[230]">
    <form id="verifyForm" class="cl-modal max-w-3xl" enctype="multipart/form-data">
        <div class="cl-modal-header">
            <div>
                <p class="cl-panel-eyebrow">Verification</p>
                <h2 class="cl-modal-title">Kriterium durch Test nachweisen</h2>
            </div>
        </div>
        <div class="p-5 space-y-3 max-h-[75vh] overflow-y-auto">
            <input id="verify_req_id" type="hidden"><input id="verify_crit_idx" type="hidden">
            <p id="verify_crit_text" class="rounded-md border border-blue-200 bg-blue-50 p-3 text-sm font-semibold"></p>
            <div class="grid grid-cols-2 gap-3">
                <label class="cl-label col-span-2">Testfall<select id="verify_test_case" class="cl-input" required>
                        <option value="">Testfall wählen</option>
                    </select></label>
                <label class="cl-label">Testtitel<input id="verify_title" class="cl-input" required></label>
                <label class="cl-label">Ergebnis<select id="verify_result" class="cl-input" required>
                        <option value="passed">Bestanden</option>
                        <option value="failed">Fehlgeschlagen</option>
                        <option value="blocked">Blockiert</option>
                    </select></label>
                <label class="cl-label col-span-2">Testbeschreibung<textarea id="verify_description" class="cl-textarea"
                        required></textarea></label>
                <label class="cl-label">Erwartetes Ergebnis<textarea id="verify_expected" class="cl-textarea"
                        required></textarea></label>
                <label class="cl-label">Tatsächliches Ergebnis<textarea id="verify_actual" class="cl-textarea"
                        required></textarea></label>
                <label class="cl-label">Softwareversion<input id="verify_software" class="cl-input" required></label>
                <label class="cl-label">Hardware-Revision<input id="verify_hardware" class="cl-input" required></label>
                <label class="cl-label">Testaufbau<input id="verify_setup" class="cl-input"
                        placeholder="z.B. Kundentestmodul"></label>
                <label class="cl-label">Ausgeführt am<input id="verify_executed_at" type="datetime-local"
                        class="cl-input" required></label>
                <label class="cl-label col-span-2">Einschränkung<textarea id="verify_limitation" class="cl-textarea"
                        placeholder="z.B. Pegel und Druck gemeinsam stimuliert"></textarea></label>
                <label class="cl-label col-span-2">Nachweise: Konsolenlog, Bilder oder PDF<input id="verify_files"
                        type="file" multiple accept=".txt,.log,.csv,.json,.pdf,.png,.jpg,.jpeg,.webp"
                        class="cl-input"></label>
            </div>
        </div>
        <div class="cl-modal-footer"><button type="button"
                onclick="document.getElementById('verifyModal').classList.add('hidden')"
                class="cl-button cl-button-secondary">Abbrechen</button><button type="submit"
                class="cl-button cl-button-success">Test speichern</button></div>
    </form>
</div>


<div id="reqAttachmentModal" class="cl-modal-overlay hidden z-[240]">
    <form id="reqAttachmentForm" class="cl-modal max-w-lg" enctype="multipart/form-data">
        <div class="cl-modal-header">
            <h2 class="cl-modal-title">Bild oder Zeichnung anhängen</h2>
        </div>
        <div class="cl-modal-body space-y-3"><input id="req_att_req_id" type="hidden"><input id="req_att_req_key"
                type="hidden"><input id="req_att_req_title" type="hidden">
            <label class="cl-label">Titel<input id="req_att_title" class="cl-input" required></label>
            <label class="cl-label">Beschreibung<textarea id="req_att_description"
                    class="cl-textarea"></textarea></label>
            <label class="cl-label">Datei<input id="req_att_file" type="file"
                    accept="image/png,image/jpeg,image/webp,application/pdf" class="cl-input" required></label>
        </div>
        <div class="cl-modal-footer"><button type="button"
                onclick="document.getElementById('reqAttachmentModal').classList.add('hidden')"
                class="cl-button cl-button-secondary">Abbrechen</button><button
                class="cl-button cl-button-success">Hochladen</button></div>
    </form>
</div>
<script type="module" src="js/requirement_evidence.js?v=1"></script>

<script type="module" src="js/requirements_import.js?v=general-1"></script>
<script type="module" src="js/acceptance_criteria_suggestions.js?v=2"></script>