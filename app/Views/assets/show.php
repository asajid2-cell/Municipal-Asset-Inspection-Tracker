<?php
$statusClass = match ((string) $asset['status']) {
    'Needs Inspection' => 'pill pill-inspection',
    'Needs Repair' => 'pill pill-repair',
    'Out of Service' => 'pill pill-out',
    default => 'pill pill-active',
};

$requestStatusClass = static function (string $status): string {
    return match ($status) {
        'Open' => 'pill pill-inspection',
        'In Progress' => 'pill pill-neutral',
        'Resolved' => 'pill pill-active',
        'Closed' => 'pill pill-out',
        default => 'pill pill-neutral',
    };
};

$openRequests = array_values(array_filter(
    $maintenanceHistory,
    static fn (array $request): bool => in_array((string) $request['status'], ['Open', 'In Progress'], true)
));
$completedRequests = array_values(array_filter(
    $maintenanceHistory,
    static fn (array $request): bool => in_array((string) $request['status'], ['Resolved', 'Closed'], true)
));
$canEdit = in_array((string) (session()->get('auth_user')['role'] ?? ''), ['admin', 'operations_coordinator', 'inspector', 'department_manager'], true);
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="detail-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow"><?= esc((string) $asset['asset_code']) ?></span>
            <h1><?= esc((string) $asset['name']) ?></h1>
            <p class="muted">
                <?= esc((string) $asset['category_name']) ?> managed by <?= esc((string) $asset['department_name']) ?>.
            </p>
        </div>
        <div class="inline-actions">
            <a class="button button-secondary" href="<?= esc(site_url('assets')) ?>">Back to inventory</a>
            <?php if ($canEdit): ?>
                <a class="button button-secondary" href="<?= esc(site_url('assets/' . $asset['id'] . '/inspections/new')) ?>">Log inspection</a>
                <a class="button" href="<?= esc(site_url('assets/' . $asset['id'] . '/edit')) ?>">Edit asset</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <span>Status</span>
            <strong class="<?= esc($statusClass) ?>"><?= esc((string) $asset['status']) ?></strong>
        </div>
        <div class="detail-item">
            <span>Location</span>
            <strong><?= esc((string) $asset['location_text']) ?></strong>
        </div>
        <div class="detail-item">
            <span>Installed on</span>
            <strong><?= $asset['installed_on'] ? esc(date('M j, Y', strtotime((string) $asset['installed_on']))) : 'Unknown' ?></strong>
        </div>
        <div class="detail-item">
            <span>Next inspection due</span>
            <strong><?= $asset['next_inspection_due_at'] ? esc(date('M j, Y', strtotime((string) $asset['next_inspection_due_at']))) : 'Not scheduled' ?></strong>
        </div>
        <div class="detail-item">
            <span>Last inspection</span>
            <strong><?= $asset['last_inspected_at'] ? esc(date('M j, Y g:i A', strtotime((string) $asset['last_inspected_at']))) : 'No inspection recorded' ?></strong>
        </div>
        <div class="detail-item">
            <span>Category cadence</span>
            <strong><?= esc((string) $asset['inspection_interval_days']) ?> day interval</strong>
        </div>
        <div class="detail-item">
            <span>Department contact</span>
            <strong><?= $asset['department_contact_email'] ? esc((string) $asset['department_contact_email']) : 'No contact email' ?></strong>
        </div>
        <div class="detail-item">
            <span>Coordinates</span>
            <strong>
                <?= ($asset['latitude'] && $asset['longitude']) ? esc((string) $asset['latitude'] . ', ' . (string) $asset['longitude']) : 'Not mapped yet' ?>
            </strong>
        </div>
        <div class="detail-item">
            <span>Source</span>
            <strong>
                <?php if ($asset['source_dataset']): ?>
                    <?= esc((string) $asset['source_system']) ?><br>
                    <span class="muted">Dataset <?= esc((string) $asset['source_dataset']) ?>, record <?= esc((string) $asset['source_record_id']) ?></span>
                <?php else: ?>
                    Added inside the app
                <?php endif; ?>
            </strong>
        </div>
        <div class="detail-item">
            <span>Condition / criticality</span>
            <strong><?= esc((string) ($asset['condition_score'] ?? 'n/a')) ?> / <?= esc((string) ($asset['criticality_score'] ?? 'n/a')) ?></strong>
        </div>
        <div class="detail-item">
            <span>Risk / lifecycle</span>
            <strong><?= esc((string) ($asset['risk_score'] ?? 'n/a')) ?>, <?= esc((string) ($asset['lifecycle_state'] ?? 'Operate')) ?></strong>
        </div>
        <div class="detail-item">
            <span>Replacement cost</span>
            <strong><?= $asset['replacement_cost'] ? '$' . esc(number_format((float) $asset['replacement_cost'], 2)) : 'Not set' ?></strong>
        </div>
        <div class="detail-item">
            <span>Service level</span>
            <strong><?= $asset['service_level'] ? esc((string) $asset['service_level']) : 'Not set' ?></strong>
        </div>
    </div>

    <?php if (isset($linearAsset) && is_array($linearAsset)): ?>
        <div class="panel" style="margin-top: 1rem;">
            <h2>Linear asset context</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span>Network</span>
                    <strong><?= esc((string) $linearAsset['network_type']) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Corridor</span>
                    <strong><?= esc((string) ($linearAsset['corridor_name'] ?? 'Not set')) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Measure range</span>
                    <strong><?= esc((string) ($linearAsset['measure_start'] ?? '0')) ?> to <?= esc((string) ($linearAsset['measure_end'] ?? '0')) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Segment length</span>
                    <strong><?= esc((string) ($linearAsset['segment_length_m'] ?? '0')) ?> m</strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel" style="margin-top: 1rem;">
        <h2>Notes</h2>
        <p><?= $asset['notes'] ? nl2br(esc((string) $asset['notes'])) : 'No operational notes recorded for this asset yet.' ?></p>
    </div>

    <?php if (isset($versionHistory) && $versionHistory !== []): ?>
        <div class="panel" style="margin-top: 1rem;">
            <h2>Version history</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Recorded</th>
                        <th>Version type</th>
                        <th>Reason</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($versionHistory as $version): ?>
                        <tr>
                            <td><?= esc(date('M j, Y g:i A', strtotime((string) $version['created_at']))) ?></td>
                            <td><?= esc((string) $version['version_type']) ?></td>
                            <td><?= $version['reason'] ? esc((string) $version['reason']) : 'No reason recorded' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel" style="margin-top: 1rem;">
        <div class="toolbar">
            <div>
                <h2>Inspection history</h2>
                <p class="muted">
                    Each inspection stores the inspector, result, notes, and the next due date computed from the category cadence.
                </p>
            </div>
            <?php if ($canEdit): ?>
                <a class="button" href="<?= esc(site_url('assets/' . $asset['id'] . '/inspections/new')) ?>">New inspection</a>
            <?php endif; ?>
        </div>

        <?php if ($inspectionHistory === []): ?>
            <div class="empty-state">
                No inspections have been recorded for this asset yet.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Inspected at</th>
                        <th>Inspector</th>
                        <th>Rating</th>
                        <th>Result</th>
                        <th>Next due</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inspectionHistory as $inspection): ?>
                        <?php
                        $historyStatusClass = match ((string) $inspection['result_status']) {
                            'Needs Inspection' => 'pill pill-inspection',
                            'Needs Repair' => 'pill pill-repair',
                            'Out of Service' => 'pill pill-out',
                            default => 'pill pill-active',
                        };
                        $attachments = $inspectionAttachments[(int) $inspection['id']] ?? [];
                        ?>
                        <tr>
                            <td><?= esc(date('M j, Y g:i A', strtotime((string) $inspection['inspected_at']))) ?></td>
                            <td>
                                <?= esc((string) $inspection['inspector_name']) ?><br>
                                <span class="muted"><?= esc(ucwords(str_replace('_', ' ', (string) $inspection['inspector_role']))) ?></span>
                            </td>
                            <td><?= esc((string) $inspection['condition_rating']) ?>/5</td>
                            <td>
                                <span class="<?= esc($historyStatusClass) ?>">
                                    <?= esc((string) $inspection['result_status']) ?>
                                </span>
                            </td>
                            <td><?= esc(date('M j, Y g:i A', strtotime((string) $inspection['next_due_at']))) ?></td>
                            <td>
                                <?= $inspection['notes'] ? nl2br(esc((string) $inspection['notes'])) : '<span class="muted">No notes</span>' ?>
                                <?php if ($attachments !== []): ?>
                                    <div style="margin-top: 0.75rem;">
                                        <strong>Attachments</strong><br>
                                        <?php foreach ($attachments as $attachment): ?>
                                            <a class="text-link" href="<?= esc(site_url('attachments/' . $attachment['id'] . '/download')) ?>">
                                                <?= esc((string) $attachment['original_name']) ?>
                                            </a><br>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel" style="margin-top: 1rem;">
        <div class="toolbar">
            <div>
                <h2>Maintenance requests</h2>
                <p class="muted">
                    Follow-up work linked to this asset stays visible here so inspectors and operations staff can track what is still open and what has already been resolved.
                </p>
            </div>
            <div class="inline-actions">
                <a class="button button-secondary" href="<?= esc(site_url('maintenance-requests')) ?>">Open queue</a>
                <?php if ($canEdit): ?>
                    <a class="button" href="<?= esc(site_url('assets/' . $asset['id'] . '/maintenance-requests/new')) ?>">New request</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($maintenanceHistory === []): ?>
            <div class="empty-state">
                No maintenance requests have been logged for this asset yet.
            </div>
        <?php else: ?>
            <div class="detail-grid" style="margin-bottom: 1rem;">
                <div class="detail-item">
                    <span>Open requests</span>
                    <strong><?= esc((string) count($openRequests)) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Resolved or closed</span>
                    <strong><?= esc((string) count($completedRequests)) ?></strong>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Request</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned department</th>
                        <th>Due / resolved</th>
                        <th>Context</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($maintenanceHistory as $request): ?>
                        <tr>
                            <td>
                                <strong><?= esc((string) $request['title']) ?></strong><br>
                                <span class="muted"><?= $request['description'] ? esc((string) $request['description']) : 'No request details added.' ?></span>
                            </td>
                            <td>
                                <span class="<?= esc($requestStatusClass((string) $request['status'])) ?>">
                                    <?= esc((string) $request['status']) ?>
                                </span>
                            </td>
                            <td><?= esc((string) $request['priority']) ?></td>
                            <td><?= esc((string) $request['assigned_department_name']) ?></td>
                            <td>
                                <?php if ($request['resolved_at']): ?>
                                    Resolved <?= esc(date('M j, Y g:i A', strtotime((string) $request['resolved_at']))) ?>
                                <?php elseif ($request['due_at']): ?>
                                    Due <?= esc(date('M j, Y g:i A', strtotime((string) $request['due_at']))) ?>
                                <?php else: ?>
                                    <span class="muted">No due date</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                Opened by <?= esc((string) $request['opened_by_name']) ?>
                                <?php if ($request['inspection_id']): ?>
                                    <br><span class="muted">Linked to inspection #<?= esc((string) $request['inspection_id']) ?></span>
                                <?php endif; ?>
                                <?php if ($request['resolution_notes']): ?>
                                    <br><span class="muted"><?= esc((string) $request['resolution_notes']) ?></span>
                                <?php endif; ?>
                            </td>
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
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
        <div class="danger-note">
            <h2>Archive asset</h2>
            <p class="muted">
                Archiving keeps historical data in the database through soft deletes while removing the asset from the active inventory list.
            </p>
            <form method="post" action="<?= esc(site_url('assets/' . $asset['id'] . '/archive')) ?>">
                <?= csrf_field() ?>
                <button class="button button-danger" type="submit" onclick="return confirm('Archive this asset from the active inventory?');">
                    Archive asset
                </button>
            </form>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
