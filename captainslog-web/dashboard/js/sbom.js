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

        const spdxVersion = sbom.spdxVersion || 'Unbekannt';
        const created = sbom.creationInfo?.created ? new Date(sbom.creationInfo.created).toLocaleString('de-DE') : 'Unbekannt';
        const tool = sbom.creationInfo?.creators?.find(c => c.startsWith('Tool:'))?.replace('Tool:', '').trim() || 'Unbekannt';
        const packages = sbom.packages || [];

        // 1. DUPLIKATE FILTERN (nur eindeutige Name+Versions-Kombinationen behalten)
        const uniquePackagesMap = new Map();
        packages.forEach(pkg => {
            const name = pkg.name || 'Unbekannt';
            const version = pkg.versionInfo || 'UNKNOWN';
            const key = `${name}@@${version}`;
            
            if (!uniquePackagesMap.has(key)) {
                uniquePackagesMap.set(key, pkg);
            }
        });
        const uniquePackages = Array.from(uniquePackagesMap.values());

        // 2. HELLES HTML AUFBAUEN
        let html = `
            <div class="mb-4 text-sm text-slate-600 border-b border-slate-200 pb-4">
                <span class="mr-4"><strong>Format:</strong> ${escapeHtml(spdxVersion)}</span>
                <span class="mr-4"><strong>Generiert am:</strong> ${escapeHtml(created)}</span>
                <span class="mr-4"><strong>Scanner-Tool:</strong> ${escapeHtml(tool)}</span><br>
                <span class="text-blue-900 font-bold mt-2 inline-block">Gesamtanzahl Komponenten: ${uniquePackages.length} <span class="text-slate-400 font-normal">(gefiltert von ursprünglich ${packages.length})</span></span>
            </div>
            
            <div class="overflow-x-auto border border-slate-200 rounded-lg shadow-sm">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-800 uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Paket / Komponente</th>
                            <th class="p-3">Version</th>
                            <th class="p-3">Lizenz</th>
                            <th class="p-3">Lieferant / Quelle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
        `;

        if (uniquePackages.length === 0) {
            html += `<tr><td colspan="4" class="p-4 text-center text-slate-500 italic">Keine Pakete in dieser SBOM gefunden.</td></tr>`;
        } else {
            uniquePackages.forEach(pkg => {
                // Bereinigung
                let license = pkg.licenseDeclared || pkg.licenseConcluded || 'Unbekannt';
                if (license === 'NOASSERTION' || license === 'NONE') license = 'Nicht angegeben';

                let supplier = pkg.supplier || pkg.originator || 'Unbekannt';
                if (supplier === 'NOASSERTION') supplier = 'Unbekannt';
                supplier = supplier.replace('Organization: ', '').replace('Person: ', '');

                const version = pkg.versionInfo || 'UNKNOWN';

                // CSS-Klassen für fehlende Daten direkt ins <td> statt über escapeHtml
                const licenseStyle = license === 'Nicht angegeben' ? 'text-slate-400 italic' : 'text-slate-700';
                const supplierStyle = supplier === 'Unbekannt' ? 'text-slate-400 italic' : 'text-slate-700';

                html += `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-3 font-bold text-blue-900">${escapeHtml(pkg.name)}</td>
                        <td class="p-3 font-mono text-slate-600">${escapeHtml(version)}</td>
                        <td class="p-3 text-xs ${licenseStyle}">${escapeHtml(license)}</td>
                        <td class="p-3 text-xs ${supplierStyle}">${escapeHtml(supplier)}</td>
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