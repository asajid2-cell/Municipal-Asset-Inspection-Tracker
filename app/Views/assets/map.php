<?php
/**
 * @var array<string, int|string|null> $filters
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $categories
 * @var list<string> $statuses
 * @var array<string, string> $sortOptions
 * @var array<string, array<string, int|string>> $openDataSources
 * @var array<string, string> $geometryFilters
 * @var array<string, float> $initialCenter
 * @var int $initialZoom
 * @var string $mapApiUrl
 * @var string $tableViewUrl
 * @var string $fullViewUrl
 * @var int $mapFeatureLimit
 */

$canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true);
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="">
<style {csp-style-nonce}>
    .asset-marker {
        display: block;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 6px rgba(21, 41, 36, 0.3);
    }

    .asset-marker--active {
        background: #1f6b5e;
    }

    .asset-marker--inspection {
        background: #c48b00;
    }

    .asset-marker--repair {
        background: #b64234;
    }

    .asset-marker--out {
        background: #6a3f9a;
    }

    .map-breakdown-list {
        margin: 0;
        padding-left: 1.1rem;
        display: grid;
        gap: 0.35rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <h1>Map Inventory</h1>
            <p class="muted">
                Filter the widened Edmonton inventory, move the map, and inspect point, line, and area assets inside the current viewport.
            </p>
        </div>
        <div class="actions-row">
            <a class="button button-secondary" href="<?= esc($tableViewUrl) ?>">Paged View</a>
            <a class="button button-secondary" href="<?= esc($fullViewUrl) ?>">Full Table</a>
            <button id="reset-map-view" class="button button-secondary" type="button">Reset Map</button>
            <?php if ($canEdit): ?>
                <a class="button" href="<?= esc(site_url('assets/new')) ?>">Add Asset</a>
            <?php endif; ?>
        </div>
    </div>

    <form id="map-filter-form" class="filter-grid" method="get" action="<?= esc(site_url('assets/map')) ?>">
        <div class="search-field">
            <label for="q">Search code or name</label>
            <input id="q" name="q" type="search" value="<?= esc((string) $filters['q']) ?>">
        </div>
        <div class="search-field">
            <label for="location">Location text</label>
            <input id="location" name="location" type="search" value="<?= esc((string) $filters['location']) ?>">
        </div>
        <div class="search-field">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id">
                <option value="">All departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= esc((string) $department['id']) ?>" <?= (string) $filters['department_id'] === (string) $department['id'] ? 'selected' : '' ?>>
                        <?= esc((string) $department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= esc((string) $category['id']) ?>" <?= (string) $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= esc((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= esc($status) ?>" <?= (string) $filters['status'] === $status ? 'selected' : '' ?>>
                        <?= esc($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="source_dataset">Public source</label>
            <select id="source_dataset" name="source_dataset">
                <option value="">All sources</option>
                <?php foreach ($openDataSources as $source): ?>
                    <option value="<?= esc((string) $source['dataset_id']) ?>" <?= (string) $filters['source_dataset'] === (string) $source['dataset_id'] ? 'selected' : '' ?>>
                        <?= esc((string) $source['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="geometry_family">Geometry</label>
            <select id="geometry_family" name="geometry_family">
                <option value="">All geometry</option>
                <?php foreach ($geometryFilters as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= (string) $filters['geometry_family'] === $value ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="overdue">Due state</label>
            <select id="overdue" name="overdue">
                <option value="">All assets</option>
                <option value="1" <?= (string) $filters['overdue'] === '1' ? 'selected' : '' ?>>Overdue only</option>
            </select>
        </div>
        <div class="search-field">
            <label for="sort">Sort by</label>
            <select id="sort" name="sort">
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= (string) $filters['sort'] === $value ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-secondary" href="<?= esc(site_url('assets/map')) ?>">Clear</a>
        </div>
    </form>

    <div class="map-shell">
        <div class="map-sidebar">
            <div class="panel">
                <h2>Viewport summary</h2>
                <ul class="bullet-list">
                    <li><strong id="total-filtered-count">0</strong> filtered assets match the current form.</li>
                    <li><strong id="mapped-count">0</strong> mapped assets are visible in the current viewport.</li>
                    <li id="map-status" class="muted">Move or zoom the map to refresh what is shown.</li>
                </ul>
            </div>
            <div class="panel">
                <h2>Loaded mix</h2>
                <p class="page-note" style="margin-top: 0;">
                    Breakdowns below reflect the assets currently drawn on the map, capped at <?= esc((string) $mapFeatureLimit) ?> features for stability.
                </p>
                <div id="loaded-breakdown" class="empty-state">The map has not loaded any asset mix details yet.</div>
            </div>
            <div class="panel">
                <h2>Visible assets</h2>
                <div id="map-asset-list" class="map-asset-list">
                    <div class="empty-state">The map has not loaded any viewport assets yet.</div>
                </div>
            </div>
        </div>
        <div id="asset-map" class="map-canvas" aria-label="Asset map"></div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
<script>
(() => {
    const mapElement = document.getElementById('asset-map');
    const filterForm = document.getElementById('map-filter-form');
    const listElement = document.getElementById('map-asset-list');
    const breakdownElement = document.getElementById('loaded-breakdown');
    const totalCountElement = document.getElementById('total-filtered-count');
    const mappedCountElement = document.getElementById('mapped-count');
    const statusElement = document.getElementById('map-status');
    const resetMapViewButton = document.getElementById('reset-map-view');
    const apiUrl = <?= json_encode($mapApiUrl) ?>;
    const assetBaseUrl = <?= json_encode(site_url('assets')) ?>;
    const canEdit = <?= $canEdit ? 'true' : 'false' ?>;
    const mapFeatureLimit = <?= json_encode($mapFeatureLimit) ?>;

    const statusColors = {
        Active: '#1f6b5e',
        'Needs Inspection': '#c48b00',
        'Needs Repair': '#b64234',
        'Out of Service': '#6a3f9a'
    };

    const map = L.map(mapElement, { preferCanvas: true }).setView(
        [<?= json_encode($initialCenter['lat']) ?>, <?= json_encode($initialCenter['lng']) ?>],
        <?= json_encode($initialZoom) ?>
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const pointClusterLayer = L.markerClusterGroup({
        chunkedLoading: true,
        removeOutsideVisibleBounds: true,
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        maxClusterRadius: 45
    }).addTo(map);
    const shapeLayerGroup = L.layerGroup().addTo(map);
    let firstLoad = true;
    let selectedAssetId = null;
    let refreshTimer = null;
    let requestController = null;
    let requestSerial = 0;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const popupHtml = (asset) => {
        const links = [`<a class="text-link" href="${assetBaseUrl}/${asset.id}">View</a>`];

        if (canEdit) {
            links.push(`<a class="text-link" href="${assetBaseUrl}/${asset.id}/edit">Edit</a>`);
            links.push(`<a class="text-link" href="${assetBaseUrl}/${asset.id}/inspections/new">Log inspection</a>`);
        }

        return `
            <strong>${escapeHtml(asset.asset_code)}</strong><br>
            ${escapeHtml(asset.name)}<br>
            <span class="muted">${escapeHtml(asset.location_text)}</span><br>
            <span class="muted">${escapeHtml(asset.category_name)} &middot; ${escapeHtml(asset.department_name)}</span><br>
            <span class="muted">${escapeHtml(asset.source_dataset || 'manual')} &middot; ${escapeHtml(asset.source_geometry_type || 'Point')}</span><br>
            <div style="margin-top: 0.75rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                ${links.join('')}
            </div>
        `;
    };

    const markerClass = (status) => {
        switch (status) {
            case 'Needs Inspection':
                return 'asset-marker asset-marker--inspection';
            case 'Needs Repair':
                return 'asset-marker asset-marker--repair';
            case 'Out of Service':
                return 'asset-marker asset-marker--out';
            default:
                return 'asset-marker asset-marker--active';
        }
    };

    const markerIcon = (status) => L.divIcon({
        className: '',
        html: `<span class="${markerClass(status)}"></span>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8],
        popupAnchor: [0, -8]
    });

    const listItemHtml = (asset) => {
        const selectedClass = selectedAssetId === asset.id ? ' is-selected' : '';
        const nextDue = asset.next_inspection_due_at ? new Date(asset.next_inspection_due_at).toLocaleDateString() : 'Not scheduled';
        const editLink = canEdit ? `<a class="text-link" href="${assetBaseUrl}/${asset.id}/edit">Edit</a>` : '';

        return `
            <div class="map-asset-card${selectedClass}" data-asset-id="${asset.id}">
                <strong>${escapeHtml(asset.asset_code)}</strong><br>
                ${escapeHtml(asset.name)}<br>
                <span class="muted">${escapeHtml(asset.location_text)}</span><br>
                <span class="muted">${escapeHtml(asset.category_name)} &middot; ${escapeHtml(asset.department_name)}</span><br>
                <span class="muted">${escapeHtml(asset.source_dataset || 'manual')} &middot; ${escapeHtml(asset.source_geometry_type || 'Point')}</span><br>
                <span class="muted">Next due: ${escapeHtml(nextDue)}</span><br>
                <div style="margin-top: 0.65rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a class="text-link" href="${assetBaseUrl}/${asset.id}">View</a>
                    ${editLink}
                </div>
            </div>
        `;
    };

    const renderList = (assets) => {
        if (assets.length === 0) {
            listElement.innerHTML = '<div class="empty-state">No mapped assets matched the current viewport.</div>';
            return;
        }

        const limited = assets.slice(0, 120);
        listElement.innerHTML = limited.map(listItemHtml).join('');

        if (assets.length > limited.length) {
            listElement.insertAdjacentHTML(
                'beforeend',
                `<div class="page-note">Showing the first ${limited.length} visible assets in the sidebar. Zoom further in to narrow the set.</div>`
            );
        }
    };

    const geometryFamily = (asset) => {
        const type = asset.source_geometry_type || '';

        if (type.includes('Polygon')) {
            return 'Area';
        }

        if (type.includes('LineString')) {
            return 'Line';
        }

        return 'Point';
    };

    const renderBreakdown = (assets) => {
        if (assets.length === 0) {
            breakdownElement.innerHTML = 'No mapped assets are loaded right now.';
            return;
        }

        const statusCounts = {
            Active: 0,
            'Needs Inspection': 0,
            'Needs Repair': 0,
            'Out of Service': 0
        };
        const geometryCounts = {
            Point: 0,
            Line: 0,
            Area: 0
        };
        const categoryCounts = new Map();

        assets.forEach((asset) => {
            const status = asset.status || 'Active';
            if (statusCounts[status] !== undefined) {
                statusCounts[status] += 1;
            }

            geometryCounts[geometryFamily(asset)] += 1;

            const category = asset.category_name || 'Unknown';
            categoryCounts.set(category, (categoryCounts.get(category) || 0) + 1);
        });

        const topCategories = [...categoryCounts.entries()]
            .sort((left, right) => right[1] - left[1])
            .slice(0, 4);

        breakdownElement.innerHTML = `
            <ul class="map-breakdown-list">
                <li><strong>Status:</strong> ${statusCounts.Active} active, ${statusCounts['Needs Inspection']} needs inspection, ${statusCounts['Needs Repair']} needs repair, ${statusCounts['Out of Service']} out of service</li>
                <li><strong>Geometry:</strong> ${geometryCounts.Point} points, ${geometryCounts.Line} lines, ${geometryCounts.Area} areas</li>
                <li><strong>Top classes:</strong> ${topCategories.map(([name, total]) => `${escapeHtml(name)} (${total})`).join(', ')}</li>
            </ul>
        `;
    };

    const assetGeometry = (asset) => {
        if (asset.source_geometry) {
            try {
                return JSON.parse(asset.source_geometry);
            } catch (error) {
                return null;
            }
        }

        if (asset.latitude && asset.longitude) {
            return {
                type: 'Point',
                coordinates: [Number(asset.longitude), Number(asset.latitude)]
            };
        }

        return null;
    };

    const featureStyle = (asset, geometryType) => {
        const color = statusColors[asset.status] || '#1f6b5e';

        if (geometryType === 'Point') {
            return {
                radius: 6,
                weight: 1,
                color: '#ffffff',
                fillColor: color,
                fillOpacity: 0.9
            };
        }

        if (geometryType.includes('Polygon')) {
            return {
                color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.22
            };
        }

        return {
            color,
            weight: 3,
            opacity: 0.9
        };
    };

    const renderMap = (assets) => {
        pointClusterLayer.clearLayers();
        shapeLayerGroup.clearLayers();
        let bounds = null;

        assets.forEach((asset) => {
            const geometry = assetGeometry(asset);

            if (!geometry) {
                return;
            }

            if (geometry.type === 'Point') {
                const coordinates = Array.isArray(geometry.coordinates) ? geometry.coordinates : [];
                const lng = Number(coordinates[0] ?? asset.longitude);
                const lat = Number(coordinates[1] ?? asset.latitude);

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    const marker = L.marker([lat, lng], {
                        icon: markerIcon(asset.status)
                    });

                    marker.bindPopup(popupHtml(asset));
                    marker.on('click', () => {
                        selectedAssetId = asset.id;
                        renderList(assets);
                    });

                    pointClusterLayer.addLayer(marker);
                    bounds = bounds === null ? L.latLngBounds([[lat, lng], [lat, lng]]) : bounds.extend([lat, lng]);
                }

                return;
            }

            const featureLayer = L.geoJSON({
                type: 'Feature',
                geometry,
                properties: asset
            }, {
                style: () => featureStyle(asset, geometry.type),
                onEachFeature: (feature, layer) => {
                    layer.bindPopup(popupHtml(feature.properties));
                    layer.on('click', () => {
                        selectedAssetId = feature.properties.id;
                        renderList(assets);
                    });
                }
            });

            featureLayer.addTo(shapeLayerGroup);
            const featureBounds = featureLayer.getBounds();

            if (featureBounds.isValid()) {
                bounds = bounds === null ? featureBounds : bounds.extend(featureBounds);
            }
        });

        if (firstLoad && bounds !== null && bounds.isValid()) {
            map.fitBounds(bounds.pad(0.1));
            firstLoad = false;
        }
    };

    const currentParams = () => {
        const params = new URLSearchParams();
        const formData = new FormData(filterForm);

        for (const [key, value] of formData.entries()) {
            if (value !== '') {
                params.set(key, value);
            }
        }

        const bounds = map.getBounds();
        params.set('north', bounds.getNorth().toFixed(6));
        params.set('south', bounds.getSouth().toFixed(6));
        params.set('east', bounds.getEast().toFixed(6));
        params.set('west', bounds.getWest().toFixed(6));

        return params;
    };

    const filterQueryOnly = () => {
        const params = new URLSearchParams();
        const formData = new FormData(filterForm);

        for (const [key, value] of formData.entries()) {
            if (value !== '') {
                params.set(key, value);
            }
        }

        return params;
    };

    const refreshMap = async () => {
        requestSerial += 1;
        const currentRequest = requestSerial;

        if (requestController !== null) {
            requestController.abort();
        }

        requestController = new AbortController();
        statusElement.textContent = 'Loading assets for the current viewport...';

        try {
            const response = await fetch(`${apiUrl}?${currentParams().toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: requestController.signal
            });

            if (!response.ok) {
                throw new Error(`Map request failed with status ${response.status}`);
            }

            const payload = await response.json();

            if (currentRequest !== requestSerial) {
                return;
            }

            renderMap(payload.data);
            renderList(payload.data);
            renderBreakdown(payload.data);
            totalCountElement.textContent = String(payload.meta.total_filtered_count);
            mappedCountElement.textContent = String(payload.meta.mapped_count);
            if (payload.meta.mapped_count === 0) {
                statusElement.textContent = 'No mapped assets are visible in this viewport. Try zooming out or adjusting the filters.';
            } else if (payload.meta.truncated) {
                statusElement.textContent = `Showing ${payload.meta.mapped_count} of ${payload.meta.viewport_total_count} viewport assets. Zoom in or filter further for a stable map.`;
            } else {
                statusElement.textContent = 'Viewport assets loaded. Click a feature to jump into that record.';
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            statusElement.textContent = error instanceof Error ? error.message : 'The map feed could not be loaded.';
            listElement.innerHTML = '<div class="empty-state">The map feed could not be loaded.</div>';
            breakdownElement.innerHTML = 'The map mix could not be calculated.';
            pointClusterLayer.clearLayers();
            shapeLayerGroup.clearLayers();
        }
    };

    const queueRefresh = () => {
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refreshMap, 250);
    };

    map.on('moveend', queueRefresh);
    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const nextQuery = filterQueryOnly().toString();
        const nextUrl = nextQuery === '' ? filterForm.action : `${filterForm.action}?${nextQuery}`;
        window.history.replaceState({}, '', nextUrl);
        queueRefresh();
    });
    resetMapViewButton.addEventListener('click', () => {
        map.setView(
            [<?= json_encode($initialCenter['lat']) ?>, <?= json_encode($initialCenter['lng']) ?>],
            <?= json_encode($initialZoom) ?>
        );
    });

    refreshMap();
})();
</script>
<?= $this->endSection() ?>
