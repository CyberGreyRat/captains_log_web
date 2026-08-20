<!-- dashboard/views/userstories.php -->

<div class="cl-panel">

    <!-- Kopfbereich -->
    <div class="cl-panel-header">

        <div>
            <p class="cl-panel-eyebrow">
                Nutzerbedarf · Mehrwert · Akzeptanzkriterien
            </p>

            <h2 class="cl-panel-title">
                User Stories
            </h2>
        </div>

        <button
            id="btnNewUserStory"
            type="button"
            class="cl-button cl-button-primary">

            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4">
                </path>
            </svg>

            Neue User Story
        </button>
    </div>


    <!-- Informationsbereich -->
    <div class="grid shrink-0 grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-3">

        <div class="cl-kpi">
            <div class="cl-kpi-label">
                Als
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Rolle oder Person, für die eine Funktion benötigt wird.
            </div>
        </div>

        <div class="cl-kpi border-l-blue-500">
            <div class="cl-kpi-label">
                Möchte ich
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Gewünschte Funktion, Aktion oder Fähigkeit.
            </div>
        </div>

        <div class="cl-kpi border-l-emerald-500">
            <div class="cl-kpi-label">
                So dass
            </div>

            <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">
                Fachlicher Nutzen und erwarteter Mehrwert.
            </div>
        </div>
    </div>


    <!-- Tabelle -->
    <div class="cl-table-container">

        <table class="cl-table min-w-[1150px]">

            <thead>
                <tr>
                    <th class="w-[120px]">
                        Key
                    </th>

                    <th class="min-w-[250px]">
                        Titel
                    </th>

                    <th class="min-w-[190px]">
                        Als
                    </th>

                    <th class="min-w-[390px]">
                        Möchte ich
                    </th>

                    <th class="w-[130px] text-center">
                        Story Points
                    </th>

                    <th class="w-[150px] text-right">
                        Aktionen
                    </th>
                </tr>
            </thead>

            <tbody id="userStoryTableBody">

                <tr>
                    <td
                        colspan="6"
                        class="cl-empty-state">
                        Bitte zuerst ein Projekt auswählen.
                    </td>
                </tr>

            </tbody>
        </table>
    </div>


    <!-- Fußbereich -->
    <div class="cl-panel-footer">

        <span>
            User Story Management
        </span>

        <span>
            Rolle · Aktion · Nutzen · Akzeptanzkriterien
        </span>
    </div>
</div>


<!-- =========================================================
     MODAL: USER STORY ERSTELLEN ODER BEARBEITEN
========================================================== -->
<div
    id="modalUserStory"
    class="cl-modal-overlay hidden">

    <form
        id="formUserStory"
        class="cl-modal max-w-3xl">

        <!-- Modalkopf -->
        <div class="cl-modal-header">

            <div>
                <p class="cl-panel-eyebrow">
                    Agile Anforderungsbeschreibung
                </p>

                <h2
                    id="userStoryModalTitle"
                    class="cl-modal-title">
                    Neue User Story
                </h2>
            </div>

            <button
                type="button"
                onclick="document.getElementById('modalUserStory').classList.add('hidden')"
                class="cl-button cl-button-secondary min-h-0 px-2.5 py-2"
                title="Fenster schließen"
                aria-label="Fenster schließen">

                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18 18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>


        <!-- Technische ID -->
        <input
            id="us_id"
            type="hidden">


        <!-- Modalinhalt -->
        <div class="cl-modal-body">

            <!-- Basisinformationen -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Basisinformationen
                </legend>

                <div class="cl-fieldset-body">

                    <label
                        for="us_title"
                        class="cl-label">
                        Titel / Kurzbezeichnung
                    </label>

                    <input
                        id="us_title"
                        type="text"
                        required
                        maxlength="255"
                        autocomplete="off"
                        placeholder="z.B. Messwerte im Zeitverlauf anzeigen"
                        class="cl-input text-base font-semibold">

                    <span class="cl-help">
                        Kurze und eindeutige Bezeichnung der User Story.
                    </span>
                </div>
            </fieldset>


            <!-- User-Story-Formulierung -->
            <fieldset class="cl-fieldset border-blue-200 bg-blue-50/30">

                <legend class="cl-legend bg-blue-50 text-blue-950">
                    User-Story-Formulierung
                </legend>

                <div class="cl-fieldset-body space-y-5">

                    <!-- Rolle -->
                    <div>
                        <label
                            for="us_role"
                            class="cl-label text-blue-950">

                            <span class="flex items-center gap-2">

                                <span class="flex h-6 w-6 items-center justify-center rounded border border-blue-200 bg-white text-[10px] font-extrabold text-blue-950">
                                    1
                                </span>

                                Als Rolle oder Person
                            </span>
                        </label>

                        <input
                            id="us_role"
                            type="text"
                            maxlength="255"
                            autocomplete="off"
                            placeholder="z.B. Endanwender, Servicetechniker oder Systemadministrator"
                            class="cl-input">

                        <span class="cl-help">
                            Wer benötigt die Funktion oder profitiert vom Ergebnis?
                        </span>
                    </div>


                    <!-- Aktion -->
                    <div>
                        <label
                            for="us_action"
                            class="cl-label text-blue-950">

                            <span class="flex items-center gap-2">

                                <span class="flex h-6 w-6 items-center justify-center rounded border border-blue-200 bg-white text-[10px] font-extrabold text-blue-950">
                                    2
                                </span>

                                Möchte ich
                            </span>
                        </label>

                        <textarea
                            id="us_action"
                            rows="4"
                            placeholder="z.B. den Temperaturverlauf der letzten 24 Stunden anzeigen können"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Gewünschte Funktion oder Aktion aus Sicht der Rolle.
                        </span>
                    </div>


                    <!-- Nutzen -->
                    <div>
                        <label
                            for="us_benefit"
                            class="cl-label text-blue-950">

                            <span class="flex items-center gap-2">

                                <span class="flex h-6 w-6 items-center justify-center rounded border border-blue-200 bg-white text-[10px] font-extrabold text-blue-950">
                                    3
                                </span>

                                So dass
                            </span>
                        </label>

                        <textarea
                            id="us_benefit"
                            rows="4"
                            placeholder="z.B. ich Veränderungen frühzeitig erkennen und nachvollziehen kann"
                            class="cl-textarea"></textarea>

                        <span class="cl-help">
                            Fachlicher Nutzen oder erwarteter Mehrwert.
                        </span>
                    </div>


                    <!-- Formulierungshilfe -->
                    <div class="rounded-md border border-blue-200 bg-white p-4">

                        <div class="mb-2 text-[10px] font-extrabold uppercase tracking-wide text-blue-950">
                            Empfohlene Form
                        </div>

                        <p class="text-sm leading-6 text-slate-700">
                            Als <strong>[Rolle]</strong> möchte ich
                            <strong>[Aktion]</strong>, so dass
                            <strong>[Nutzen]</strong>.
                        </p>
                    </div>
                </div>
            </fieldset>


            <!-- Akzeptanzkriterien -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Akzeptanzkriterien
                </legend>

                <div class="cl-fieldset-body">

                    <label
                        for="us_acceptance_criteria"
                        class="cl-label">
                        Prüfkriterien
                    </label>

                    <textarea
                        id="us_acceptance_criteria"
                        rows="6"
                        placeholder="- Kriterium 1&#10;- Kriterium 2&#10;- Kriterium 3"
                        class="cl-textarea"></textarea>

                    <span class="cl-help">
                        Ein überprüfbares Kriterium pro Zeile. Die Kriterien
                        beschreiben, wann die User Story vollständig umgesetzt ist.
                    </span>
                </div>
            </fieldset>


            <!-- Aufwandsschätzung -->
            <fieldset class="cl-fieldset">

                <legend class="cl-legend">
                    Aufwandsschätzung
                </legend>

                <div class="cl-fieldset-body">

                    <label
                        for="us_story_points"
                        class="cl-label">
                        Story Points
                    </label>

                    <input
                        id="us_story_points"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="z.B. 3"
                        class="cl-input max-w-xs font-bold">

                    <span class="cl-help">
                        Häufig verwendete Werte sind 1, 2, 3, 5, 8 und 13.
                    </span>
                </div>
            </fieldset>


            <!-- Hinweis -->
            <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-4">

                <div class="flex items-start gap-3">

                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v3m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                        </path>
                    </svg>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-amber-900">
                            Modellierungshinweis
                        </div>

                        <p class="mt-1 text-xs leading-5 text-amber-900">
                            Detaillierte technische Vorgaben, Schnittstellen
                            und Testfälle gehören in den Reiter Anforderungen.
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal-Fuß -->
        <div class="cl-modal-footer">

            <button
                type="button"
                onclick="document.getElementById('modalUserStory').classList.add('hidden')"
                class="cl-button cl-button-secondary">
                Abbrechen
            </button>

            <button
                type="submit"
                class="cl-button cl-button-primary">

                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7">
                    </path>
                </svg>

                User Story speichern
            </button>
        </div>
    </form>
</div>