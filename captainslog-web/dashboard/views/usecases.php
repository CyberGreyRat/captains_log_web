<!-- dashboard/views/usecases.php -->

<div class="cl-panel">

    <!-- =====================================================
         KOPFBEREICH
    ====================================================== -->
    <div class="cl-panel-header">

        <div>
            <p class="cl-panel-eyebrow">
                Akteure · Abläufe · Systemverhalten
            </p>

            <h2 class="cl-panel-title">
                Use Case Management
            </h2>
        </div>

        <button id="btnNewUseCase" type="button" class="cl-button cl-button-primary">

            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neuer Use Case
        </button>
    </div>


    <!-- =====================================================
         INFORMATIONSBEREICH
    ====================================================== -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">

        <!-- Akteur -->
        <div class="cl-kpi">

            <div class="cl-kpi-label">
                Primärer Akteur
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Person, Rolle oder System, das den Ablauf auslöst.
            </div>
        </div>


        <!-- Hauptszenario -->
        <div class="cl-kpi border-l-emerald-500">

            <div class="cl-kpi-label">
                Hauptszenario
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Beschreibt den erfolgreichen und erwarteten Standardablauf.
            </div>
        </div>


        <!-- Alternativen -->
        <div class="cl-kpi border-l-amber-500">

            <div class="cl-kpi-label">
                Alternativ- und Fehlerfälle
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Dokumentiert Abweichungen, Sonderfälle und Fehlerreaktionen.
            </div>
        </div>
    </div>


    <!-- =====================================================
         USE-CASE-TABELLE
    ====================================================== -->
    <div class="cl-table-container">

        <table class="cl-table min-w-[1100px]">

            <thead>
                <tr>
                    <th class="w-[120px]">
                        Key
                    </th>

                    <th class="min-w-[280px]">
                        Titel
                    </th>

                    <th class="min-w-[220px]">
                        Primärer Akteur
                    </th>

                    <th class="min-w-[440px]">
                        Hauptszenario
                    </th>

                    <th class="w-[150px] text-right">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="useCaseTableBody">

                <tr>
                    <td colspan="5" class="cl-empty-state">
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
            Use Case Management
        </span>

        <span>
            Akteure · Vorbedingungen · Abläufe · Fehlerfälle
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: USE CASE ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div id="modalUseCase" class="cl-modal-overlay hidden">

    <form id="formUseCase" class="cl-modal max-w-3xl">

        <!-- =================================================
             MODAL-KOPF
        ================================================== -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Funktionales Systemverhalten
                </p>

                <h2 id="useCaseModalTitle" class="cl-modal-title">
                    Neuer Use Case
                </h2>
            </div>

            <button type="button" onclick="document.getElementById('modalUseCase').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>


        <!-- Technische ID -->
        <input id="uc_id" type="hidden">


        <!-- =================================================
             MODAL-INHALT
        ================================================== -->
        <div class="cl-modal-body">

            <!-- =============================================
                 BASISINFORMATIONEN
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Basisinformationen
                </legend>

                <div class="cl-fieldset-body space-y-5">

                    <!-- Titel -->
                    <label class="cl-label">
                        Titel / Name

                        <input id="uc_title" type="text" required maxlength="255" autocomplete="off"
                            placeholder="z.B. Anlage starten oder Benutzer anmelden"
                            class="cl-input text-base font-semibold">

                        <span class="cl-help">
                            Der Titel sollte das Ziel des Use Cases kurz und
                            eindeutig beschreiben.
                        </span>
                    </label>


                    <!-- Primärer Akteur -->
                    <label class="cl-label">
                        Primärer Akteur

                        <input id="uc_primary_actor" type="text" maxlength="255" autocomplete="off"
                            placeholder="z.B. Bedienperson, Systemadministrator, Kunde oder externes System"
                            class="cl-input">

                        <span class="cl-help">
                            Person, Rolle oder System, das den Use Case startet
                            oder das gewünschte Ergebnis erreichen möchte.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 VORBEDINGUNGEN
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Vorbedingungen
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Preconditions

                        <textarea id="uc_preconditions" rows="4"
                            placeholder="Welche Voraussetzungen müssen erfüllt sein, bevor der Ablauf beginnen kann?"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Beispiele: Das System ist eingeschaltet, eine
                            Verbindung besteht, die Person ist angemeldet oder
                            eine gültige Konfiguration wurde geladen.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 HAUPTSZENARIO
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Hauptszenario
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Erfolgreicher Standardablauf

                        <textarea id="uc_main_scenario" rows="8"
                            placeholder="1. Der Akteur startet den Vorgang.&#10;2. Das System prüft die Eingaben.&#10;3. Das System führt die gewünschte Aktion aus.&#10;4. Das Ergebnis wird angezeigt.&#10;5. Der Vorgang wird abgeschlossen."
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Beschreibe den normalen Erfolgsfall Schritt für
                            Schritt und in zeitlicher Reihenfolge.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 ALTERNATIV- UND FEHLERSZENARIEN
            ============================================== -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Alternativ- und Fehlerszenarien
                </legend>

                <div class="cl-fieldset-body">

                    <label class="cl-label">
                        Alternative Abläufe und Fehlerfälle

                        <textarea id="uc_alt_scenario" rows="6"
                            placeholder="2a. Die Eingaben sind ungültig: Das System zeigt eine verständliche Fehlermeldung.&#10;3a. Die Verbindung ist unterbrochen: Das System bricht den Vorgang sicher ab.&#10;3b. Der Vorgang kann wiederholt werden."
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Erfasse alternative Abläufe, ungültige Eingaben,
                            technische Fehler, Abbruchbedingungen und mögliche
                            Wiederholungen.
                        </span>
                    </label>
                </div>
            </fieldset>


            <!-- =============================================
                 MODELLIERUNGSHINWEIS
            ============================================== -->
            <div class="mt-5 rounded-md border border-blue-200 bg-blue-50 p-4">

                <div class="flex items-start gap-3">

                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" aria-hidden="true">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z">
                        </path>
                    </svg>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-blue-950">
                            Modellierungshinweis
                        </div>

                        <p class="mt-1 text-xs leading-5 text-blue-900">
                            Ein Use Case sollte ein fachliches Ziel des Akteurs
                            beschreiben. Technische Einzelanforderungen gehören
                            in den Reiter Anforderungen und können dort separat
                            spezifiziert und verifiziert werden.
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- =================================================
             MODAL-FUSS
        ================================================== -->
        <div class="cl-modal-footer">

            <button type="button" onclick="document.getElementById('modalUseCase').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button type="submit" class="cl-button cl-button-primary">

                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>

                Use Case speichern
            </button>
        </div>
    </form>
</div>