import { currentProjectId } from './state.js';

let assets = [];

const esc = value =>
    String(value ?? '').replace(/[&<>'"]/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[character]));

function parseAttributes(asset) {
    if (!asset?.attributes) {
        return {};
    }

    if (typeof asset.attributes === 'object') {
        return asset.attributes;
    }

    try {
        return JSON.parse(asset.attributes);
    } catch {
        return {};
    }
}

export async function loadAssets() {
    const tableBody = document.getElementById('assetTableBody');

    if (!tableBody) {
        return;
    }

    if (!currentProjectId) {
        assets = [];

        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="p-8 text-center italic text-slate-400">
                    Bitte zuerst ein Projekt auswählen.
                </td>
            </tr>
        `;

        renderAssetCount(0);
        return;
    }

    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="p-8 text-center italic text-slate-500">
                Assets werden geladen...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            `../api/get_requirements.php?project_id=${encodeURIComponent(currentProjectId)}`
        );

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.error || 'Assets konnten nicht geladen werden.'
            );
        }

        assets = (data.requirements || [])
            .filter(item => item.type === 'AST')
            .sort((a, b) =>
                String(a.req_key).localeCompare(
                    String(b.req_key),
                    'de',
                    { numeric: true }
                )
            );

        fillCategoryFilter();
        renderAssets();
    } catch (error) {
        console.error('Fehler beim Laden der Assets:', error);

        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="p-8 text-center text-red-600">
                    ${esc(error.message)}
                </td>
            </tr>
        `;
    }
}

function fillCategoryFilter() {
    const select = document.getElementById('assetCategoryFilter');

    if (!select) {
        return;
    }

    const currentValue = select.value;

    const categories = [
        ...new Set(
            assets
                .map(asset => parseAttributes(asset).asset_type)
                .filter(Boolean)
        )
    ].sort((a, b) => a.localeCompare(b, 'de'));

    select.innerHTML = `
        <option value="">Alle Asset-Kategorien</option>
        ${categories.map(category => `
            <option value="${esc(category)}">
                ${esc(category)}
            </option>
        `).join('')}
    `;

    if (categories.includes(currentValue)) {
        select.value = currentValue;
    }
}

function renderAssets() {
    const tableBody = document.getElementById('assetTableBody');
    const query = String(
        document.getElementById('assetSearch')?.value || ''
    ).trim().toLowerCase();

    const category = document.getElementById(
        'assetCategoryFilter'
    )?.value || '';

    const filteredAssets = assets.filter(asset => {
        const attributes = parseAttributes(asset);

        const matchesCategory =
            !category || attributes.asset_type === category;

        const searchableText = [
            asset.req_key,
            asset.title,
            asset.description,
            asset.rationale,
            attributes.asset_type,
            attributes.asset_exposure
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return (
            matchesCategory &&
            (!query || searchableText.includes(query))
        );
    });

    if (!filteredAssets.length) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="p-8 text-center italic text-slate-400">
                    Keine Assets gefunden.
                </td>
            </tr>
        `;

        renderAssetCount(0);
        return;
    }

    tableBody.innerHTML = filteredAssets.map(asset => {
        const attributes = parseAttributes(asset);

        const approved =
            asset.review_status === 'Geprüft & Freigegeben';

        const statusClasses = approved
            ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
            : 'border-slate-300 bg-slate-100 text-slate-700';

        return `
            <tr class="border-b border-slate-200 hover:bg-blue-50/40">

                <td class="p-3 align-top font-mono font-bold text-blue-950">
                    ${esc(asset.req_key)}
                </td>

                <td class="p-3 align-top">
                    <div class="font-bold text-slate-900">
                        ${esc(asset.title)}
                    </div>

                    <div class="mt-1 line-clamp-2 text-xs text-slate-500">
                        ${esc(asset.description || '')}
                    </div>
                </td>

                <td class="p-3 align-top">
                    ${esc(attributes.asset_type || '-')}
                </td>

                <td class="p-3 align-top">
                    ${esc(attributes.asset_exposure || '-')}
                </td>

                <td class="p-3 align-top">
                    <span class="inline-flex whitespace-nowrap border px-2 py-1 text-xs font-bold ${statusClasses}">
                        ${esc(asset.review_status || 'Neu')}
                    </span>
                </td>

                <td class="p-3 align-top text-right">
                    <button
                        type="button"
                        onclick="window.editAsset(${Number(asset.id)})"
                        class="font-bold text-blue-700 hover:text-blue-950">
                        Bearbeiten
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    renderAssetCount(filteredAssets.length);
}

function renderAssetCount(count) {
    const element = document.getElementById('assetResultCount');

    if (element) {
        element.textContent = `${count} von ${assets.length} Assets`;
    }
}

function openAssetModal(asset = null) {
    const form = document.getElementById('assetForm');

    if (!form) {
        return;
    }

    form.reset();

    const attributes = parseAttributes(asset);

    document.getElementById('asset_id').value =
        asset?.id || '';

    document.getElementById('asset_title').value =
        asset?.title || '';

    document.getElementById('asset_description').value =
        asset?.description || '';

    document.getElementById('asset_rationale').value =
        asset?.rationale || '';

    document.getElementById('asset_type').value =
        attributes.asset_type || '';

    document.getElementById('asset_exposure').value =
        attributes.asset_exposure || '';

    document.getElementById('asset_review_status').value =
        asset?.review_status || 'Neu';

    document.getElementById('assetModalTitle').textContent =
        asset
            ? `${asset.req_key} bearbeiten`
            : 'Neues Asset';

    document.getElementById('assetModal').classList.remove('hidden');
}

function closeAssetModal() {
    document.getElementById('assetModal')?.classList.add('hidden');
}

export function initAssetEvents() {
    document.getElementById('btnNewAsset')?.addEventListener(
        'click',
        () => {
            if (!currentProjectId) {
                alert('Bitte zuerst ein Projekt wählen.');
                return;
            }

            openAssetModal();
        }
    );

    document.getElementById('assetSearch')?.addEventListener(
        'input',
        renderAssets
    );

    document.getElementById('assetCategoryFilter')?.addEventListener(
        'change',
        renderAssets
    );

    document.getElementById('assetCancel')?.addEventListener(
        'click',
        closeAssetModal
    );

    document.getElementById('assetModalClose')?.addEventListener(
        'click',
        closeAssetModal
    );

    document.getElementById('assetForm')?.addEventListener(
        'submit',
        saveAsset
    );
}

async function saveAsset(event) {
    event.preventDefault();

    const payload = {
        id: document.getElementById('asset_id').value,
        project_id: currentProjectId,
        type: 'AST',
        title: document.getElementById('asset_title').value.trim(),
        description:
            document.getElementById('asset_description').value.trim(),
        rationale:
            document.getElementById('asset_rationale').value.trim(),
        status: 'open',
        review_status:
            document.getElementById('asset_review_status').value,
        parents: [],
        children: [],
        attributes: {
            asset_type:
                document.getElementById('asset_type').value,
            asset_exposure:
                document.getElementById('asset_exposure').value
        }
    };

    try {
        const response = await fetch('../api/set_requirements.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Asset konnte nicht gespeichert werden.');
        }

        closeAssetModal();
        await loadAssets();
    } catch (error) {
        console.error(error);
        alert(`Fehler: ${error.message}`);
    }
}

window.editAsset = function (assetId) {
    const asset = assets.find(
        item => Number(item.id) === Number(assetId)
    );

    if (asset) {
        openAssetModal(asset);
    }
};