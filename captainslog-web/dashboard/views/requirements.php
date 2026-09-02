<?php
// dashboard/views/requirements.php
// Framework-freie View fuer Captain's Log Document Studio.
?>

<section id="v4" aria-label="Document Studio">
    <aside class="nav" aria-label="Dokumentnavigation">
        <header>
            <div>
                <small>DOCUMENT STUDIO</small>
                <h2>Dokumente</h2>
            </div>

            <button id="v4New" type="button">
                + Neu
            </button>
        </header>

        <label for="v4Search" class="visually-hidden">
            Dokumente suchen
        </label>

        <input
            id="v4Search"
            type="search"
            placeholder="Suchen ..."
            autocomplete="off"
        >

        <div id="v4Tree" role="tree" aria-label="Dokumentbaum">
            <p class="empty-message">Bitte zuerst ein Projekt auswählen.</p>
        </div>
    </aside>

    <main>
        <section id="v4Empty" aria-live="polite">
            <h2>Dokument auswählen</h2>
            <p>
                Wähle links ein Dokument aus oder erstelle ein neues Dokument.
            </p>
        </section>

        <section id="v4Work" class="hidden">
            <header class="sticky">
                <div>
                    <span id="v4Key" aria-label="Dokumentkennung"></span>

                    <label for="v4Title" class="visually-hidden">
                        Dokumenttitel
                    </label>

                    <input
                        id="v4Title"
                        type="text"
                        placeholder="Dokumenttitel"
                        autocomplete="off"
                    >
                </div>

                <div>
                    <button id="v4Edit" type="button" title="Dokument bearbeiten">✎ Bearbeiten</button>

                    <button id="v4CancelEdit" type="button" class="secondary hidden">Bearbeitung abbrechen</button>

                    <button
                        id="v4Delete"
                        type="button"
                        class="danger"
                    >
                        Löschen
                    </button>

                    <span id="reqSaveState" role="status" aria-live="polite">
                        Bereit
                    </span>

                    <button id="reqSave" type="button">
                        Speichern
                    </button>
                </div>
            </header>

            <div class="grid">
                <article class="paper" aria-label="Dokumentinhalt">
                    <aside
                        id="v4Margin"
                        class="margin"
                        aria-label="Dokumentstatus"
                    ></aside>

                    <div id="requirementsEditor"></div>
                </article>

                <aside class="inspector" aria-label="Dokumentinspektor">
                    <nav aria-label="Inspektorbereiche">
                        <button
                            type="button"
                            data-tab="meta"
                            class="is-active"
                        >
                            Eigenschaften
                        </button>

                        <button type="button" data-tab="trace">
                            Traceability
                        </button>

                        <button type="button" data-tab="verify">
                            Verifikation
                        </button>
                    </nav>

                    <div id="v4Panel"></div>
                </aside>
            </div>
        </section>
    </main>
</section>

<dialog id="v4CreateDialog" class="dlg">
    <form method="dialog">
        <h3>Neues Dokument</h3>

        <label for="v4CreateTitle">
            <span>Titel</span>
            <input
                id="v4CreateTitle"
                type="text"
                autocomplete="off"
                required
            >
        </label>

        <label for="v4CreateType">
            <span>Dokumenttyp</span>
            <select id="v4CreateType" required></select>
        </label>

        <label for="v4CreateTemplate">
            <span>Vorlage</span>
            <select id="v4CreateTemplate">
                <option value="">Leeres Dokument</option>
            </select>
        </label>

        <div class="dlg-actions">
            <button type="button" class="secondary" data-close-dialog="v4CreateDialog">
                Abbrechen
            </button>

            <button id="v4CreateConfirm" type="button">
                Erstellen
            </button>
        </div>
    </form>
</dialog>

<dialog id="v4PickerDialog" class="dlg">
    <form method="dialog">
        <h3 id="v4PickerTitle">Eintrag auswählen</h3>

        <label for="v4PickerSearch" class="picker-search-label">
            <span>Suchen</span>
            <input
                id="v4PickerSearch"
                type="search"
                placeholder="ID, Titel oder Status ..."
                autocomplete="off"
            >
        </label>

        <div
            id="v4PickerList"
            class="picker-list"
            role="listbox"
            aria-label="Verfügbare Einträge"
        ></div>

        <div class="dlg-actions">
            <button type="button" class="secondary" data-close-dialog="v4PickerDialog">
                Schließen
            </button>
        </div>
    </form>
</dialog>

<style>
/* Nur kleine strukturelle Hilfen.
   Das eigentliche Design liegt lokal in dashboard/css/document-studio.css. */
#v4 .hidden {
    display: none !important;
}

#v4 .visually-hidden,
.dlg .visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

#v4 .empty-message {
    margin: 8px 3px;
    color: #68727c;
    font-size: 11px;
    font-style: italic;
}
</style>
