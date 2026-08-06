// dashboard/js/sbom.js
import { currentProjectId } from './state.js';

export async function loadSBOM() {
    const container = document.getElementById('sbomContainer');
    if (!container) return;

    if (!currentProjectId) {
        container.innerHTML = '<p class="text-slate-400 italic p-4">Bitte wähle zuerst ein Projekt aus.</p>';
        return;
    }
    
    container.innerHTML = '<p class="text-slate-400 italic p-4">Lade und verarbeite SBOM...</p>';
    
    try {
        const res = await fetch(`../api/get_sbom.php?project_id=${currentProjectId}`);
        const textData = await res.text();
        
        if (!res.ok || !textData || textData.includes('"success":false')) {
            container.innerHTML = '<p class="text-slate-400 italic p-4">Keine SBOM für dieses Projekt gefunden. Nutze die CLI (.\\cap push sbom), um eine Stückliste hochzuladen.</p>';
            return;
        }

        let sbom;
        try {
            sbom = JSON.parse(textData);
        } catch (e) {
            container.innerHTML = `<p class="text-red-500 p-4">Das SBOM-Format ist ungültig (kein lesbares JSON).</p>`;
            return;
        }

        // Metadaten aus dem SPDX-Dokument auslesen
        const spdxVersion = sbom.spdxVersion || 'Unbekannt';
        const created = sbom.creationInfo?.created ? new Date(sbom.creationInfo.created).toLocaleString('de-DE') : 'Unbekannt';
        const tool = sbom.creationInfo?.creators?.find(c => c.startsWith('Tool:'))?.replace('Tool:', '').trim() || 'Unbekannt';
        const packages = sbom.packages || [];

        // HTML für Metadaten und Tabellenkopf aufbauen
        let html = `
            <div class="mb-4 text-sm text-slate-300 border-b border-slate-700 pb-4">
                <span class="mr-4"><strong>Format:</strong> ${spdxVersion}</span>
                <span class="mr-4"><strong>Generiert am:</strong> ${created}</span>
                <span class="mr-4"><strong>Scanner-Tool:</strong> ${tool}</span><br>
                <span class="text-blue-400 font-bold mt-2 inline-block">Gesamtanzahl Komponenten: ${packages.length}</span>
            </div>
            
            <div class="overflow-x-auto border border-slate-700 rounded-lg">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-800 text-slate-100 uppercase font-semibold border-b border-slate-700">
                        <tr>
                            <th class="p-3">Paket / Komponente</th>
                            <th class="p-3">Version</th>
                            <th class="p-3">Lizenz</th>
                            <th class="p-3">Lieferant / Quelle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
        `;

        if (packages.length === 0) {
            html += `<tr><td colspan="4" class="p-4 text-center text-slate-500 italic">Keine Pakete in dieser SBOM gefunden.</td></tr>`;
        } else {
            // Jedes Paket als Tabellenzeile hinzufügen
            packages.forEach(pkg => {
                // Lizenzen bereinigen (NOASSERTION bedeutet "nicht angegeben")
                let license = pkg.licenseDeclared || pkg.licenseConcluded || 'Unbekannt';
                if (license === 'NOASSERTION' || license === 'NONE') {
                    license = '<span class="text-slate-500 italic">Nicht angegeben</span>';
                }

                // Lieferant bereinigen ("Organization: " oder "Person: " abschneiden)
                let supplier = pkg.supplier || pkg.originator || 'Unbekannt';
                if (supplier === 'NOASSERTION') {
                    supplier = '<span class="text-slate-500 italic">Unbekannt</span>';
                }
                supplier = supplier.replace('Organization: ', '').replace('Person: ', '');

                const version = pkg.versionInfo || '-';

                html += `
                    <tr class="hover:bg-slate-800/50 transition-colors">
                        <td class="p-3 font-bold text-blue-300">${escapeHtml(pkg.name)}</td>
                        <td class="p-3 font-mono text-emerald-400">${escapeHtml(version)}</td>
                        <td class="p-3 text-xs">${license}</td>
                        <td class="p-3 text-xs text-slate-400">${escapeHtml(supplier)}</td>
                    </tr>
                `;
            });
        }

        html += `
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
        
    } catch (e) {
        console.error("Fehler beim Laden der SBOM:", e);
        container.innerHTML = '<p class="text-red-500 p-4">Kritischer Fehler beim Verarbeiten der SBOM.</p>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}