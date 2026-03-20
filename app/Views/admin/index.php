<?php
/**
 * @var array<string, mixed>|null $organization
 * @var array<int, array<string, mixed>> $templates
 * @var array<int, array<string, mixed>> $workflowRules
 * @var array<int, array<string, mixed>> $syncJobs
 * @var array<int, array<string, mixed>> $exportJobs
 * @var array<int, array<string, mixed>> $sourceHealth
 * @var array<int, array<string, mixed>> $packets
 * @var array<int, array<string, mixed>> $conflicts
 * @var array<int, array<string, mixed>> $performance
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="hero">
    <div>
        <span class="eyebrow">Admin</span>
        <h1>Admin Console</h1>
        <p class="lede">One place for tenant settings, workflow rules, source operations, exports, mobile diagnostics, and platform telemetry.</p>
        <?php if ($organization !== null): ?>
            <p class="muted"><?= esc((string) $organization['name']) ?>, <?= esc((string) ($organization['region'] ?? '')) ?></p>
        <?php endif; ?>
    </div>
    <div class="hero-actions">
        <a class="button" href="<?= esc(site_url('exports')) ?>">Open Exports</a>
        <a class="button button-secondary" href="<?= esc(site_url('reports')) ?>">Open Reports</a>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <h2>Workflow rules</h2>
        <ul class="bullet-list">
            <?php foreach ($workflowRules as $rule): ?>
                <li><?= esc((string) $rule['name']) ?>: <?= esc((string) $rule['trigger_event']) ?>, <?= (int) $rule['enabled'] === 1 ? 'enabled' : 'disabled' ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="panel">
        <h2>Notification templates</h2>
        <ul class="bullet-list">
            <?php foreach ($templates as $template): ?>
                <li><?= esc((string) $template['template_key']) ?> (<?= esc((string) $template['channel']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="panel">
        <h2>Source health</h2>
        <ul class="bullet-list">
            <?php foreach ($sourceHealth as $snapshot): ?>
                <li><?= esc((string) $snapshot['source_key']) ?>: <?= esc((string) $snapshot['total_assets']) ?> assets, <?= esc((string) $snapshot['duplicate_assets']) ?> duplicates</li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="panel">
        <h2>Open sync conflicts</h2>
        <?php if ($conflicts === []): ?>
            <p class="muted">No unresolved offline conflicts.</p>
        <?php else: ?>
            <ul class="bullet-list">
                <?php foreach ($conflicts as $conflict): ?>
                    <li>Packet #<?= esc((string) $conflict['packet_id']) ?>: <?= esc((string) $conflict['conflict_type']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="panel" style="grid-column: 1 / -1;">
        <h2>Recent sync jobs</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Mode</th>
                    <th>Fetched</th>
                    <th>Imported</th>
                    <th>Updated</th>
                    <th>Skipped</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($syncJobs as $job): ?>
                    <tr>
                        <td><?= esc((string) $job['source_label']) ?></td>
                        <td><?= esc((string) $job['status']) ?></td>
                        <td><?= esc((string) $job['mode']) ?></td>
                        <td><?= esc((string) $job['fetched_count']) ?></td>
                        <td><?= esc((string) $job['imported_count']) ?></td>
                        <td><?= esc((string) $job['updated_count']) ?></td>
                        <td><?= esc((string) $job['skipped_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel" style="grid-column: 1 / -1;">
        <h2>Export jobs and telemetry</h2>
        <div class="content-grid" style="margin-bottom: 0;">
            <div class="panel-inline">
                <strong>Recent exports</strong>
                <ul class="bullet-list">
                    <?php foreach ($exportJobs as $job): ?>
                        <li><?= esc((string) $job['name']) ?>: <?= esc((string) $job['status']) ?>, <?= esc((string) $job['row_count']) ?> rows</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="panel-inline">
                <strong>Recent performance snapshots</strong>
                <ul class="bullet-list">
                    <?php foreach ($performance as $metric): ?>
                        <li><?= esc((string) $metric['snapshot_type']) ?> / <?= esc((string) $metric['metric_key']) ?>: <?= esc((string) $metric['metric_value']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
