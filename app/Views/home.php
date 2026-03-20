<?php $canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true); ?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="hero">
    <div>
        <span class="eyebrow">Current build</span>
        <h1><?= esc($projectName) ?></h1>
        <p class="lede">
            A realistic municipal operations app built in <code>CodeIgniter 4</code> to track public assets,
            inspection readiness, and maintenance follow-up.
        </p>
        <p class="muted"><?= esc($currentBuild) ?></p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= esc(site_url('reports')) ?>">Open Reports</a>
        <a class="button button-secondary" href="<?= esc(site_url('digital-twin')) ?>">Open Digital Twin</a>
        <a class="button button-secondary" href="<?= esc(site_url('capital-planning')) ?>">Open Capital Planning</a>
        <a class="button" href="<?= esc(site_url('assets')) ?>">Open Asset Inventory</a>
        <a class="button button-secondary" href="<?= esc(site_url('maintenance-requests')) ?>">Open Maintenance Queue</a>
        <a class="button button-secondary" href="<?= esc(site_url('audit-log')) ?>">View Audit Log</a>
        <a class="button button-secondary" href="<?= esc(site_url('notifications')) ?>">Open Notifications</a>
        <?php if ($canEdit): ?>
            <a class="button button-secondary" href="<?= esc(site_url('assets/new')) ?>">Add Asset</a>
        <?php endif; ?>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Total assets</span>
        <strong class="stat-value"><?= esc((string) $assetCount) ?></strong>
        <p>Tracked municipal assets currently in the active inventory.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Overdue inspections</span>
        <strong class="stat-value"><?= esc((string) $overdueCount) ?></strong>
        <p>Assets whose next inspection date has passed and need attention now.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Open maintenance requests</span>
        <strong class="stat-value"><?= esc((string) $openMaintenanceCount) ?></strong>
        <p>Follow-up requests currently open or already in progress.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Average portfolio risk</span>
        <strong class="stat-value"><?= esc((string) $reportSummary['average_risk']) ?></strong>
        <p>Aggregate risk pressure across the current tenant portfolio.</p>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <h2>Status breakdown</h2>
        <ul class="bullet-list">
            <?php foreach ($statusBreakdown as $status => $count): ?>
                <li><?= esc($status) ?>: <?= esc((string) $count) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="panel">
        <h2>Overdue inspections</h2>
        <?php if ($overdueAssets === []): ?>
            <p class="muted">No assets are currently overdue.</p>
        <?php else: ?>
            <ul class="bullet-list">
                <?php foreach ($overdueAssets as $asset): ?>
                    <li>
                        <a class="text-link" href="<?= esc(site_url('assets/' . $asset['id'])) ?>">
                            <?= esc((string) $asset['asset_code']) ?>
                        </a>
                        : <?= esc((string) $asset['name']) ?> due <?= esc(date('M j, Y', strtotime((string) $asset['next_inspection_due_at']))) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h2>Maintenance queue</h2>
        <?php if ($openMaintenanceRequests === []): ?>
            <p class="muted">No open maintenance requests right now.</p>
        <?php else: ?>
            <ul class="bullet-list">
                <?php foreach ($openMaintenanceRequests as $request): ?>
                    <li>
                        <a class="text-link" href="<?= esc(site_url('maintenance-requests')) ?>">
                            <?= esc((string) $request['title']) ?>
                        </a>
                        : <?= esc((string) $request['asset_code']) ?>,
                        <?= esc((string) $request['priority']) ?> priority,
                        <?= esc((string) $request['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h2>Recent notifications</h2>
        <?php if ($recentNotifications === []): ?>
            <p class="muted">No notifications have been captured yet.</p>
        <?php else: ?>
            <ul class="bullet-list">
                <?php foreach ($recentNotifications as $delivery): ?>
                    <li>
                        <a class="text-link" href="<?= esc(site_url('notifications')) ?>">
                            <?= esc((string) $delivery['recipient_email']) ?>
                        </a>
                        : <?= esc((string) $delivery['subject']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($canEdit): ?>
            <form method="post" action="<?= esc(site_url('notifications/overdue-reminders')) ?>" style="margin-top: 1rem;">
                <?= csrf_field() ?>
                <button class="button" type="submit">Capture overdue reminders</button>
            </form>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h2>Capital and export activity</h2>
        <ul class="bullet-list">
            <?php foreach ($recentScenarios as $scenario): ?>
                <li>Scenario: <?= esc((string) $scenario['name']) ?></li>
            <?php endforeach; ?>
            <?php foreach ($recentExports as $job): ?>
                <li>Export: <?= esc((string) $job['name']) ?>, <?= esc((string) $job['status']) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<p class="page-note">
    Environment: <?= esc(ENVIRONMENT) ?>. Page rendered in {elapsed_time} seconds using {memory_usage} MB.
</p>
<?= $this->endSection() ?>
