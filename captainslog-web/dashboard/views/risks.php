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

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neues Risiko
        </button>
    </div>


    <!-- =====================================================
         HINWEISLEISTE
    ====================================================== -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">

        <div class="cl-kpi border-l-emerald-500">
            <div class="cl-kpi-label">
                Niedriges Risiko
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Beobachten und regelmäßig bewerten
            </div>
        </div>

        <div class="cl-kpi border-l-amber-500">
            <div class="cl-kpi-label">
                Mittleres Risiko
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Maßnahmen und Frühwarnsystem festlegen
            </div>
        </div>

        <div class="cl-kpi border-l-red-500">
            <div class="cl-kpi-label">
                Hohes Risiko
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Sofort bewerten und aktiv reduzieren
            </div>
        </div>
    </div>


    <!-- =====================================================
         RISIKOTABELLE
    ====================================================== -->
    <div class="cl-table-container min-h-[280px] shrink-0 flex-none">

        <table class="cl-table min-w-[1650px]">

            <thead>
                <tr>
                    <th class="w-[110px]">
                        Datum
                    </th>

                    <th class="w-[90px]">
                        Nr.
                    </th>

                    <th class="min-w-[300px]">
                        Risiko
                    </th>

                    <th class="w-[65px] text-center" title="Wahrscheinlichkeit">
                        W
                    </th>

                    <th class="w-[65px] text-center" title="Auswirkung">
                        E
                    </th>

                    <th class="w-[70px] text-center" title="Risikozahl">
                        R
                    </th>

                    <th class="min-w-[150px]">
                        Verantwortlich
                    </th>

                    <th class="w-[115px]">
                        Termin
                    </th>

                    <th class="min-w-[260px]">
                        Mitigationsstrategie
                    </th>

                    <th class="min-w-[220px]">
                        Entscheidung
                    </th>

                    <th class="min-w-[240px]">
                        Auswirkung
                    </th>

                    <th class="w-[145px] text-right">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="riskTableBody">

                <tr>
                    <td colspan="12" class="cl-empty-state">
                        Bitte zuerst ein Projekt auswählen.
                    </td>
                </tr>

            </tbody>
        </table>
    </div>


    <!-- =====================================================
         RISIKOMATRIX
    ====================================================== -->
    <section class="shrink-0 border-t border-slate-200 bg-slate-50 px-5 py-5">

        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">

            <div>
                <p class="cl-panel-eyebrow">
                    Visuelle Auswertung
                </p>

                <h3 class="text-lg font-extrabold text-blue-950">
                    Risikomatrix
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Positionierung anhand von Wahrscheinlichkeit und Auswirkung.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase">

                <span class="cl-badge cl-badge-success">
                    Niedrig
                </span>

                <span class="cl-badge cl-badge-warning">
                    Mittel
                </span>

                <span class="cl-badge border-orange-300 bg-orange-50 text-orange-800">
                    Hoch
                </span>

                <span class="cl-badge cl-badge-danger">
                    Kritisch
                </span>
            </div>
        </div>


        <div class="cl-card mx-auto max-w-3xl">

            <div class="cl-card-body">

                <div class="flex justify-center overflow-x-auto px-8 pb-10 pt-4">

                    <div class="relative h-[460px] w-[460px] shrink-0">

                        <!-- Y-Achsen-Beschriftung -->
                        <div
                            class="absolute -left-20 top-1/2 -translate-y-1/2 -rotate-90 text-xs font-extrabold uppercase tracking-wide text-slate-600">
                            Auswirkung
                        </div>

                        <!-- X-Achsen-Beschriftung -->
                        <div
                            class="absolute -bottom-9 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-extrabold uppercase tracking-wide text-slate-600">
                            Wahrscheinlichkeit
                        </div>


                        <!-- Y-Achsen-Werte -->
                        <div class="absolute -left-7 inset-y-0 grid grid-rows-5 text-xs font-bold text-slate-500">

                            <div class="flex items-center justify-center">
                                5
                            </div>

                            <div class="flex items-center justify-center">
                                4
                            </div>

                            <div class="flex items-center justify-center">
                                3
                            </div>

                            <div class="flex items-center justify-center">
                                2
                            </div>

                            <div class="flex items-center justify-center">
                                1
                            </div>
                        </div>


                        <!-- X-Achsen-Werte -->
                        <div class="absolute -bottom-6 inset-x-0 grid grid-cols-5 text-xs font-bold text-slate-500">

                            <div class="text-center">1</div>
                            <div class="text-center">2</div>
                            <div class="text-center">3</div>
                            <div class="text-center">4</div>
                            <div class="text-center">5</div>
                        </div>


                        <!-- Matrix -->
                        <div
                            class="absolute inset-0 overflow-hidden rounded-md border-2 border-slate-500 bg-white shadow-sm">

                            <!-- Farbfelder -->
                            <div class="absolute inset-0 grid grid-cols-5 grid-rows-5">

                                <!-- Auswirkung 5 -->
                                <div class="border border-white/50 bg-amber-300"></div>
                                <div class="border border-white/50 bg-orange-400"></div>
                                <div class="border border-white/50 bg-red-400"></div>
                                <div class="border border-white/50 bg-red-500"></div>
                                <div class="border border-white/50 bg-red-600"></div>

                                <!-- Auswirkung 4 -->
                                <div class="border border-white/50 bg-emerald-300"></div>
                                <div class="border border-white/50 bg-amber-300"></div>
                                <div class="border border-white/50 bg-orange-400"></div>
                                <div class="border border-white/50 bg-red-400"></div>
                                <div class="border border-white/50 bg-red-500"></div>

                                <!-- Auswirkung 3 -->
                                <div class="border border-white/50 bg-emerald-400"></div>
                                <div class="border border-white/50 bg-emerald-300"></div>
                                <div class="border border-white/50 bg-amber-300"></div>
                                <div class="border border-white/50 bg-orange-400"></div>
                                <div class="border border-white/50 bg-red-400"></div>

                                <!-- Auswirkung 2 -->
                                <div class="border border-white/50 bg-emerald-500"></div>
                                <div class="border border-white/50 bg-emerald-400"></div>
                                <div class="border border-white/50 bg-emerald-300"></div>
                                <div class="border border-white/50 bg-amber-300"></div>
                                <div class="border border-white/50 bg-orange-400"></div>

                                <!-- Auswirkung 1 -->
                                <div class="border border-white/50 bg-emerald-500"></div>
                                <div class="border border-white/50 bg-emerald-500"></div>
                                <div class="border border-white/50 bg-emerald-400"></div>
                                <div class="border border-white/50 bg-emerald-300"></div>
                                <div class="border border-white/50 bg-amber-300"></div>
                            </div>

                            <!-- Risikopunkte -->
                            <div id="riskMapPoints" class="absolute inset-0 z-10">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- =====================================================
         FUSSLEISTE
    ====================================================== -->
    <div class="cl-panel-footer">

        <span>
            Risikomanagement
        </span>

        <span>
            Wahrscheinlichkeit · Auswirkung · Maßnahmen · Review
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: RISIKO ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="modalRisk" class="cl-modal-overlay hidden">

    <form id="formRisk" class="cl-modal max-w-4xl">

        <!-- Modal-Kopf -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Risikoerfassung
                </p>

                <h2 id="riskModalTitle" class="cl-modal-title">
                    Neues Risiko erfassen
                </h2>
            </div>

            <button type="button" onclick="document.getElementById('modalRisk').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <input id="risk_id" type="hidden">

        <input id="risk_key" type="hidden">


        <!-- Modal-Inhalt -->
        <div class="cl-modal-body">

            <!-- Risikobeschreibung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Risiko
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Risikobeschreibung / Titel

                        <textarea id="risk_title" required rows="3" maxlength="1000"
                            placeholder="Beschreibe das mögliche Ereignis und die daraus entstehende Gefährdung."
                            class="cl-textarea text-base font-semibold"></textarea>

                        <span class="cl-help">
                            Formuliere möglichst eindeutig, welches Ereignis
                            eintreten kann und wodurch das Projekt betroffen wäre.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Bewertung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Risikobewertung
                </legend>

                <div class="cl-fieldset-body">

                    <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-950">
                        Die Risikozahl wird aus Wahrscheinlichkeit und
                        Auswirkung berechnet. Wertebereich jeweils 1 bis 5.
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                        <label class="cl-label">
                            Wahrscheinlichkeit

                            <input id="risk_w" type="number" min="1" max="5" step="1" value="1" required
                                class="cl-input text-center text-lg font-extrabold">

                            <span class="cl-help">
                                1 = sehr unwahrscheinlich, 5 = sehr wahrscheinlich
                            </span>
                        </label>

                        <label class="cl-label">
                            Auswirkung

                            <input id="risk_e" type="number" min="1" max="5" step="1" value="1" required
                                class="cl-input text-center text-lg font-extrabold">

                            <span class="cl-help">
                                1 = gering, 5 = kritisch
                            </span>
                        </label>

                        <label class="cl-label">
                            Verantwortlich

                            <select id="risk_responsible" class="cl-select">
                            </select>

                            <span class="cl-help">
                                Verantwortliche Person für Überwachung und Maßnahmen.
                            </span>
                        </label>
                    </div>
                </div>
            </fieldset>


            <!-- Steuerung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Steuerung und Review
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">

                    <label class="cl-label">
                        Review-Termin

                        <input id="risk_date" type="date" class="cl-input">

                        <span class="cl-help">
                            Datum der nächsten Risikobewertung.
                        </span>
                    </label>

                    <label class="cl-label">
                        Entscheidung

                        <input id="risk_decision" type="text" maxlength="255" placeholder="z.B. Lieferkette verfolgen"
                            class="cl-input">

                        <span class="cl-help">
                            Festgelegte Entscheidung zum weiteren Umgang.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Maßnahmen -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Maßnahmen und Auswirkungen
                </legend>

                <div class="cl-fieldset-body space-y-5">

                    <label class="cl-label">
                        Mitigationsstrategie

                        <textarea id="risk_mitigation" rows="4"
                            placeholder="Welche vorbeugenden oder reduzierenden Maßnahmen werden umgesetzt?"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Maßnahmen zur Vermeidung, Überwachung oder Reduzierung
                            des Risikos.
                        </span>
                    </label>

                    <label class="cl-label">
                        Auswirkung / Effect

                        <textarea id="risk_effect" rows="4"
                            placeholder="z.B. Terminverzug, Mehrkosten, technische Einschränkungen oder Qualitätsverlust"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Beschreibe mögliche Auswirkungen auf Zeit, Kosten,
                            Umfang, Qualität oder technische Umsetzung.
                        </span>
                    </label>
                </div>
            </fieldset>
        </div>


        <!-- Modal-Fuß -->
        <div class="cl-modal-footer">

            <button type="button" onclick="document.getElementById('modalRisk').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button type="submit" class="cl-button cl-button-primary">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>

                Risiko speichern
            </button>
        </div>
    </form>
</div>


<!-- =========================================================
     MODAL: RISIKO ARCHIVIEREN
========================================================== -->
<div id="riskArchiveModal" class="cl-modal-overlay hidden z-[230]">

    <div class="cl-modal max-w-md">

        <!-- Modal-Kopf -->
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
                    <p class="cl-panel-eyebrow">
                        Archivierung
                    </p>

                    <h3 class="cl-modal-title">
                        Risiko archivieren?
                    </h3>
                </div>
            </div>

            <button type="button" onclick="document.getElementById('riskArchiveModal').classList.add('hidden')"
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

            <div class="rounded-md border border-red-200 bg-red-50 p-4">

                <p class="text-sm leading-6 text-slate-700">
                    Möchtest du das Risiko

                    <span id="modalArchiveRiskName" class="font-bold text-red-700">
                    </span>

                    wirklich archivieren?
                </p>

                <p class="mt-3 text-xs leading-5 text-slate-500">
                    Das Risiko wird aus der aktiven Übersicht entfernt.
                    Aus Revisionsgründen bleibt der Eintrag in der Historie
                    erhalten.
                </p>
            </div>
        </div>


        <!-- Modal-Fuß -->
        <div class="cl-modal-footer">

            <button id="modalRiskCancelBtn" type="button" class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button id="modalRiskConfirmBtn" type="button"
                class="cl-button cl-button-danger border-red-600 bg-red-600 text-white hover:bg-red-700">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16">
                    </path>
                </svg>

                Ja, archivieren
            </button>
        </div>
    </div>
</div>