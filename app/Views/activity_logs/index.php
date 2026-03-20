<?php
/**
 * @var array<int, array<string, mixed>> $entries
 * @var array<string, string> $filters
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 * @var int $resultTotal
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Audit trail</span>
            <h1>Activity Log</h1>
            <p class="page-note">
                Review who changed operational data, what changed, and when it happened.
            </p>
        </div>
    </div>

    <form class="search-form" method="get" action="<?= esc(site_url('audit-log')) ?>">
        <div class="filter-grid" style="width: 100%;">
            <div class="search-field">
                <label for="q">Search</label>
                <input id="q" name="q" type="search" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Summary, entity, or actor">
            </div>

            <div class="search-field">
                <label for="entity_type">Entity type</label>
                <input id="entity_type" name="entity_type" type="text" value="<?= esc($filters['entity_type'] ?? '') ?>" placeholder="asset, inspection, maintenance_request">
            </div>

            <div class="search-field">
                <label for="action">Action</label>
                <input id="action" name="action" type="text" value="<?= esc($filters['action'] ?? '') ?>" placeholder="created, updated, resolved">
            </div>
        </div>

        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-secondary" href="<?= esc(site_url('audit-log')) ?>">Reset</a>
            <span class="muted"><?= esc((string) $resultTotal) ?> event<?= $resultTotal === 1 ? '' : 's' ?></span>
        </div>
    </form>

    <?php if ($entries === []): ?>
        <div class="empty-state" style="margin-top: 1rem;">
            No audit entries match the current filters.
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                <tr>
                    <th>When</th>
                    <th>Actor</th>
                    <th>Entity</th>
                    <th>Action</th>
                    <th>Summary</th>
                    <th>Metadata</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= esc(date('M j, Y g:i A', strtotime((string) $entry['created_at']))) ?></td>
                        <td>
                            <?= $entry['actor_name'] ? esc((string) $entry['actor_name']) : 'System' ?><br>
                            <?php if ($entry['actor_role']): ?>
                                <span class="muted"><?= esc(ucwords(str_replace('_', ' ', (string) $entry['actor_role']))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= esc((string) $entry['entity_type']) ?><br>
                            <span class="muted">#<?= esc((string) $entry['entity_id']) ?></span>
                        </td>
                        <td><?= esc((string) $entry['action']) ?></td>
                        <td><?= esc((string) $entry['summary']) ?></td>
                        <td>
                            <?php if ($entry['metadata_json']): ?>
                                <code><?= esc((string) $entry['metadata_json']) ?></code>
                            <?php else: ?>
                                <span class="muted">None</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
