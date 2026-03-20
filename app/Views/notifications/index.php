<?php
/**
 * @var array<int, array<string, mixed>> $deliveries
 * @var array<string, string> $filters
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 * @var int $resultTotal
 */

$canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true);
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Development outbox</span>
            <h1>Notification Deliveries</h1>
            <p class="page-note">
                Email notifications are captured here in development mode so the workflow is reviewable without a real mail server.
            </p>
        </div>
        <?php if ($canEdit): ?>
            <form method="post" action="<?= esc(site_url('notifications/overdue-reminders')) ?>">
                <?= csrf_field() ?>
                <button class="button" type="submit">Capture overdue reminders</button>
            </form>
        <?php endif; ?>
    </div>

    <form class="search-form" method="get" action="<?= esc(site_url('notifications')) ?>">
        <div class="filter-grid" style="width: 100%;">
            <div class="search-field">
                <label for="q">Search</label>
                <input id="q" name="q" type="search" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Recipient, subject, or body">
            </div>
            <div class="search-field">
                <label for="context_type">Context type</label>
                <input id="context_type" name="context_type" type="text" value="<?= esc($filters['context_type'] ?? '') ?>" placeholder="inspection, asset">
            </div>
        </div>

        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-secondary" href="<?= esc(site_url('notifications')) ?>">Reset</a>
            <span class="muted"><?= esc((string) $resultTotal) ?> notification<?= $resultTotal === 1 ? '' : 's' ?></span>
        </div>
    </form>

    <?php if ($deliveries === []): ?>
        <div class="empty-state" style="margin-top: 1rem;">
            No notifications have been captured yet.
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                <tr>
                    <th>When</th>
                    <th>Recipient</th>
                    <th>Context</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Preview</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($deliveries as $delivery): ?>
                    <tr>
                        <td><?= esc(date('M j, Y g:i A', strtotime((string) $delivery['created_at']))) ?></td>
                        <td>
                            <strong><?= esc((string) $delivery['recipient_email']) ?></strong>
                            <?php if ($delivery['recipient_name']): ?>
                                <br><span class="muted"><?= esc((string) $delivery['recipient_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= esc((string) $delivery['context_type']) ?>
                            <?php if ($delivery['context_id']): ?>
                                <br><span class="muted">#<?= esc((string) $delivery['context_id']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) $delivery['subject']) ?></td>
                        <td><?= esc((string) $delivery['status']) ?></td>
                        <td><code><?= esc((string) $delivery['body_text']) ?></code></td>
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
