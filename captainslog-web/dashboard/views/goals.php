<!-- dashboard/views/goals.php -->

<div class="flex h-full flex-col overflow-hidden border border-slate-300 bg-white shadow-sm">

    <div class="flex shrink-0 items-center justify-between border-b border-slate-300 bg-white px-5 py-4">

        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                Strategie · Projektziele · Stakeholder-Erwartungen
            </p>

            <h2 class="text-2xl font-extrabold text-blue-950">
                Ziele
            </h2>
        </div>

        <button
            id="btnNewGoal"
            type="button"
            class="whitespace-nowrap border border-blue-950 bg-blue-950 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-blue-900">
            + Neues Ziel
        </button>
    </div>

    <div class="flex shrink-0 gap-2 border-b border-slate-300 bg-slate-50 px-5 py-3">

        <input
            id="goalSearch"
            type="search"
            placeholder="Ziel, Beschreibung oder Begründung suchen"
            class="h-10 flex-1 border border-slate-300 bg-white px-3 text-sm outline-none focus:border-blue-950">

        <select
            id="goalStatusFilter"
            class="h-10 min-w-[220px] border border-slate-300 bg-white px-3 text-sm font-semibold outline-none focus:border-blue-950">

            <option value="">Alle Prüfstatus</option>
            <option value="Neu">Neu</option>
            <option value="Wartet auf Überprüfung">
                Wartet auf Überprüfung
            </option>
            <option value="Geprüft & Freigegeben">
                Geprüft & Freigegeben
            </option>
            <option value="Abgelehnt">Abgelehnt</option>
        </select>
    </div>

    <div class="min-h-0 flex-1 overflow-auto bg-slate-50 p-5">
        <div
            id="goalCardContainer"
            class="grid grid-cols-1 gap-4 xl:grid-cols-2">

            <div class="col-span-full border border-slate-300 bg-white p-8 text-center italic text-slate-400">
                Bitte zuerst ein Projekt auswählen.
            </div>
        </div>
    </div>

    <div class="flex h-9 shrink-0 items-center justify-between border-t border-slate-300 bg-[#eef2f6] px-4 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
        <span id="goalResultCount">0 Ziele</span>
        <span>Strategische und fachliche Projektziele</span>
    </div>
</div>


<!-- Ziel-Formular -->
<div
    id="goalModal"
    class="fixed inset-0 z-[220] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

    <form
        id="goalForm"
        class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden border border-slate-400 bg-white shadow-2xl">

        <div class="flex items-start justify-between border-b border-slate-300 bg-[#eef2f6] px-6 py-4">

            <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    Projektstrategie
                </p>

                <h2 id="goalModalTitle" class="text-xl font-extrabold text-blue-950">
                    Ziel bearbeiten
                </h2>
            </div>

            <button
                id="goalModalClose"
                type="button"
                class="border border-slate-300 bg-white p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>
        </div>

        <input type="hidden" id="goal_id">

        <div class="flex-1 space-y-5 overflow-y-auto p-6">

            <label class="block text-sm font-bold text-slate-700">
                Zielbezeichnung

                <input
                    id="goal_title"
                    required
                    maxlength="255"
                    placeholder="Was soll im Projekt erreicht werden?"
                    class="mt-1 w-full border border-slate-400 p-2.5 text-base font-semibold outline-none focus:border-blue-950">
            </label>

            <label class="block text-sm font-bold text-slate-700">
                Zielbeschreibung

                <textarea
                    id="goal_description"
                    rows="5"
                    placeholder="Beschreibe das gewünschte Projektergebnis."
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal leading-6 outline-none focus:border-blue-950"></textarea>
            </label>

            <label class="block text-sm font-bold text-slate-700">
                Begründung / Nutzen

                <textarea
                    id="goal_rationale"
                    rows="4"
                    placeholder="Warum ist dieses Ziel wichtig?"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal leading-6 outline-none focus:border-blue-950"></textarea>
            </label>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <label class="block text-sm font-bold text-slate-700">
                    Zuständiger Stakeholder

                    <select
                        id="goal_source_contact"
                        class="mt-1 w-full border border-slate-300 bg-white p-2 outline-none focus:border-blue-950">

                        <option value="">
                            -- Niemand zugewiesen --
                        </option>
                    </select>
                </label>

                <label class="block text-sm font-bold text-slate-700">
                    Aufwand / Zielgröße

                    <input
                        id="goal_effort"
                        placeholder="z.B. bis Q4 / 30 % Einsparung"
                        class="mt-1 w-full border border-slate-300 p-2 outline-none focus:border-blue-950">
                </label>
            </div>

            <label class="block text-sm font-bold text-slate-700">
                Erfolgskriterien

                <textarea
                    id="goal_acceptance_criteria"
                    rows="4"
                    placeholder="- Messbares Erfolgskriterium 1&#10;- Messbares Erfolgskriterium 2"
                    class="mt-1 w-full border border-slate-300 p-2.5 font-normal leading-6 outline-none focus:border-blue-950"></textarea>
            </label>

            <label class="block text-sm font-bold text-slate-700">
                Prüfstatus

                <select
                    id="goal_review_status"
                    class="mt-1 w-full border border-slate-300 bg-white p-2 outline-none focus:border-blue-950">

                    <option value="Neu">Neu</option>
                    <option value="Wartet auf Überprüfung">
                        Wartet auf Überprüfung
                    </option>
                    <option value="Geprüft & Freigegeben">
                        Geprüft & Freigegeben
                    </option>
                    <option value="Abgelehnt">Abgelehnt</option>
                </select>
            </label>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-300 bg-slate-50 px-6 py-4">

            <button
                id="goalCancel"
                type="button"
                class="border border-slate-300 bg-white px-5 py-2 text-xs font-bold uppercase hover:bg-slate-100">
                Abbrechen
            </button>

            <button
                type="submit"
                class="bg-blue-950 px-6 py-2 text-xs font-bold uppercase text-white hover:bg-blue-900">
                Ziel speichern
            </button>
        </div>
    </form>
</div>