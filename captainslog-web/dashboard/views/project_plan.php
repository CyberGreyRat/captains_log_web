<!-- dashboard/views/project_plan.php -->

<div class="cl-panel">

    <!-- =====================================================
         KOPFBEREICH
    ====================================================== -->
    <div class="cl-panel-header">

        <div>
            <p class="cl-panel-eyebrow">
                Planung · Aufgaben · Fortschritt
            </p>

            <h2 class="cl-panel-title">
                Projektplan & Aufgaben
            </h2>
        </div>

        <button id="btnNewTask" type="button" class="cl-button cl-button-primary">

            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neue Aufgabe
        </button>
    </div>


    <!-- =====================================================
         HINWEISLEISTE
    ====================================================== -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 lg:grid-cols-3">

        <div class="cl-kpi">
            <div class="cl-kpi-label">
                Projektstruktur
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Aufgaben werden nach Kategorie gruppiert.
            </div>
        </div>

        <div class="cl-kpi border-l-emerald-500">
            <div class="cl-kpi-label">
                Automatischer Fortschritt
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Fortschritt kann aus Requirements oder Checklisten berechnet werden.
            </div>
        </div>

        <div class="cl-kpi border-l-amber-500">
            <div class="cl-kpi-label">
                Checklisten
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-700">
                Zeilen mit „-- “ erzeugen automatisch Unteraufgaben.
            </div>
        </div>
    </div>


    <!-- =====================================================
         AUFGABENTABELLE
    ====================================================== -->
    <div class="cl-table-container">

        <table class="cl-table min-w-[1150px]">

            <thead>
                <tr>
                    <th class="w-[90px]">
                        ID
                    </th>

                    <th class="min-w-[360px]">
                        Aufgabe / Kommentare
                    </th>

                    <th class="min-w-[150px]">
                        Zuweisung
                    </th>

                    <th class="w-[110px] text-center">
                        Aufwand
                    </th>

                    <th class="w-[145px] text-center">
                        Zeitraum
                    </th>

                    <th class="w-[190px]">
                        Fortschritt
                    </th>

                    <th class="w-[135px] text-right">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="taskTableBody">

                <tr>
                    <td colspan="7" class="cl-empty-state">
                        Bitte zuerst ein Projekt auswählen.
                    </td>
                </tr>

            </tbody>
        </table>
    </div>


    <!-- =====================================================
         FUSSLEISTE
    ====================================================== -->
    <div class="cl-panel-footer">

        <span>
            Projektplanung
        </span>

        <span>
            Aufgaben · Checklisten · Requirements · Fortschritt
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: AUFGABE ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="modalTask" class="cl-modal-overlay hidden">

    <form id="formTask" class="cl-modal max-w-5xl">

        <!-- Modal-Kopf -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Projektplanung
                </p>

                <h2 id="taskModalTitle" class="cl-modal-title">
                    Aufgabe bearbeiten
                </h2>
            </div>

            <button type="button" onclick="document.getElementById('modalTask').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <input id="task_id" type="hidden">


        <!-- =================================================
             SCROLLBARER MODAL-INHALT
        ================================================== -->
        <div class="cl-modal-body">

            <!-- =============================================
                 AUFGABENKATALOG
            ============================================== -->
            <fieldset class="cl-fieldset border-blue-200 bg-blue-50/30">

                <legend class="cl-legend bg-blue-50 text-blue-950">
                    Aus Aufgabenkatalog übernehmen
                </legend>

                <div class="cl-fieldset-body">

                    <div class="mb-4 flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-blue-200 bg-white text-blue-950">

                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16">
                                </path>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-extrabold text-blue-950">
                                Vorlage auswählen
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Eine vorhandene Aufgabengruppe und danach eine
                                passende Aufgabe auswählen. Titel, Kategorie und
                                Standardaufwand werden automatisch übernommen.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <label class="cl-label">
                            1. Hauptgruppe wählen

                            <select id="tpl_category" class="cl-select font-semibold">
                            </select>
                        </label>

                        <label class="cl-label">
                            2. Aufgabe wählen

                            <select id="tpl_item" disabled
                                class="cl-select cursor-not-allowed bg-slate-100 font-semibold">
                            </select>
                        </label>
                    </div>
                </div>
            </fieldset>


            <!-- =============================================
                 BASISINFORMATIONEN
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Basisinformationen
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-12">

                    <!-- WBS -->
                    <label class="cl-label md:col-span-3">
                        ID / WBS-Code

                        <input id="task_wbs" type="text" readonly
                            class="cl-input cursor-not-allowed bg-slate-100 font-mono font-bold text-slate-600">

                        <span class="cl-help">
                            Wird für neue Hauptaufgaben automatisch erzeugt.
                        </span>
                    </label>

                    <!-- Kategorie -->
                    <label class="cl-label md:col-span-9">
                        Kategorie / Bereich

                        <input id="task_category" type="text" required maxlength="100"
                            placeholder="z.B. Software Controller oder Gesamtinbetriebnahme"
                            class="cl-input font-semibold">
                    </label>

                    <!-- Titel -->
                    <label class="cl-label md:col-span-12">
                        Titel der Aufgabe

                        <input id="task_title" type="text" required maxlength="255"
                            placeholder="Kurze und eindeutige Aufgabenbezeichnung"
                            class="cl-input text-base font-semibold">
                    </label>

                    <!-- Kommentare / Checkliste -->
                    <label class="cl-label md:col-span-12">

                        <span class="flex flex-col justify-between gap-1 sm:flex-row sm:items-center">

                            <span>
                                Kommentare / Checkliste
                            </span>

                            <span class="text-xs font-normal italic text-slate-500">
                                Zeilen mit „-- “ werden automatisch zu Unteraufgaben.
                            </span>
                        </span>

                        <textarea id="task_description" rows="5"
                            placeholder="Notizen zur Aufgabe&#10;-- Schaltplan prüfen&#10;-- Stückliste freigeben"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Normale Zeilen bleiben Kommentare. Jede Zeile mit
                            „-- “ wird als eigener Checklistenpunkt gespeichert.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 VERANTWORTUNG UND ZEITPLAN
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Verantwortung und Zeitplan
                </legend>

                <div class="cl-fieldset-body grid grid-cols-1 gap-5 md:grid-cols-12">

                    <!-- Assignee -->
                    <label class="cl-label md:col-span-6">
                        Zuweisung / Verantwortlich

                        <input id="task_assignee" type="text" maxlength="100" placeholder="Name oder Projektrolle"
                            class="cl-input">

                        <span class="cl-help">
                            Aktuell als Freitext gespeichert. Später kann dieses
                            Feld direkt mit dem Projektteam verbunden werden.
                        </span>
                    </label>

                    <!-- Start -->
                    <label class="cl-label md:col-span-3">
                        Startdatum

                        <input id="task_start" type="date" class="cl-input">
                    </label>

                    <!-- Ende -->
                    <label class="cl-label md:col-span-3">
                        Enddatum

                        <input id="task_end" type="date" class="cl-input">
                    </label>

                    <!-- Aufwand -->
                    <label class="cl-label md:col-span-6">
                        Aufwand in Stunden

                        <input id="task_effort" type="number" min="0" step="0.1" placeholder="0,0" class="cl-input">

                        <span class="cl-help">
                            Geplanter Gesamtaufwand für die Aufgabe.
                        </span>
                    </label>

                    <!-- Performance -->
                    <label class="cl-label md:col-span-6">
                        Performance / Leistungsfaktor in Prozent

                        <input id="task_performance" type="number" min="1" max="100" value="100" class="cl-input">

                        <span class="cl-help">
                            100 % entspricht der regulären Planleistung.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 FORTSCHRITT UND REQUIREMENTS
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Fortschritt und Traceability
                </legend>

                <div class="cl-fieldset-body">

                    <!-- Automatik -->
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50/40">

                        <input id="task_is_auto" type="checkbox" checked
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-950 focus:ring-blue-900">

                        <span>
                            <span class="block text-sm font-bold text-slate-800">
                                Fortschritt automatisch berechnen
                            </span>

                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                Der Fortschritt wird aus verknüpften und
                                freigegebenen Anforderungen berechnet. Falls
                                Checklistenpunkte vorhanden sind, werden diese
                                ebenfalls berücksichtigt.
                            </span>
                        </span>
                    </label>


                    <!-- Verknüpfte Anforderungen -->
                    <div id="container_linked_reqs" class="relative mt-5 transition-all">

                        <label class="cl-label">
                            Verknüpfte Anforderungen
                        </label>

                        <p class="cl-help">
                            Anforderungen auswählen, deren Freigabestatus den
                            Aufgabenfortschritt beeinflusst.
                        </p>

                        <div id="task_selected_reqs_container"
                            class="mt-3 flex min-h-[54px] flex-wrap gap-2 rounded-md border border-slate-200 bg-slate-50 p-3">
                        </div>

                        <div id="reqMenuContainer" class="relative z-[160] mt-3">
                            <!-- JavaScript erzeugt hier das Auswahlmenü -->
                        </div>

                        <input id="task_linked_reqs" type="hidden">
                    </div>


                    <!-- Manueller Fortschritt -->
                    <div id="container_manual_progress" class="mt-5 hidden transition-all">

                        <label class="cl-label">
                            Manueller Fortschritt in Prozent

                            <input id="task_progress" type="number" min="0" max="100" value="0"
                                class="cl-input max-w-xs font-bold">

                            <span class="cl-help">
                                Wird verwendet, wenn die automatische
                                Fortschrittsberechnung deaktiviert ist.
                            </span>
                        </label>
                    </div>
                </div>
            </fieldset>
        </div>


        <!-- =================================================
             MODAL-FUSS
        ================================================== -->
        <div class="cl-modal-footer">

            <button type="button" onclick="document.getElementById('modalTask').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button type="submit" class="cl-button cl-button-primary">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>

                Aufgabe speichern
            </button>
        </div>
    </form>
</div>


<!-- =========================================================
     ANALYTICS-SLIDE-OVER
========================================================== -->

<!-- Hintergrund -->
<div id="analyticsPanelOverlay"
    class="fixed inset-0 z-[200] hidden cursor-pointer bg-slate-950/30 backdrop-blur-sm transition-opacity"
    onclick="window.closeAnalyticsPanel()">
</div>


<!-- Seitenpanel -->
<div id="analyticsPanel"
    class="fixed right-0 top-0 z-[210] flex h-full w-full max-w-lg translate-x-full transform flex-col border-l border-slate-300 bg-slate-50 shadow-2xl transition-transform duration-300">

    <!-- Panel-Kopf -->
    <div
        class="flex shrink-0 items-start justify-between border-t-4 border-amber-400 bg-blue-950 p-6 text-white shadow-md">

        <div>
            <div class="mb-1 text-xs font-bold uppercase tracking-widest text-amber-400">
                Performance Analyse
            </div>

            <h2 id="analyticsTitle" class="text-xl font-extrabold leading-tight">
                Lade Daten...
            </h2>
        </div>

        <button type="button" onclick="window.closeAnalyticsPanel()"
            class="rounded-md border border-white/20 bg-white/10 p-2 text-slate-200 transition hover:bg-white/20 hover:text-white"
            title="Analyse schließen" aria-label="Analyse schließen">

            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                </path>
            </svg>
        </button>
    </div>


    <!-- Panel-Inhalt -->
    <div class="flex-1 space-y-7 overflow-y-auto p-6">

        <!-- Gesamtfortschritt -->
        <section class="cl-card">

            <div class="cl-card-body">

                <div class="mb-4 flex items-center justify-between">

                    <div>
                        <p class="cl-panel-eyebrow">
                            Fortschritt
                        </p>

                        <h3 class="text-sm font-extrabold text-blue-950">
                            Aktueller Aufgabenstatus
                        </h3>
                    </div>

                    <span id="analyticsTotalProgress" class="text-3xl font-extrabold text-blue-950">
                        0 %
                    </span>
                </div>

                <div class="mb-2 flex items-center justify-between">
                    <span id="analyticsReqCount" class="text-xs font-semibold text-slate-500">
                        0 / 0 freigegeben
                    </span>
                </div>

                <div class="h-3 w-full overflow-hidden rounded-full border border-slate-200 bg-slate-100">

                    <div id="analyticsProgressBar"
                        class="h-full w-0 rounded-full bg-emerald-500 transition-all duration-1000">
                    </div>
                </div>
            </div>
        </section>


        <!-- Checkliste -->
        <section id="analyticsChecklistContainer" class="cl-card hidden">

            <div class="border-b border-slate-200 bg-[#eef2f6] px-4 py-3">

                <p class="cl-panel-eyebrow">
                    Unteraufgaben
                </p>

                <h3 class="text-sm font-extrabold text-blue-950">
                    Checkliste
                </h3>
            </div>

            <div id="analyticsChecklist" class="space-y-2 p-4">
                <!-- JavaScript füllt die Checkliste -->
            </div>
        </section>


        <!-- Contributors -->
        <section class="cl-card">

            <div class="border-b border-slate-200 bg-[#eef2f6] px-4 py-3">

                <p class="cl-panel-eyebrow">
                    Bearbeitung
                </p>

                <h3 class="text-sm font-extrabold text-blue-950">
                    Top Contributors
                </h3>
            </div>

            <div id="analyticsContributors" class="space-y-4 p-4">

                <div class="text-xs italic text-slate-400">
                    Noch keine Beiträge geladen.
                </div>
            </div>
        </section>


        <!-- Detail-Log -->
        <section class="cl-card">

            <div class="border-b border-slate-200 bg-[#eef2f6] px-4 py-3">

                <p class="cl-panel-eyebrow">
                    Traceability
                </p>

                <h3 class="text-sm font-extrabold text-blue-950">
                    Detail-Log
                </h3>
            </div>

            <div id="analyticsReqList" class="space-y-2 p-4">

                <div class="text-xs italic text-slate-400">
                    Noch keine Anforderungsdaten geladen.
                </div>
            </div>
        </section>
    </div>


    <!-- Panel-Fuß -->
    <div class="flex shrink-0 justify-end border-t border-slate-300 bg-[#eef2f6] px-5 py-4">

        <button type="button" onclick="window.closeAnalyticsPanel()" class="cl-button cl-button-secondary">
            Analyse schließen
        </button>
    </div>
</div>