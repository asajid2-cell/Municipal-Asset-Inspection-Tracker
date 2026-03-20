<?php
/**
 * @var array<int, array<string, mixed>> $assets
 * @var CodeIgniter\Pager\Pager $pager
 * @var array<string, int|string|null> $filters
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $categories
 * @var array<string, mixed>|null $importReport
 * @var array<string, mixed>|null $syncReport
 * @var array<string, array<string, int|string>> $openDataSources
 * @var array<string, string> $geometryFilters
 * @var string $importTemplateUrl
 * @var list<string> $statuses
 * @var array<string, string> $sortOptions
 * @var array<string, mixed> $summary
 */

$statusClass = static function (string $status): string {
    return match ($status) {
        'Needs Inspection' => 'pill pill-inspection',
        'Needs Repair' => 'pill pill-repair',
        'Out of Service' => 'pill pill-out',
        default => 'pill pill-active',
    };
};
$canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true);
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <h1>Asset Inventory</h1>
            <p class="muted">
                Use the filters below to work across parks, drainage, streets, and other public asset classes from the Edmonton catalog.
            </p>
        </div>
        <?php if ($canEdit): ?>
            <div class="actions-row">
                <a class="button button-secondary" href="<?= esc(site_url('assets/map')) ?>">Map View</a>
                <a class="button button-secondary" href="<?= esc(site_url('assets/full')) ?>">Full Table</a>
                <a class="button" href="<?= esc(site_url('assets/new')) ?>">Add Asset</a>
            </div>
        <?php else: ?>
            <div class="actions-row">
                <a class="button button-secondary" href="<?= esc(site_url('assets/map')) ?>">Map View</a>
                <a class="button button-secondary" href="<?= esc(site_url('assets/full')) ?>">Full Table</a>
            </div>
        <?php endif; ?>
    </div>

    <form class="filter-grid" method="get" action="<?= esc(site_url('assets')) ?>">
        <div class="search-field">
            <label for="q">Search code or name</label>
            <input
                id="q"
                name="q"
                type="search"
                value="<?= esc((string) $filters['q']) ?>"
                placeholder="Try FAC-HVAC or Riverfront"
            >
        </div>

        <div class="search-field">
            <label for="location">Location text</label>
            <input
                id="location"
                name="location"
                type="search"
                value="<?= esc((string) $filters['location']) ?>"
                placeholder="Community Centre or 107 Avenue"
            >
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
            <a class="button button-secondary" href="<?= esc(site_url('assets')) ?>">Clear</a>
            <a class="button button-secondary" href="<?= esc($csvUrl) ?>">Export CSV</a>
        </div>
    </form>

    <div class="panel panel-inline" style="margin-bottom: 1rem;">
        <strong><?= esc((string) $resultTotal) ?></strong> assets match the current filter set.
    </div>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Mapped assets</span>
            <strong class="stat-value"><?= esc((string) ($summary['mapped_count'] ?? 0)) ?></strong>
            <span class="muted"><?= esc((string) ($summary['unmapped_count'] ?? 0)) ?> unmapped assets remain in the result set.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Needs inspection</span>
            <strong class="stat-value"><?= esc((string) (($summary['status_breakdown']['Needs Inspection'] ?? 0))) ?></strong>
            <span class="muted">Use this to prioritize overdue field work.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Needs repair</span>
            <strong class="stat-value"><?= esc((string) (($summary['status_breakdown']['Needs Repair'] ?? 0))) ?></strong>
            <span class="muted">Active repair candidates in the current result set.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Geometry mix</span>
            <strong class="stat-value"><?= esc((string) (($summary['point_count'] ?? 0))) ?></strong>
            <span class="muted">
                Points, <?= esc((string) (($summary['line_count'] ?? 0))) ?> lines, <?= esc((string) (($summary['polygon_count'] ?? 0))) ?> areas
            </span>
        </article>
    </section>

    <?php if ($canEdit): ?>
        <div class="panel" style="margin-bottom: 1rem;">
            <div class="toolbar" style="margin-bottom: 0.75rem;">
                <div>
                    <h2>Large export job</h2>
                    <p class="muted">Use the durable export job path for large filtered datasets so the browser does not need to hold the full response open.</p>
                </div>
                <a class="button button-secondary" href="<?= esc(site_url('exports')) ?>">View export jobs</a>
            </div>
            <form method="post" action="<?= esc(site_url('exports/assets')) ?>" class="actions-row">
                <?= csrf_field() ?>
                <input type="hidden" name="q" value="<?= esc((string) $filters['q']) ?>">
                <input type="hidden" name="location" value="<?= esc((string) $filters['location']) ?>">
                <input type="hidden" name="category_id" value="<?= esc((string) ($filters['category_id'] ?? '')) ?>">
                <input type="hidden" name="department_id" value="<?= esc((string) ($filters['department_id'] ?? '')) ?>">
                <input type="hidden" name="status" value="<?= esc((string) $filters['status']) ?>">
                <input type="hidden" name="overdue" value="<?= esc((string) $filters['overdue']) ?>">
                <input type="hidden" name="source_dataset" value="<?= esc((string) $filters['source_dataset']) ?>">
                <input type="hidden" name="geometry_family" value="<?= esc((string) $filters['geometry_family']) ?>">
                <input type="hidden" name="sort" value="<?= esc((string) $filters['sort']) ?>">
                <input name="name" type="text" value="Filtered asset export" style="max-width: 260px;">
                <button class="button" type="submit">Generate export</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (($summary['top_categories'] ?? []) !== []): ?>
        <div class="panel" style="margin-bottom: 1rem;">
            <h2>Top asset classes in this result set</h2>
            <ul class="bullet-list">
                <?php foreach ($summary['top_categories'] as $category): ?>
                    <li><?= esc((string) $category['name']) ?>: <?= esc((string) $category['total']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($canEdit): ?>
        <div class="panel" style="margin-bottom: 1rem;">
            <div class="toolbar" style="margin-bottom: 0.75rem;">
                <div>
                    <h2>Import assets from CSV</h2>
                    <p class="muted">
                        Use department codes and category names from the existing seeded reference data. Empty optional fields are allowed.
                    </p>
                </div>
                <a class="button button-secondary" href="<?= esc($importTemplateUrl) ?>">Download template</a>
            </div>

            <form method="post" action="<?= esc(site_url('assets/import')) ?>" enctype="multipart/form-data" class="actions-row">
                <?= csrf_field() ?>
                <input name="asset_import" type="file" accept=".csv" required>
                <button class="button" type="submit">Import CSV</button>
            </form>

            <?php if (is_array($importReport)): ?>
                <div class="page-note">
                    Imported <?= esc((string) ($importReport['imported_count'] ?? 0)) ?> asset<?= (int) ($importReport['imported_count'] ?? 0) === 1 ? '' : 's' ?>.
                </div>

                <?php if (($importReport['errors'] ?? []) !== []): ?>
                    <div class="empty-state" style="margin-top: 0.75rem;">
                        <strong>Rows with issues</strong>
                        <ul class="bullet-list">
                            <?php foreach ($importReport['errors'] as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="panel" style="margin-bottom: 1rem;">
            <div class="toolbar" style="margin-bottom: 0.75rem;">
                <div>
                    <h2>Sync public municipal data</h2>
                    <p class="muted">
                        Pull Edmonton open-data datasets into the local inventory. You can bring in a sample size or run the full public dataset.
                    </p>
                </div>
            </div>

            <form method="post" action="<?= esc(site_url('assets/open-data-sync')) ?>" class="filter-grid">
                <?= csrf_field() ?>
                <div class="search-field">
                    <label for="source_key">Source</label>
                    <select id="source_key" name="source_key" required>
                        <option value="">Choose a source</option>
                        <?php foreach ($openDataSources as $sourceKey => $source): ?>
                            <option value="<?= esc($sourceKey) ?>">
                                <?= esc((string) $source['label']) ?> (<?= esc((string) $source['dataset_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <label for="limit">Sample limit</label>
                    <input id="limit" name="limit" type="number" min="1" value="100">
                </div>

                <div class="search-field">
                    <label for="sync_all">Import mode</label>
                    <select id="sync_all" name="sync_all">
                        <option value="0">Sample only</option>
                        <option value="1">Full dataset</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="button" type="submit">Sync public data</button>
                </div>
            </form>

            <?php if (is_array($syncReport)): ?>
                <div class="page-note">
                    <?= esc((string) ($syncReport['source_label'] ?? 'Public data source')) ?>:
                    <?= esc((string) ($syncReport['imported_count'] ?? 0)) ?> imported,
                    <?= esc((string) ($syncReport['updated_count'] ?? 0)) ?> updated,
                    <?= esc((string) ($syncReport['restored_count'] ?? 0)) ?> restored,
                    <?= esc((string) ($syncReport['unchanged_count'] ?? 0)) ?> unchanged,
                    <?= esc((string) ($syncReport['fetched_count'] ?? 0)) ?> fetched.
                </div>

                <?php if (($syncReport['errors'] ?? []) !== []): ?>
                    <div class="empty-state" style="margin-top: 0.75rem;">
                        <strong>Skipped public records</strong>
                        <ul class="bullet-list">
                            <?php foreach ($syncReport['errors'] as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($assets === []): ?>
        <div class="empty-state">
            No assets matched the current search.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Asset</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Next inspection</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td>
                            <strong><?= esc((string) $asset['asset_code']) ?></strong><br>
                            <?= esc((string) $asset['name']) ?><br>
                            <span class="muted"><?= esc((string) $asset['location_text']) ?></span>
                        </td>
                        <td><?= esc((string) $asset['category_name']) ?></td>
                        <td>
                            <?= esc((string) $asset['department_name']) ?><br>
                            <span class="muted"><?= esc((string) $asset['department_code']) ?></span>
                        </td>
                        <td>
                            <?= $asset['source_dataset'] ? esc((string) $asset['source_dataset']) : 'Manual' ?><br>
                            <span class="muted"><?= esc((string) ($asset['source_geometry_type'] ?? 'Point')) ?></span>
                        </td>
                        <td>
                            <span class="<?= esc($statusClass((string) $asset['status'])) ?>">
                                <?= esc((string) $asset['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $asset['next_inspection_due_at'] ? esc(date('M j, Y', strtotime((string) $asset['next_inspection_due_at']))) : 'Not scheduled' ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="text-link" href="<?= esc(site_url('assets/' . $asset['id'])) ?>">View</a>
                                <?php if ($canEdit): ?>
                                    <a class="text-link" href="<?= esc(site_url('assets/' . $asset['id'] . '/edit')) ?>">Edit</a>
                                <?php endif; ?>
                            </div>
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
