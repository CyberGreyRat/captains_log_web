<!-- dashboard/views/issues.php -->

<div class="flex h-full flex-col overflow-hidden border border-slate-300 bg-white shadow-sm">

  <!-- Kopfbereich -->
  <div class="flex shrink-0 items-center justify-between border-b border-slate-300 bg-white px-5 py-4">

    <div>
      <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
        Fehler · Abweichungen · Rückmeldungen
      </p>

      <h2 class="text-2xl font-extrabold text-blue-950">
        Issues
      </h2>
    </div>

    <div class="flex items-center gap-2">
      <button id="btnImportIssues" type="button"
        class="whitespace-nowrap border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
        Excel importieren
      </button>

      <button id="btnNewIssue" type="button"
        class="whitespace-nowrap border border-blue-950 bg-blue-950 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-blue-900">
        + Neues Issue
      </button>
    </div>
  </div>

  <!-- Kennzahlen -->
  <div class="shrink-0 border-b border-slate-300 bg-[#f8fafc] px-5 py-4">
    <div id="issueKpis" class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          Offen
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          In Bearbeitung
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          Wartet auf Antwort
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          Testbereit
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          Freigegeben
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>

      <div class="border border-slate-300 bg-white p-3">
        <div class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-500">
          Geschlossen
        </div>
        <div class="mt-1 text-2xl font-extrabold text-blue-950">
          0
        </div>
      </div>
    </div>
  </div>

  <!-- Suche und Filter -->
  <div class="flex shrink-0 flex-col gap-2 border-b border-slate-300 bg-white px-5 py-3 md:flex-row">

    <div class="relative flex-1">
      <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none"
        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">

        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z">
        </path>
      </svg>

      <label for="issueSearch" class="sr-only">
        Issues durchsuchen
      </label>

      <input id="issueSearch" type="search" autocomplete="off"
        placeholder="Issue, Titel, Kategorie oder Zuständigkeit suchen"
        class="h-10 w-full border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
    </div>

    <label for="issueStatusFilter" class="sr-only">
      Status auswählen
    </label>

    <select id="issueStatusFilter"
      class="h-10 min-w-[220px] border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">

      <option value="">Alle Status</option>
      <option value="open">Offen</option>
      <option value="in_progress">In Bearbeitung</option>
      <option value="waiting_response">Wartet auf Antwort</option>
      <option value="ready_for_test">Bereit zum Test</option>
      <option value="approved">Freigegeben</option>
      <option value="closed">Geschlossen</option>
      <option value="rejected">Abgelehnt</option>
    </select>
  </div>

  <!-- Tabelle -->
  <div class="min-h-0 flex-1 overflow-auto bg-white">

    <table class="w-full min-w-[1220px] border-collapse text-left text-sm">

      <thead class="sticky top-0 z-10 border-b border-slate-300 bg-[#eef2f6] text-blue-950">
        <tr>
          <th class="w-[115px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Key
          </th>

          <th class="min-w-[400px] px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Meldung
          </th>

          <th class="min-w-[155px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Status
          </th>

          <th class="w-[110px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Priorität
          </th>

          <th class="w-[140px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Kategorie
          </th>

          <th class="w-[160px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Zuständig
          </th>

          <th class="w-[105px] whitespace-nowrap px-3 py-3 text-xs font-extrabold uppercase tracking-wide">
            Links
          </th>

          <th class="w-[125px] whitespace-nowrap px-3 py-3 text-right text-xs font-extrabold uppercase tracking-wide">
            Aktionen
          </th>
        </tr>
      </thead>

      <tbody id="issueTableBody" class="divide-y divide-slate-200">

        <tr>
          <td colspan="8" class="p-8 text-center text-sm italic text-slate-400">
            Bitte zuerst ein Projekt auswählen.
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Fußleiste -->
  <div
    class="flex h-9 shrink-0 items-center justify-between border-t border-slate-300 bg-[#eef2f6] px-4 text-[10px] font-semibold uppercase tracking-wide text-slate-500">

    <span id="issueResultCount">
      0 Issues
    </span>

    <span>
      Erledigte Issues werden am Listenende angezeigt
    </span>
  </div>
</div>


<!-- Issue-Formular -->
<div id="issueModal"
  class="fixed inset-0 z-[220] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

  <form id="issueForm"
    class="mx-auto flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-[5px] border border-slate-400 bg-white shadow-2xl">

    <!-- Formularkopf -->
    <div class="flex shrink-0 items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">

      <div>
        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
          Fehler · Änderung · Rückmeldung
        </p>

        <h2 id="issueModalTitle" class="text-xl font-extrabold text-blue-950">
          Issue bearbeiten
        </h2>
      </div>

      <button id="issueModalClose" type="button"
        class="border border-slate-300 bg-white p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
        aria-label="Fenster schließen">

        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
          </path>
        </svg>
      </button>
    </div>

    <input type="hidden" id="issue_id">

    <!-- Formularinhalt -->
    <div class="flex-1 overflow-y-auto p-6">

      <!-- Allgemeine Angaben -->
      <fieldset class="border border-slate-300">
        <legend class="ml-3 px-2 text-[11px] font-extrabold uppercase tracking-wide text-blue-950">
          Allgemeine Angaben
        </legend>

        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-12">

          <label class="block text-sm font-bold text-slate-700 md:col-span-3">
            Externe Nummer

            <input id="issue_external_id" type="text" placeholder="z.B. EPSA-012"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-3">
            Issue-Typ

            <select id="issue_type"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">

              <option value="bug">Fehler</option>
              <option value="change_request">Change Request</option>
              <option value="customer_feedback">Kundenrückmeldung</option>
              <option value="question">Frage</option>
              <option value="deviation">Abweichung</option>
              <option value="improvement">Verbesserung</option>
            </select>
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-3">
            Status

            <select id="issue_status"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">

              <option value="open">Offen</option>
              <option value="in_progress">In Bearbeitung</option>
              <option value="waiting_response">Wartet auf Antwort</option>
              <option value="ready_for_test">Bereit zum Test</option>
              <option value="approved">Freigegeben</option>
              <option value="closed">Geschlossen</option>
              <option value="rejected">Abgelehnt</option>
            </select>
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-3">
            Priorität

            <select id="issue_priority"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">

              <option value="low">Niedrig</option>
              <option value="medium" selected>Mittel</option>
              <option value="high">Hoch</option>
              <option value="critical">Kritisch</option>
            </select>
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-12">
            Titel / Kurzmeldung

            <input id="issue_title" type="text" required maxlength="255" placeholder="Kurze und eindeutige Beschreibung"
              class="mt-1 w-full border border-slate-400 bg-white p-2.5 text-base font-semibold text-slate-900 outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-12">
            Fehlerbeschreibung / Meldung

            <textarea id="issue_description" rows="4"
              placeholder="Was wurde festgestellt und unter welchen Bedingungen tritt das Problem auf?"
              class="mt-1 w-full resize-y border border-slate-300 bg-white p-2.5 text-sm font-normal leading-6 outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10"></textarea>
          </label>
        </div>
      </fieldset>

      <!-- Organisation -->
      <fieldset class="mt-5 border border-slate-300">
        <legend class="ml-3 px-2 text-[11px] font-extrabold uppercase tracking-wide text-blue-950">
          Organisation und Termine
        </legend>

        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-12">

          <label class="block text-sm font-bold text-slate-700 md:col-span-4">
            Kategorie / Bereich

            <input id="issue_category" type="text" placeholder="z.B. Software, Elektronik, Display"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-4">
            Zuständig

            <select id="issue_assignee"
              class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">

              <option value="">
                -- Niemand zugewiesen --
              </option>
            </select>
          </label>

          <div class="grid grid-cols-2 gap-3 md:col-span-4">

            <label class="block text-sm font-bold text-slate-700">
              Meldedatum

              <input id="issue_reported_at" type="date"
                class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
            </label>

            <label class="block text-sm font-bold text-slate-700">
              Zieltermin

              <input id="issue_due_date" type="date"
                class="mt-1 w-full border border-slate-300 bg-white p-2 text-sm font-normal outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10">
            </label>
          </div>
        </div>
      </fieldset>

      <!-- Kommunikation -->
      <fieldset class="mt-5 border border-slate-300">
        <legend class="ml-3 px-2 text-[11px] font-extrabold uppercase tracking-wide text-blue-950">
          Kommunikation und Bearbeitung
        </legend>

        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">

          <label class="block text-sm font-bold text-slate-700">
            Externe Rückmeldung

            <textarea id="issue_external_response" rows="5"
              placeholder="Rückmeldung des Kunden, Auftraggebers oder Lieferanten"
              class="mt-1 w-full resize-y border border-slate-300 bg-white p-2.5 text-sm font-normal leading-6 outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10"></textarea>
          </label>

          <label class="block text-sm font-bold text-slate-700">
            Interne Rückmeldung

            <textarea id="issue_internal_response" rows="5"
              placeholder="Interne Bewertung und aktueller Bearbeitungsstand"
              class="mt-1 w-full resize-y border border-slate-300 bg-white p-2.5 text-sm font-normal leading-6 outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10"></textarea>
          </label>

          <label class="block text-sm font-bold text-slate-700 md:col-span-2">
            Lösung / Abschlussinformation

            <textarea id="issue_resolution" rows="4"
              placeholder="Wie wurde das Problem gelöst und wie wurde die Lösung geprüft?"
              class="mt-1 w-full resize-y border border-slate-300 bg-white p-2.5 text-sm font-normal leading-6 outline-none focus:border-blue-950 focus:ring-2 focus:ring-blue-950/10"></textarea>
          </label>
        </div>
      </fieldset>

      <!-- Verknüpfungen -->
      <fieldset class="mt-5 border border-slate-300">
        <legend class="ml-3 px-2 text-[11px] font-extrabold uppercase tracking-wide text-blue-950">Traceability und Verknüpfungen</legend>
        <div class="grid grid-cols-1 gap-6 p-4 md:grid-cols-2">
          <div>
            <label for="issueReqSearch" class="block text-sm font-bold text-slate-700">Betroffene Anforderungen</label>
            <p class="mt-1 text-xs text-slate-500">Suchen und direkt per Checkbox auswählen.</p>
            <input id="issueReqSearch" type="search" autocomplete="off" placeholder="ID, Typ oder Titel suchen..."
              class="mt-3 w-full rounded border border-slate-300 p-2 text-sm outline-none focus:border-blue-950">
            <div id="issueReqCheckboxList" class="mt-2 max-h-56 overflow-y-auto overscroll-contain rounded border border-slate-300 bg-white">
              <div class="p-4 text-sm italic text-slate-400">Anforderungen werden geladen...</div>
            </div>
            <input id="issue_requirements" type="hidden">
          </div>
          <div>
            <label for="issueTaskSearch" class="block text-sm font-bold text-slate-700">Umsetzungsaufgaben</label>
            <p class="mt-1 text-xs text-slate-500">Suchen und direkt per Checkbox auswählen.</p>
            <input id="issueTaskSearch" type="search" autocomplete="off" placeholder="WBS, Kategorie oder Titel suchen..."
              class="mt-3 w-full rounded border border-slate-300 p-2 text-sm outline-none focus:border-blue-950">
            <div id="issueTaskCheckboxList" class="mt-2 max-h-56 overflow-y-auto overscroll-contain rounded border border-slate-300 bg-white">
              <div class="p-4 text-sm italic text-slate-400">Aufgaben werden geladen...</div>
            </div>
            <input id="issue_tasks" type="hidden">
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Formularfuß -->
    <div class="flex shrink-0 items-center justify-between border-t border-slate-300 bg-[#f8fafc] px-6 py-4">

      <p class="hidden text-xs text-slate-500 md:block">
        Pflichtfeld: Titel / Kurzmeldung
      </p>

      <div class="flex items-center gap-3">
        <button id="issueCancel" type="button"
          class="whitespace-nowrap border border-slate-300 bg-white px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 transition hover:bg-slate-100">
          Abbrechen
        </button>

        <button type="submit"
          class="whitespace-nowrap border border-blue-950 bg-blue-950 px-6 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:bg-slate-400">
          Issue speichern
        </button>
      </div>
    </div>
  </form>
</div>


<!-- =========================================================
     ISSUE REPORT SLIDE-OVER PANEL ("Auge"-Ansicht)
========================================================== -->
<div id="issueReportOverlay"
  class="fixed inset-0 z-[210] hidden cursor-pointer bg-slate-950/30 backdrop-blur-sm transition-opacity"
  onclick="window.closeIssueReportPanel()">
</div>

<div id="issueReportPanel"
  class="fixed right-0 top-0 z-[220] flex h-full w-full max-w-2xl translate-x-full transform flex-col border-l border-slate-300 bg-slate-50 shadow-2xl transition-transform duration-300">
  <!-- Panel-Kopf -->
  <div
    class="flex shrink-0 items-start justify-between border-t-4 border-blue-600 bg-blue-950 p-6 text-white shadow-md">
    <div>
      <div id="reportIssueKey" class="mb-1 font-mono text-xs font-bold uppercase tracking-widest text-amber-400">
        ISSUE-000
      </div>

      <h2 id="reportIssueTitle" class="text-xl font-extrabold leading-tight">
        Lade Bericht...
      </h2>
    </div>

    <button type="button" onclick="window.closeIssueReportPanel()"
      class="rounded-md border border-white/20 bg-white/10 p-2 text-slate-200 transition hover:bg-white/20 hover:text-white"
      title="Bericht schließen" aria-label="Bericht schließen">
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <!-- Panel-Inhalt -->
  <div id="issueReportBody" class="flex-1 space-y-6 overflow-y-auto p-6 text-sm text-slate-700">
    <!-- Wird dynamisch befüllt -->
  </div>

  <!-- Panel-Fuß -->
  <div class="flex shrink-0 items-center justify-between border-t border-slate-300 bg-[#eef2f6] px-6 py-4">
    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
      Issue-Bericht
    </span>

    <button type="button" onclick="window.closeIssueReportPanel()"
      class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-100">
      Schließen
    </button>
  </div>
</div>



<!-- Excel-Import -->
<div id="issueImportModal"
  class="fixed inset-0 z-[230] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

  <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden border border-slate-400 bg-white shadow-2xl">

    <!-- Importkopf -->
    <div class="flex shrink-0 items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">

      <div>
        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
          Datenübernahme
        </p>

        <h2 class="text-xl font-extrabold text-blue-950">
          Excel-Fehlerliste importieren
        </h2>
      </div>

      <button id="issueImportClose" type="button"
        class="border border-slate-300 bg-white p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
        aria-label="Importfenster schließen">

        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12">
          </path>
        </svg>
      </button>
    </div>

    <!-- Importinhalt -->
    <div class="flex-1 overflow-y-auto p-6">

      <div class="border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
        <p class="font-bold">
          Unterstützte Excel-Spalten
        </p>

        <p class="mt-1 leading-6">
          Erforderlich sind
          <strong>STATUS</strong>
          und
          <strong>Fehler</strong>.

          Zusätzlich können
          <strong>#</strong>,
          <strong>Date</strong>,
          <strong>Rückmeldung EPSA</strong>,
          <strong>interne Rückmeldung</strong>,
          <strong>Kategorie</strong>
          und
          <strong>Zuweisung</strong>
          übernommen werden.
        </p>
      </div>

      <label for="issueExcelFile" class="mt-5 block text-sm font-bold text-slate-700">
        Excel- oder CSV-Datei auswählen
      </label>

      <input id="issueExcelFile" type="file" accept=".xlsx,.xls,.csv"
        class="mt-1 block w-full border border-slate-300 bg-white p-3 text-sm text-slate-700 file:mr-4 file:border-0 file:bg-blue-950 file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:text-white hover:file:bg-blue-900">

      <div class="mt-5">

        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-extrabold uppercase tracking-wide text-blue-950">
            Importvorschau
          </h3>

          <span class="text-xs text-slate-500">
            Maximal 8 Einträge
          </span>
        </div>

        <div id="issueImportPreview"
          class="min-h-[150px] max-h-[360px] overflow-auto border border-slate-300 bg-[#f8fafc] p-4 text-sm text-slate-600">

          <div class="flex min-h-[110px] items-center justify-center italic text-slate-400">
            Noch keine Datei ausgewählt.
          </div>
        </div>
      </div>
    </div>

    <!-- Importfuß -->
    <div class="flex shrink-0 justify-end gap-3 border-t border-slate-300 bg-[#f8fafc] px-6 py-4">

      <button id="issueImportCancel" type="button"
        class="whitespace-nowrap border border-slate-300 bg-white px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 transition hover:bg-slate-100">
        Abbrechen
      </button>

      <button id="issueImportConfirm" type="button" disabled
        class="whitespace-nowrap border border-blue-950 bg-blue-950 px-6 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:border-slate-300 disabled:bg-slate-300 disabled:text-slate-500">
        Importieren
      </button>
    </div>
  </div>


</div>

<!-- Excel-Verarbeitung -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>


