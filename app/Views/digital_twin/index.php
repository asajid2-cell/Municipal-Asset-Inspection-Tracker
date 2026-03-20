<?php
/**
 * @var array<string, mixed> $summary
 * @var array<int, array<string, mixed>> $linearAssets
 * @var string $mapApiUrl
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="hero">
    <div>
        <span class="eyebrow">Operations twin</span>
        <h1>Digital Twin</h1>
        <p class="lede">A consolidated operations view across mapped assets, risk pressure, and linear network context.</p>
    </div>
    <div class="hero-actions">
        <a class="button" href="<?= esc(site_url('assets/map')) ?>">Open Full Map</a>
        <a class="button button-secondary" href="<?= esc(site_url('reports')) ?>">Open Reports</a>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Assets</span>
        <strong class="stat-value"><?= esc((string) $summary['asset_count']) ?></strong>
        <span class="muted">Portfolio entities represented in the twin.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Overdue</span>
        <strong class="stat-value"><?= esc((string) $summary['overdue_count']) ?></strong>
        <span class="muted">Inspection pressure currently visible in the operating model.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Backlog</span>
        <strong class="stat-value"><?= esc((string) $summary['repair_backlog_count']) ?></strong>
        <span class="muted">Active repair work that still feeds the twin state.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Average risk</span>
        <strong class="stat-value"><?= esc((string) $summary['average_risk']) ?></strong>
        <span class="muted">Risk pressure across the current operating portfolio.</span>
    </article>
</section>

<section class="map-shell">
    <div class="map-sidebar">
        <div class="panel">
            <h2>Linear network context</h2>
            <ul class="bullet-list">
                <?php foreach ($linearAssets as $linear): ?>
                    <li><?= esc((string) $linear['corridor_name']) ?>: <?= esc((string) $linear['asset_code']) ?>, <?= esc((string) $linear['segment_length_m']) ?> m</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="panel">
            <h2>Top capital pressure</h2>
            <ul class="bullet-list">
                <?php foreach ($summary['top_capital_candidates'] as $candidate): ?>
                    <li><?= esc((string) $candidate['asset_code']) ?>: risk <?= esc((string) $candidate['effective_risk']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div id="twin-map" class="map-canvas"></div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(() => {
    const map = L.map('twin-map').setView([53.5461, -113.4938], 11);
    const apiUrl = <?= json_encode($mapApiUrl) ?>;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const layer = L.layerGroup().addTo(map);

    const loadAssets = async () => {
        const bounds = map.getBounds();
        const params = new URLSearchParams({
            north: bounds.getNorth().toFixed(6),
            south: bounds.getSouth().toFixed(6),
            east: bounds.getEast().toFixed(6),
            west: bounds.getWest().toFixed(6),
        });
        const response = await fetch(`${apiUrl}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();
        layer.clearLayers();

        payload.data.forEach((asset) => {
            if (!asset.latitude || !asset.longitude) {
                return;
            }

            L.circleMarker([asset.latitude, asset.longitude], {
                radius: 5,
                color: '#ffffff',
                weight: 1,
                fillColor: asset.status === 'Needs Repair' ? '#b64234' : (asset.status === 'Needs Inspection' ? '#c48b00' : '#1f6b5e'),
                fillOpacity: 0.9
            }).bindPopup(`${asset.asset_code}<br>${asset.name}`).addTo(layer);
        });
    };

    map.on('moveend', loadAssets);
    loadAssets();
})();
</script>
<?= $this->endSection() ?>
