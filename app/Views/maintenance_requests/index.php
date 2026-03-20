<?php
/**
 * @var array<int, array<string, mixed>> $departments
 * @var array<string, int|string|null> $filters
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 * @var list<string> $priorities
 * @var int $resultTotal
 * @var array<int, array<string, mixed>> $requests
 * @var list<string> $statuses
 */

$statusClass = static function (string $status): string {
    return match ($status) {
        'Open' => 'pill pill-inspection',
        'In Progress' => 'pill pill-neutral',
        'Resolved' => 'pill pill-active',
        'Closed' => 'pill pill-out',
        default => 'pill pill-neutral',
    };
};
$canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true);
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Operations queue</span>
            <h1>Maintenance Requests</h1>
            <p class="page-note">
                Search and triage follow-up work opened from inspections or manual reports.
            </p>
        </div>
    </div>

    <form class="search-form" method="get" action="<?= esc(site_url('maintenance-requests')) ?>">
        <input type="hidden" name="active_only" value="0">
        <div class="filter-grid" style="width: 100%;">
            <div class="search-field">
                <label for="q">Search</label>
                <input id="q" name="q" type="search" value="<?= esc((string) ($filters['q'] ?? '')) ?>" placeholder="Title, asset code, or asset name">
            </div>

            <div class="search-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                            <?= esc($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="search-field">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="">All priorities</option>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?= esc($priority) ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>>
                            <?= esc($priority) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="search-field">
                <label for="assigned_department_id">Assigned department</label>
                <select id="assigned_department_id" name="assigned_department_id">
                    <option value="">All departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= esc((string) $department['id']) ?>" <?= (string) ($filters['assigned_department_id'] ?? '') === (string) $department['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="search-field">
                <label for="active_only">Active queue only</label>
                <input id="active_only" name="active_only" type="checkbox" value="1" <?= ($filters['active_only'] ?? '') === '1' ? 'checked' : '' ?> style="width: auto; align-self: flex-start; margin-top: 0.8rem;">
            </div>
        </div>

        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-secondary" href="<?= esc(site_url('maintenance-requests')) ?>">Reset</a>
            <span class="muted"><?= esc((string) $resultTotal) ?> request<?= $resultTotal === 1 ? '' : 's' ?></span>
        </div>
    </form>

    <?php if ($requests === []): ?>
        <div class="empty-state" style="margin-top: 1rem;">
            No maintenance requests match the current filters.
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                <tr>
                    <th>Request</th>
                    <th>Asset</th>
                    <th>Assigned department</th>
                    <th>Assigned staff</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Opened by</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <strong><?= esc((string) $request['title']) ?></strong><br>
                            <span class="muted"><?= $request['description'] ? esc((string) $request['description']) : 'No description added.' ?></span>
                        </td>
                        <td>
                            <a class="text-link" href="<?= esc(site_url('assets/' . $request['asset_id'])) ?>">
                                <?= esc((string) $request['asset_code']) ?>
                            </a><br>
                            <span class="muted"><?= esc((string) $request['asset_name']) ?></span>
                        </td>
                        <td><?= esc((string) $request['assigned_department_name']) ?></td>
                        <td>
                            <?= $request['assigned_user_name'] ? esc((string) $request['assigned_user_name']) : 'Unassigned' ?><br>
                            <span class="muted"><?= esc((string) ($request['work_order_code'] ?? '')) ?></span>
                        </td>
                        <td><?= esc((string) $request['priority']) ?></td>
                        <td>
                            <span class="<?= esc($statusClass((string) $request['status'])) ?>">
                                <?= esc((string) $request['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($request['resolved_at']): ?>
                                Resolved <?= esc(date('M j, Y', strtotime((string) $request['resolved_at']))) ?>
                            <?php elseif ($request['sla_target_at']): ?>
                                SLA <?= esc(date('M j, Y g:i A', strtotime((string) $request['sla_target_at']))) ?><br>
                                <span class="muted">Due <?= $request['due_at'] ? esc(date('M j, Y g:i A', strtotime((string) $request['due_at']))) : 'Not set' ?></span>
                            <?php elseif ($request['due_at']): ?>
                                <?= esc(date('M j, Y g:i A', strtotime((string) $request['due_at']))) ?>
                            <?php else: ?>
                                <span class="muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) $request['opened_by_name']) ?></td>
                        <td>
                            <?php if ($canEdit): ?>
                                <a class="text-link" href="<?= esc(site_url('maintenance-requests/' . $request['id'] . '/edit')) ?>">Manage</a>
                            <?php else: ?>
                                <span class="muted">View only</span>
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
