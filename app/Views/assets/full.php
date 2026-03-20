<?php
/**
 * @var array<int, array<string, mixed>> $assets
 * @var array<string, int|string|null> $filters
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $categories
 * @var list<string> $statuses
 * @var array<string, string> $sortOptions
 * @var CodeIgniter\Pager\Pager $pager
 * @var int $resultTotal
 * @var int $currentPerPage
 * @var list<int> $perPageOptions
 * @var string $backToPagedUrl
 * @var string $mapViewUrl
 * @var array<string, array<string, int|string>> $openDataSources
 * @var array<string, string> $geometryFilters
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
            <h1>Full Asset Inventory</h1>
            <p class="muted">
                This is now a large-table view with pagination. It keeps the same broad filters, but avoids loading the entire city inventory into PHP at once.
            </p>
        </div>
        <div class="actions-row">
            <a class="button button-secondary" href="<?= esc($backToPagedUrl) ?>">Paged View</a>
            <a class="button button-secondary" href="<?= esc($mapViewUrl) ?>">Map View</a>
            <?php if ($canEdit): ?>
                <a class="button" href="<?= esc(site_url('assets/new')) ?>">Add Asset</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="filter-grid" method="get" action="<?= esc(site_url('assets/full')) ?>">
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
        <div class="search-field">
            <label for="per_page">Rows per page</label>
            <select id="per_page" name="per_page">
                <?php foreach ($perPageOptions as $option): ?>
                    <option value="<?= esc((string) $option) ?>" <?= $currentPerPage === $option ? 'selected' : '' ?>>
                        <?= esc((string) $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-secondary" href="<?= esc(site_url('assets/full')) ?>">Clear</a>
        </div>
    </form>

    <div class="panel panel-inline" style="margin-bottom: 1rem;">
        <strong><?= esc((string) $resultTotal) ?></strong> assets match the current filters.
        Showing <strong><?= esc((string) count($assets)) ?></strong> rows on this page.
    </div>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Mapped assets</span>
            <strong class="stat-value"><?= esc((string) ($summary['mapped_count'] ?? 0)) ?></strong>
            <span class="muted"><?= esc((string) ($summary['unmapped_count'] ?? 0)) ?> unmapped assets remain in the filtered result.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Needs inspection</span>
            <strong class="stat-value"><?= esc((string) (($summary['status_breakdown']['Needs Inspection'] ?? 0))) ?></strong>
            <span class="muted">Operational follow-up items in the filtered set.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Needs repair</span>
            <strong class="stat-value"><?= esc((string) (($summary['status_breakdown']['Needs Repair'] ?? 0))) ?></strong>
            <span class="muted">Repair candidates currently returned by the filters.</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Geometry mix</span>
            <strong class="stat-value"><?= esc((string) (($summary['point_count'] ?? 0))) ?></strong>
            <span class="muted">
                Points, <?= esc((string) (($summary['line_count'] ?? 0))) ?> lines, <?= esc((string) (($summary['polygon_count'] ?? 0))) ?> areas
            </span>
        </article>
    </section>

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

    <?php if ($assets === []): ?>
        <div class="empty-state">No assets matched the current search.</div>
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
                    <th>Coordinates</th>
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
                        <td><?= esc((string) $asset['department_name']) ?></td>
                        <td>
                            <?= $asset['source_dataset'] ? esc((string) $asset['source_dataset']) : 'Manual' ?><br>
                            <span class="muted"><?= esc((string) ($asset['source_geometry_type'] ?? 'Point')) ?></span>
                        </td>
                        <td><span class="<?= esc($statusClass((string) $asset['status'])) ?>"><?= esc((string) $asset['status']) ?></span></td>
                        <td>
                            <?= ($asset['latitude'] && $asset['longitude']) ? esc((string) $asset['latitude'] . ', ' . (string) $asset['longitude']) : 'Unmapped' ?>
                        </td>
                        <td><?= $asset['next_inspection_due_at'] ? esc(date('M j, Y', strtotime((string) $asset['next_inspection_due_at']))) : 'Not scheduled' ?></td>
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
            <?= $pager->links('full') ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
