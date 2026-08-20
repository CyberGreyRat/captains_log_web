<!-- dashboard/views/stakeholders.php -->

<div class="cl-panel">

    <!-- =====================================================
         KOPFBEREICH
    ====================================================== -->
    <div class="cl-panel-header">

        <div>
            <p class="cl-panel-eyebrow">
                Externe Kontakte · Einfluss · Kommunikation
            </p>

            <h2 class="cl-panel-title">
                Externe Stakeholder
            </h2>
        </div>

        <button id="btnNewStakeholder" type="button" class="cl-button cl-button-primary">

            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neuer Stakeholder
        </button>
    </div>


    <!-- =====================================================
         HINWEISLEISTE
    ====================================================== -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">

        <div class="cl-kpi">
            <div class="cl-kpi-label">
                Externe Beteiligte
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Kunden, Partner, Lieferanten und weitere externe Kontakte.
            </div>
        </div>

        <div class="cl-kpi border-l-emerald-500">
            <div class="cl-kpi-label">
                Interne Projektmitglieder
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Interne Nutzer werden getrennt im Reiter Projektteam verwaltet.
            </div>
        </div>

        <div class="cl-kpi border-l-amber-500">
            <div class="cl-kpi-label">
                Stakeholder-Analyse
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Einfluss und Interesse bestimmen den Kommunikationsbedarf.
            </div>
        </div>
    </div>


    <!-- =====================================================
         STAKEHOLDER-TABELLE
    ====================================================== -->
    <div class="cl-table-container min-h-[280px] shrink-0 flex-none">

        <table class="cl-table min-w-[1250px]">

            <thead>
                <tr>
                    <th class="min-w-[190px]">
                        Name
                    </th>

                    <th class="min-w-[180px]">
                        Rolle
                    </th>

                    <th class="min-w-[190px]">
                        Position
                    </th>

                    <th class="min-w-[240px]">
                        Kontakt
                    </th>

                    <th class="min-w-[260px]">
                        Wissen / Verfügbarkeit
                    </th>

                    <th class="min-w-[170px]">
                        Einfluss / Interesse
                    </th>

                    <th class="w-[150px] text-right">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="stakeholderTableBody">

                <tr>
                    <td colspan="7" class="cl-empty-state">
                        Stakeholder werden geladen...
                    </td>
                </tr>

            </tbody>
        </table>
    </div>


    <!-- =====================================================
         STAKEHOLDER-MATRIX
    ====================================================== -->
    <section class="shrink-0 border-t border-slate-200 bg-slate-50 px-5 py-5">

        <div class="mb-5 flex flex-col justify-between gap-3 lg:flex-row lg:items-start">

            <div>
                <p class="cl-panel-eyebrow">
                    Visuelle Auswertung
                </p>

                <h3 class="text-lg font-extrabold text-blue-950">
                    Stakeholder-Matrix
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Einordnung anhand des Einflusses auf das Projekt und des
                    Interesses am Projektergebnis.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                <span class="cl-badge cl-badge-warning">
                    Sorgfältig betreuen
                </span>

                <span class="cl-badge cl-badge-info">
                    Höchste Priorität
                </span>

                <span class="cl-badge cl-badge-neutral">
                    Beobachten
                </span>

                <span class="cl-badge cl-badge-success">
                    Informiert halten
                </span>
            </div>
        </div>


        <!-- Matrix-Karte -->
        <div class="cl-card mx-auto max-w-4xl">

            <div class="cl-card-body">

                <div class="overflow-x-auto px-8 pb-10 pt-4">

                    <div class="relative mx-auto h-[440px] min-w-[700px] max-w-[820px]">

                        <!-- Y-Achse -->
                        <div
                            class="absolute -left-20 top-1/2 -translate-y-1/2 -rotate-90 whitespace-nowrap text-xs font-extrabold uppercase tracking-wide text-slate-600">
                            Einfluss
                        </div>

                        <!-- X-Achse -->
                        <div
                            class="absolute -bottom-9 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-extrabold uppercase tracking-wide text-slate-600">
                            Interesse
                        </div>


                        <!-- Y-Achsen-Werte -->
                        <div
                            class="absolute -left-12 top-0 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Hoch
                        </div>

                        <div
                            class="absolute -left-14 bottom-0 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Gering
                        </div>


                        <!-- X-Achsen-Werte -->
                        <div
                            class="absolute -bottom-6 left-0 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Gering
                        </div>

                        <div
                            class="absolute -bottom-6 right-0 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Hoch
                        </div>


                        <!-- Matrix -->
                        <div
                            class="absolute inset-0 overflow-hidden rounded-md border-2 border-slate-400 bg-white shadow-sm">

                            <!-- Quadranten -->
                            <div class="absolute inset-0 grid grid-cols-2 grid-rows-2">

                                <!-- Hoher Einfluss, geringes Interesse -->
                                <div class="relative border-b border-r border-white bg-amber-100">

                                    <div class="absolute left-4 top-4">
                                        <div class="text-xs font-extrabold uppercase tracking-wide text-amber-900">
                                            Sorgfältig betreuen
                                        </div>

                                        <div class="mt-1 max-w-[220px] text-[10px] leading-4 text-amber-800">
                                            Hoher Einfluss, aber geringeres Interesse.
                                            Zufriedenheit regelmäßig prüfen.
                                        </div>
                                    </div>
                                </div>


                                <!-- Hoher Einfluss, hohes Interesse -->
                                <div class="relative border-b border-white bg-blue-100">

                                    <div class="absolute left-4 top-4">
                                        <div class="text-xs font-extrabold uppercase tracking-wide text-blue-950">
                                            Höchste Priorität
                                        </div>

                                        <div class="mt-1 max-w-[220px] text-[10px] leading-4 text-blue-800">
                                            Eng einbinden, regelmäßig abstimmen und
                                            Entscheidungen frühzeitig kommunizieren.
                                        </div>
                                    </div>
                                </div>


                                <!-- Geringer Einfluss, geringes Interesse -->
                                <div class="relative border-r border-white bg-slate-200">

                                    <div class="absolute bottom-4 left-4">
                                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-700">
                                            Beobachten
                                        </div>

                                        <div class="mt-1 max-w-[220px] text-[10px] leading-4 text-slate-600">
                                            Entwicklung beobachten und bei relevanten
                                            Änderungen gezielt informieren.
                                        </div>
                                    </div>
                                </div>


                                <!-- Geringer Einfluss, hohes Interesse -->
                                <div class="relative bg-emerald-100">

                                    <div class="absolute bottom-4 left-4">
                                        <div class="text-xs font-extrabold uppercase tracking-wide text-emerald-900">
                                            Informiert halten
                                        </div>

                                        <div class="mt-1 max-w-[220px] text-[10px] leading-4 text-emerald-800">
                                            Regelmäßig über Fortschritt, Änderungen
                                            und Ergebnisse informieren.
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Stakeholder-Punkte -->
                            <div id="stakeholderMapPoints" class="absolute inset-0 z-10 p-8">
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
            Externe Stakeholder
        </span>

        <span>
            Kunden · Partner · Lieferanten · Prüfstellen
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: STAKEHOLDER ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="modalStakeholder" class="cl-modal-overlay hidden">

    <form id="formStakeholder" class="cl-modal max-w-3xl">

        <!-- Modal-Kopf -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Externe Projektbeteiligung
                </p>

                <h2 class="cl-modal-title">
                    Stakeholder bearbeiten
                </h2>
            </div>

            <button type="button" onclick="document.getElementById('modalStakeholder').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>


        <input id="stk_id" type="hidden">


        <!-- Modal-Inhalt -->
        <div class="cl-modal-body">

            <!-- Person und Organisation -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Person und Organisation
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">

                    <!-- Name -->
                    <label class="cl-label md:col-span-2">
                        Name

                        <input id="stk_name" type="text" required maxlength="255"
                            placeholder="Name der externen Ansprechperson oder Organisation"
                            class="cl-input text-base font-semibold">
                    </label>

                    <!-- Projektrolle -->
                    <label class="cl-label">
                        Rolle im Projekt

                        <input id="stk_role" type="text" maxlength="150"
                            placeholder="z.B. Auftraggeber, Lieferant oder Prüfstelle" class="cl-input">

                        <span class="cl-help">
                            Fachliche Rolle oder Beziehung zum Projekt.
                        </span>
                    </label>

                    <!-- Position -->
                    <label class="cl-label">
                        Position im Unternehmen

                        <input id="stk_position" type="text" maxlength="150"
                            placeholder="z.B. Technische Leitung oder Einkauf" class="cl-input">

                        <span class="cl-help">
                            Position oder Funktion in der externen Organisation.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Kontaktdaten -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Kontaktdaten
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">

                    <!-- E-Mail -->
                    <label class="cl-label">
                        E-Mail-Adresse

                        <input id="stk_email" type="email" maxlength="255" autocomplete="email"
                            placeholder="name@unternehmen.de" class="cl-input">
                    </label>

                    <!-- Telefon -->
                    <label class="cl-label">
                        Telefonnummer

                        <input id="stk_phone" type="tel" maxlength="100" autocomplete="tel" placeholder="+49 ..."
                            class="cl-input">
                    </label>
                </div>
            </fieldset>


            <!-- Fachliche Angaben -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Fachliche Angaben
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-2">

                    <!-- Expertise -->
                    <label class="cl-label">
                        Wissensgebiet / Expertise

                        <input id="stk_expertise" type="text" maxlength="255"
                            placeholder="z.B. Systembetrieb, Zulassung oder Hardware" class="cl-input">

                        <span class="cl-help">
                            Fachwissen und relevante Kenntnisse.
                        </span>
                    </label>

                    <!-- Verfügbarkeit -->
                    <label class="cl-label">
                        Verfügbarkeit

                        <input id="stk_availability" type="text" maxlength="150"
                            placeholder="z.B. nach Abstimmung oder zweiwöchentlich" class="cl-input">

                        <span class="cl-help">
                            Erreichbarkeit oder bevorzugtes Abstimmungsintervall.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- Stakeholder-Bewertung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Stakeholder-Bewertung
                </legend>

                <div class="cl-fieldset-body">

                    <div class="mb-5 rounded-md border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-950">
                        Die Bewertung von Einfluss und Interesse bestimmt die
                        Position innerhalb der Stakeholder-Matrix.
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <!-- Einfluss -->
                        <label class="cl-label">
                            Einfluss auf das Projekt

                            <select id="stk_influence" class="cl-select font-semibold">

                                <option value="Low">
                                    Gering
                                </option>

                                <option value="High">
                                    Hoch
                                </option>
                            </select>

                            <span class="cl-help">
                                Wie stark kann der Stakeholder Entscheidungen,
                                Umfang oder Projekterfolg beeinflussen?
                            </span>
                        </label>

                        <!-- Interesse -->
                        <label class="cl-label">
                            Interesse am Projekt

                            <select id="stk_interest" class="cl-select font-semibold">

                                <option value="Low">
                                    Gering
                                </option>

                                <option value="High">
                                    Hoch
                                </option>
                            </select>

                            <span class="cl-help">
                                Wie stark ist der Stakeholder an Fortschritt
                                und Ergebnis des Projekts interessiert?
                            </span>
                        </label>
                    </div>
                </div>
            </fieldset>
        </div>


        <!-- Modal-Fuß -->
        <div class="cl-modal-footer">

            <button type="button" onclick="document.getElementById('modalStakeholder').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button type="submit" class="cl-button cl-button-primary">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>

                Stakeholder speichern
            </button>
        </div>
    </form>
</div>