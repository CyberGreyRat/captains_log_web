<?php
// dashboard/views/requirements.php
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

        <input id="v4Search" type="search" placeholder="Suchen ..." autocomplete="off">

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

                    <input id="v4Title" type="text" placeholder="Dokumenttitel" autocomplete="off">
                </div>

                <div>
                    <button id="v4Edit" type="button" title="Dokument bearbeiten">✎ Bearbeiten</button>

                    <button id="v4CancelEdit" type="button" class="secondary hidden">Bearbeitung abbrechen</button>

                    <button id="v4Delete" type="button" class="danger">
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
                    <aside id="v4Margin" class="margin" aria-label="Dokumentstatus"></aside>

                    <div id="requirementsEditor"></div>
                </article>

                <aside class="inspector" aria-label="Dokumentinspektor">
                    <nav aria-label="Inspektorbereiche">
                        <button type="button" data-tab="meta" class="is-active">
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

<!-- =================================================================
     MODALS IM EINHEITLICHEN CAPTAIN'S LOG DESIGN (.cl-modal)
================================================================== -->
<div id="v4CreateDialog" class="cl-modal-overlay hidden fixed inset-0 z-[300] items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <form class="cl-modal mx-auto flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded bg-white shadow-2xl">
        <div class="cl-modal-header">
            <div>
                <p class="cl-panel-eyebrow">Document Studio</p>
                <h2 class="cl-modal-title">Neues Dokument</h2>
            </div>
            <button type="button" class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" data-close-modal="v4CreateDialog">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="cl-modal-body">
            <fieldset class="cl-fieldset">
                <legend class="cl-legend">Basisinformationen</legend>
                <div class="cl-fieldset-body space-y-5">
                    <label class="cl-label">Titel
                        <input id="v4CreateTitle" type="text" autocomplete="off" required class="cl-input mt-1">
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <label class="cl-label">Dokumenttyp
                            <select id="v4CreateType" required class="cl-select mt-1"></select>
                        </label>
                        <label class="cl-label">Vorlage
                            <select id="v4CreateTemplate" class="cl-select mt-1">
                                <option value="">Leeres Dokument</option>
                            </select>
                        </label>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="cl-modal-footer">
            <button type="button" class="cl-button cl-button-secondary" data-close-modal="v4CreateDialog">Abbrechen</button>
            <button id="v4CreateConfirm" type="button" class="cl-button cl-button-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Erstellen
            </button>
        </div>
    </form>
</div>

<div id="v4PickerDialog" class="cl-modal-overlay hidden fixed inset-0 z-[300] items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <div class="cl-modal mx-auto flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded bg-white shadow-2xl">
        <div class="cl-modal-header">
            <div>
                <p class="cl-panel-eyebrow">Zuweisung</p>
                <h2 id="v4PickerTitle" class="cl-modal-title">Eintrag auswählen</h2>
            </div>
            <button type="button" class="cl-button cl-button-secondary min-h-0 px-2.5 py-2" data-close-modal="v4PickerDialog">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="cl-modal-body p-0 flex flex-col min-h-[50vh]">
            <div class="p-4 border-b border-slate-200 bg-slate-50 shrink-0">
                <label class="cl-label">Suchen
                    <input id="v4PickerSearch" type="search" placeholder="ID, Titel oder Status ..." autocomplete="off" class="cl-input mt-1">
                </label>
            </div>
            <div id="v4PickerList" class="flex-1 overflow-y-auto p-4 bg-slate-50 space-y-2" role="listbox" aria-label="Verfügbare Einträge"></div>
        </div>

        <div class="cl-modal-footer">
            <button type="button" class="cl-button cl-button-secondary" data-close-modal="v4PickerDialog">Schließen</button>
        </div>
    </div>
</div>

<style>
/* CSS-Korrekturen für das Document Studio */
#v4 .hidden { display: none !important; }
#v4 .visually-hidden { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important; }
#v4 .empty-message { margin: 8px 3px; color: #68727c; font-size: 11px; font-style: italic; }

/* Volle Bildschirmbreite für den Hauptarbeitsbereich */
#v4 .grid {
    grid-template-columns: minmax(0, 1fr) 325px !important;
    max-width: 100% !important;
}
</style>