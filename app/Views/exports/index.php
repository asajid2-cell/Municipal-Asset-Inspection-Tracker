<?php
/**
 * @var array<int, array<string, mixed>> $jobs
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Exports</span>
            <h1>Export Jobs</h1>
            <p class="page-note">Large inventory exports are generated as durable files instead of trying to stream the whole city inventory directly from one request.</p>
        </div>
    </div>

    <?php if ($jobs === []): ?>
        <div class="empty-state">No export jobs have been generated yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Format</th>
                    <th>Rows</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= esc((string) $job['name']) ?></td>
                        <td><?= esc((string) $job['status']) ?></td>
                        <td><?= esc((string) $job['format']) ?></td>
                        <td><?= esc((string) $job['row_count']) ?></td>
                        <td><?= esc((string) $job['created_at']) ?></td>
                        <td>
                            <?php if ((string) $job['status'] === 'Completed' && (string) ($job['file_path'] ?? '') !== ''): ?>
                                <a class="text-link" href="<?= esc(site_url('exports/' . $job['id'] . '/download')) ?>">Download</a>
                            <?php else: ?>
                                <span class="muted">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
