<?php
/**
 * @var array<string, mixed> $summary
 * @var array<int, array<string, mixed>> $recentScenarios
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="hero">
    <div>
        <span class="eyebrow">Executive view</span>
        <h1>Executive Reports</h1>
        <p class="lede">Portfolio-level reporting for asset condition, source health, maintenance backlog, and capital pressure.</p>
    </div>
    <div class="hero-actions">
        <a class="button" href="<?= esc(site_url('capital-planning')) ?>">Open Capital Planning</a>
        <a class="button button-secondary" href="<?= esc(site_url('digital-twin')) ?>">Open Digital Twin</a>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Assets</span>
        <strong class="stat-value"><?= esc((string) $summary['asset_count']) ?></strong>
        <span class="muted">Active assets in the tenant portfolio.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Overdue inspections</span>
        <strong class="stat-value"><?= esc((string) $summary['overdue_count']) ?></strong>
        <span class="muted">Assets that have passed their next inspection due date.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Open repair backlog</span>
        <strong class="stat-value"><?= esc((string) $summary['repair_backlog_count']) ?></strong>
        <span class="muted">Active maintenance requests still in the queue.</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Average risk</span>
        <strong class="stat-value"><?= esc((string) $summary['average_risk']) ?></strong>
        <span class="muted">Average portfolio risk score across current assets.</span>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <h2>Department scorecards</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Department</th>
                    <th>Assets</th>
                    <th>Needs inspection</th>
                    <th>Needs repair</th>
                    <th>Avg risk</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($summary['department_scorecards'] as $scorecard): ?>
                    <tr>
                        <td><?= esc((string) $scorecard['name']) ?></td>
                        <td><?= esc((string) $scorecard['asset_total']) ?></td>
                        <td><?= esc((string) $scorecard['needs_inspection_total']) ?></td>
                        <td><?= esc((string) $scorecard['needs_repair_total']) ?></td>
                        <td><?= esc((string) $scorecard['avg_risk']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <h2>Maintenance backlog age</h2>
        <ul class="bullet-list">
            <li>Under 7 days: <?= esc((string) $summary['maintenance_age_buckets']['under_7_days']) ?></li>
            <li>8 to 30 days: <?= esc((string) $summary['maintenance_age_buckets']['between_8_and_30_days']) ?></li>
            <li>Over 30 days: <?= esc((string) $summary['maintenance_age_buckets']['over_30_days']) ?></li>
        </ul>
    </article>

    <article class="panel">
        <h2>Source health snapshot</h2>
        <ul class="bullet-list">
            <?php foreach ($summary['source_health'] as $source): ?>
                <li>
                    <?= esc((string) $source['source_key']) ?>:
                    <?= esc((string) $source['total_assets']) ?> total,
                    <?= esc((string) $source['unmapped_assets']) ?> unmapped,
                    <?= esc((string) $source['duplicate_assets']) ?> duplicates
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="panel">
        <h2>Recent planning scenarios</h2>
        <?php if ($recentScenarios === []): ?>
            <p class="muted">No generated scenarios yet.</p>
        <?php else: ?>
            <ul class="bullet-list">
                <?php foreach ($recentScenarios as $scenario): ?>
                    <li><?= esc((string) $scenario['name']) ?>, <?= esc((string) $scenario['planning_horizon_years']) ?> years, $<?= esc(number_format((float) $scenario['annual_budget'], 2)) ?>/year</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="panel" style="grid-column: 1 / -1;">
        <h2>Top capital candidates</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Asset</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Risk</th>
                    <th>Replacement cost</th>
                    <th>Lifecycle</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($summary['top_capital_candidates'] as $candidate): ?>
                    <tr>
                        <td><?= esc((string) $candidate['asset_code']) ?><br><span class="muted"><?= esc((string) $candidate['name']) ?></span></td>
                        <td><?= esc((string) $candidate['department_name']) ?></td>
                        <td><?= esc((string) $candidate['category_name']) ?></td>
                        <td><?= esc((string) $candidate['status']) ?></td>
                        <td><?= esc((string) $candidate['effective_risk']) ?></td>
                        <td>$<?= esc(number_format((float) $candidate['replacement_cost'], 2)) ?></td>
                        <td><?= esc((string) $candidate['lifecycle_state']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
