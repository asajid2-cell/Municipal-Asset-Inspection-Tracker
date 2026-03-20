<?php
/**
 * @var array<int, array<string, mixed>> $scenarios
 * @var array<int, array<string, mixed>> $topCandidates
 * @var array<string, string> $errors
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Capital planning</span>
            <h1>Capital Planning</h1>
            <p class="page-note">Generate multi-year scenario plans from asset risk, replacement cost, and lifecycle posture.</p>
        </div>
    </div>

    <form method="post" action="<?= esc(site_url('capital-planning')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="<?= isset($errors['name']) ? 'field has-error' : 'field' ?>">
            <label for="name">Scenario name</label>
            <input id="name" name="name" type="text" value="<?= esc((string) old('name', 'Scenario ' . date('Y-m-d'))) ?>">
            <?php if (isset($errors['name'])): ?><div class="field-error"><?= esc($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="<?= isset($errors['planning_horizon_years']) ? 'field has-error' : 'field' ?>">
            <label for="planning_horizon_years">Horizon years</label>
            <input id="planning_horizon_years" name="planning_horizon_years" type="number" min="1" value="<?= esc((string) old('planning_horizon_years', '10')) ?>">
            <?php if (isset($errors['planning_horizon_years'])): ?><div class="field-error"><?= esc($errors['planning_horizon_years']) ?></div><?php endif; ?>
        </div>
        <div class="<?= isset($errors['annual_budget']) ? 'field has-error' : 'field' ?>">
            <label for="annual_budget">Annual budget</label>
            <input id="annual_budget" name="annual_budget" type="number" min="1" step="0.01" value="<?= esc((string) old('annual_budget', '250000')) ?>">
            <?php if (isset($errors['annual_budget'])): ?><div class="field-error"><?= esc($errors['annual_budget']) ?></div><?php endif; ?>
        </div>
        <div class="field full-width">
            <label for="strategy_notes">Strategy notes</label>
            <textarea id="strategy_notes" name="strategy_notes"><?= esc((string) old('strategy_notes', 'Balance safety-critical renewals against constrained annual budgets.')) ?></textarea>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Generate scenario</button>
        </div>
    </form>

    <section class="content-grid" style="margin-top: 1rem;">
        <article class="panel">
            <h2>Top capital candidates</h2>
            <ul class="bullet-list">
                <?php foreach ($topCandidates as $candidate): ?>
                    <li><?= esc((string) $candidate['asset_code']) ?>: risk <?= esc((string) $candidate['effective_risk']) ?>, $<?= esc(number_format((float) $candidate['replacement_cost'], 2)) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="panel">
            <h2>Recent scenarios</h2>
            <?php if ($scenarios === []): ?>
                <p class="muted">No scenarios yet.</p>
            <?php else: ?>
                <ul class="bullet-list">
                    <?php foreach ($scenarios as $scenario): ?>
                        <li><?= esc((string) $scenario['name']) ?>, <?= esc((string) $scenario['planning_horizon_years']) ?> years, $<?= esc(number_format((float) $scenario['annual_budget'], 2)) ?>/year</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</section>
<?= $this->endSection() ?>
