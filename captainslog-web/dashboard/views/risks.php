<!-- dashboard/views/risks.php -->
<div class="cl-panel">

    <!-- =====================================================
         KOPFBEREICH
    ====================================================== -->
    <div class="cl-panel-header">
        <div>
            <p class="cl-panel-eyebrow">
                Bewertung · Maßnahmen · Überwachung
            </p>
            <h2 class="cl-panel-title">
                Risikomanagement
            </h2>
        </div>

        <button id="btnNewRisk" type="button" class="cl-button cl-button-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Neues Risiko
        </button>
    </div>

    <!-- =====================================================
         HINWEISLEISTE
    ====================================================== -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">
        <div class="cl-kpi border-l-emerald-500">
            <div class="cl-kpi-label">Niedriges Risiko</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Beobachten und regelmäßig bewerten</div>
        </div>
        <div class="cl-kpi border-l-amber-500">
            <div class="cl-kpi-label">Mittleres Risiko</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Maßnahmen und Frühwarnsystem festlegen</div>
        </div>
        <div class="cl-kpi border-l-red-500">
            <div class="cl-kpi-label">Hohes Risiko</div>
            <div class="mt-1 text-sm font-semibold text-slate-700">Sofort bewerten und aktiv reduzieren</div>
        </div>
    </div>

    <!-- =====================================================
         RISIKOTABELLE
    ====================================================== -->
    <div class="cl-table-container">
        <table class="cl-table min-w-[1650px]">
            <thead>
                <tr>
                    <th class="w-[110px]">Datum</th>
                    <th class="w-[90px]">Nr.</th>
                    <th class="min-w-[300px]">Risiko</th>
                    <th class="w-[65px] text-center" title="Wahrscheinlichkeit">W</th>
                    <th class="w-[65px] text-center" title="Auswirkung">E</th>
                    <th class="w-[70px] text-center" title="Risikozahl">R</th>
                    <th class="min-w-[150px]">Verantwortlich</th>
                    <th class="w-[115px]">Termin</th>
                    <th class="min-w-[260px]">Mitigationsstrategie</th>
                    <th class="min-w-[220px]">Entscheidung</th>
                    <th class="min-w-[240px]">Auswirkung</th>
                    <th class="w-[145px] text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody id="riskTableBody">
                <tr>
                    <td colspan="12" class="cl-empty-state">Bitte zuerst ein Projekt auswählen.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- =====================================================
         FUSSLEISTE
    ====================================================== -->
    <div class="cl-panel-footer">
        <span>Risikomanagement</span>
        <span>Wahrscheinlichkeit · Auswirkung · Maßnahmen · Review</span>
    </div>

    <!-- VERSTECKTER CONTAINER FÜR JS (Damit risks.js nicht abstürzt!) -->
    <div id="riskMapPoints" class="hidden"></div>
</div>

<!-- =========================================================
     MODAL: RISIKO ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="modalRisk" class="cl-modal-overlay hidden">
    <form id="formRisk"
        class="mx-auto flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded bg-white shadow-2xl">
        <div class="cl-modal-header shrink-0">
            <div>
                <p class="cl-panel-eyebrow">Risikoerfassung</p>
                <h2 id="riskModalTitle" class="cl-modal-title">Neues Risiko erfassen</h2>
            </div>
            <button id="riskModalClose" type="button"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2">✖</button>
        </div>
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
            <input id="risk_id" type="hidden">
            <input id="risk_key" type="hidden">

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Risiko</legend>
                <div class="cl-form-grid-2">
                    <label class="cl-label">Risikotyp<select id="risk_type" class="cl-select">
                            <option value="technical_product">Technisches Produktrisiko</option>
                            <option value="software">Software-Risiko</option>
                            <option value="hardware">Hardware-Risiko</option>
                            <option value="cybersecurity">Cybersecurity-Risiko</option>
                            <option value="quality">Qualitätsrisiko</option>
                            <option value="project">Projektrisiko</option>
                        </select></label>
                    <label class="cl-label">Status<select id="risk_workflow_status" class="cl-select">
                            <option value="open">Offen</option>
                            <option value="assessment">In Bewertung</option>
                            <option value="measures">Maßnahmen offen</option>
                            <option value="implementation">Umsetzung läuft</option>
                            <option value="verification">Verifikation offen</option>
                            <option value="residual_review">Restrisiko bewerten</option>
                            <option value="accepted">Akzeptiert</option>
                            <option value="closed">Geschlossen</option>
                        </select></label>
                    <label class="cl-label md:col-span-2">Risikobeschreibung / Titel<textarea id="risk_title" required
                            rows="2" maxlength="1000" class="cl-textarea"
                            placeholder="z.B. Fehlerhafte Regelung der Tankbefüllung"></textarea></label>
                    <label class="cl-label">Ursache / möglicher Softwarefehler<textarea id="risk_cause" rows="3"
                            class="cl-textarea"
                            placeholder="z.B. Pegelwert wird falsch erfasst oder bewertet"></textarea></label>
                    <label class="cl-label">Mögliches Fehlverhalten des Systems<textarea id="risk_malfunction" rows="3"
                            class="cl-textarea"
                            placeholder="z.B. Befüllung startet nicht oder stoppt am falschen Pegel"></textarea></label>
                    <label class="cl-label md:col-span-2">Mögliche Auswirkung<textarea id="risk_effect" rows="3"
                            class="cl-textarea"
                            placeholder="z.B. Unterfüllung, Überfüllung oder fehlende Alarmierung"></textarea></label>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Initiale Risikobewertung</legend>
                <div class="cl-form-grid-3">
                    <label class="cl-label">Wahrscheinlichkeit<input id="risk_w" type="number" min="1" max="5" value="1"
                            required class="cl-input"><span class="cl-help">1 = sehr unwahrscheinlich, 5 = sehr
                            wahrscheinlich</span></label>
                    <label class="cl-label">Auswirkung<input id="risk_e" type="number" min="1" max="5" value="1"
                            required class="cl-input"><span class="cl-help">1 = gering, 5 = kritisch</span></label>
                    <div><span class="cl-label">Risikozahl</span>
                        <div id="risk_initial_score"
                            class="mt-1 flex h-11 items-center justify-center border border-slate-300 bg-slate-100 text-xl font-extrabold text-blue-950 rounded">
                            1</div>
                    </div>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Steuerung und Maßnahmen</legend>
                <div class="cl-form-grid-3">
                    <label class="cl-label">Verantwortlich<select id="risk_responsible" class="cl-select">
                            <option value="">-- Niemand --</option>
                        </select></label>
                    <label class="cl-label">Review-Termin<input id="risk_date" type="date" class="cl-input"></label>
                    <label class="cl-label">Umsetzungsstatus<select id="risk_implementation_status" class="cl-select">
                            <option value="open">Offen</option>
                            <option value="in_progress">In Arbeit</option>
                            <option value="implemented">Umgesetzt</option>
                            <option value="verified">Verifiziert</option>
                        </select></label>
                    <label class="cl-label md:col-span-2">Mitigationsstrategie<textarea id="risk_mitigation" rows="4"
                            class="cl-textarea"
                            placeholder="Welche Maßnahme reduziert oder beherrscht das Risiko?"></textarea></label>
                    <label class="cl-label">Entscheidung / Begründung<textarea id="risk_decision" rows="4"
                            class="cl-textarea"></textarea></label>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Traceability und Nachweise</legend>
                <p class="mb-4 text-xs text-slate-500">Verknüpfe das Risiko direkt mit kontrollierenden Anforderungen,
                    Verifikation, Umsetzung und bekannten Fehlern.</p>
                <div class="cl-form-grid-2">
                    <div><label for="riskReqSearch" class="cl-label">Verknüpfte Anforderungen</label><input
                            id="riskReqSearch" type="search" class="cl-input"
                            placeholder="ID, Typ oder Titel suchen...">
                        <div id="riskReqList"
                            class="mt-2 max-h-56 overflow-y-auto rounded border border-slate-300 bg-white">
                            <div class="p-4 text-sm italic text-slate-400">Wird geladen...</div>
                        </div>
                    </div>
                    <div><label for="riskVerificationSearch" class="cl-label">Verifikation, TC / TR</label><input
                            id="riskVerificationSearch" type="search" class="cl-input"
                            placeholder="TC, TR oder Titel suchen...">
                        <div id="riskVerificationList"
                            class="mt-2 max-h-56 overflow-y-auto rounded border border-slate-300 bg-white">
                            <div class="p-4 text-sm italic text-slate-400">Wird geladen...</div>
                        </div>
                    </div>
                    <div><label for="riskTaskSearch" class="cl-label">Umsetzungsaufgaben</label><input
                            id="riskTaskSearch" type="search" class="cl-input"
                            placeholder="WBS, Kategorie oder Titel suchen...">
                        <div id="riskTaskList"
                            class="mt-2 max-h-56 overflow-y-auto rounded border border-slate-300 bg-white">
                            <div class="p-4 text-sm italic text-slate-400">Wird geladen...</div>
                        </div>
                    </div>
                    <div><label for="riskIssueSearch" class="cl-label">Relevante Issues</label><input
                            id="riskIssueSearch" type="search" class="cl-input"
                            placeholder="Issue, Status oder Titel suchen...">
                        <div id="riskIssueList"
                            class="mt-2 max-h-56 overflow-y-auto rounded border border-slate-300 bg-white">
                            <div class="p-4 text-sm italic text-slate-400">Wird geladen...</div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Restrisikobewertung</legend>
                <div class="cl-form-grid-3">
                    <label class="cl-label">Restwahrscheinlichkeit<input id="risk_residual_w" type="number" min="1"
                            max="5" value="1" class="cl-input"></label>
                    <label class="cl-label">Restauswirkung<input id="risk_residual_e" type="number" min="1" max="5"
                            value="1" class="cl-input"></label>
                    <div><span class="cl-label">Restrisikozahl</span>
                        <div id="risk_residual_score"
                            class="mt-1 flex h-11 items-center justify-center border border-slate-300 bg-slate-100 text-xl font-extrabold text-blue-950 rounded">
                            1</div>
                    </div>
                    <label class="flex items-center gap-3 font-bold text-slate-700"><input id="risk_residual_accepted"
                            type="checkbox" class="h-4 w-4 accent-[#0d3158]">Restrisiko akzeptiert</label>
                    <label class="cl-label md:col-span-2">Begründung<textarea id="risk_residual_reason" rows="3"
                            class="cl-textarea"
                            placeholder="Warum ist das verbleibende Risiko akzeptabel?"></textarea></label>
                </div>
            </fieldset>
        </div>
        <div class="flex shrink-0 justify-end gap-3 border-t border-slate-300 bg-slate-100 p-4">
            <button id="riskModalCancel" type="button" class="cl-button cl-button-secondary">Abbrechen</button>
            <button type="submit" class="cl-button cl-button-primary">Risiko speichern</button>
        </div>
    </form>
</div>

<!-- MODAL: RISIKO ARCHIVIEREN -->
<div id="riskArchiveModal" class="cl-modal-overlay hidden">
    <div class="cl-modal max-w-md mx-auto">
        <div class="cl-modal-header">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="cl-panel-eyebrow">Archivierung</p>
                    <h3 class="cl-modal-title">Risiko archivieren?</h3>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('riskArchiveModal').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2">✖</button>
        </div>
        <div class="cl-modal-body">
            <div class="rounded-md border border-red-200 bg-red-50 p-4">
                <p class="text-sm leading-6 text-slate-700">Möchtest du das Risiko <span id="modalArchiveRiskName"
                        class="font-bold text-red-700"></span> wirklich archivieren?</p>
                <p class="mt-3 text-xs leading-5 text-slate-500">Das Risiko wird aus der aktiven Übersicht entfernt. Aus
                    Revisionsgründen bleibt der Eintrag in der Historie erhalten.</p>
            </div>
        </div>
        <div class="cl-modal-footer">
            <button id="modalRiskCancelBtn" type="button" class="cl-button cl-button-secondary"
                onclick="document.getElementById('riskArchiveModal').classList.add('hidden')">Abbrechen</button>
            <button id="modalRiskConfirmBtn" type="button"
                class="cl-button cl-button-danger border-red-600 bg-red-600 text-white hover:bg-red-700">Ja,
                archivieren</button>
        </div>
    </div>
</div>