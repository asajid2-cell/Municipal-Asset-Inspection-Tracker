<?php
/**
 * @var array<string, mixed> $asset
 * @var array<string, string> $errors
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $inspectors
 * @var list<string> $requestPriorities
 * @var list<string> $statuses
 */

$fieldValue = static function (string $field, string $default = ''): string {
    return (string) old($field, $default);
};

$fieldClass = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? 'field has-error' : 'field';
};
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="form-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow"><?= esc((string) $asset['asset_code']) ?></span>
            <h1>Log Inspection</h1>
            <p class="muted">
                The next due date will be calculated automatically using the <?= esc((string) $asset['inspection_interval_days']) ?> day cadence for <?= esc((string) $asset['category_name']) ?>.
            </p>
        </div>
        <div class="inline-actions">
            <a class="button button-secondary" href="<?= esc(site_url('assets/' . $asset['id'])) ?>">Back to asset</a>
        </div>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <span>Current status</span>
            <strong><?= esc((string) $asset['status']) ?></strong>
        </div>
        <div class="detail-item">
            <span>Last inspection</span>
            <strong><?= $asset['last_inspected_at'] ? esc(date('M j, Y g:i A', strtotime((string) $asset['last_inspected_at']))) : 'No inspection recorded' ?></strong>
        </div>
        <div class="detail-item">
            <span>Current next due</span>
            <strong><?= $asset['next_inspection_due_at'] ? esc(date('M j, Y g:i A', strtotime((string) $asset['next_inspection_due_at']))) : 'Not scheduled' ?></strong>
        </div>
    </div>

    <form method="post" action="<?= esc(site_url('assets/' . $asset['id'] . '/inspections')) ?>" enctype="multipart/form-data" style="margin-top: 1rem;">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="<?= esc($fieldClass('inspector_id')) ?>">
                <label for="inspector_id">Inspector</label>
                <select id="inspector_id" name="inspector_id" required>
                    <option value="">Select a staff member</option>
                    <?php foreach ($inspectors as $inspector): ?>
                        <option value="<?= esc((string) $inspector['id']) ?>" <?= old('inspector_id') === (string) $inspector['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $inspector['full_name']) ?> (<?= esc(ucwords(str_replace('_', ' ', (string) $inspector['role']))) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['inspector_id'])): ?>
                    <div class="field-error"><?= esc($errors['inspector_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('inspected_at')) ?>">
                <label for="inspected_at">Inspected at</label>
                <input
                    id="inspected_at"
                    name="inspected_at"
                    type="datetime-local"
                    value="<?= esc($fieldValue('inspected_at')) ?>"
                    required
                >
                <?php if (isset($errors['inspected_at'])): ?>
                    <div class="field-error"><?= esc($errors['inspected_at']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('condition_rating')) ?>">
                <label for="condition_rating">Condition rating</label>
                <select id="condition_rating" name="condition_rating" required>
                    <option value="">Select a rating</option>
                    <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                        <option value="<?= esc((string) $rating) ?>" <?= old('condition_rating') === (string) $rating ? 'selected' : '' ?>>
                            <?= esc((string) $rating) ?> / 5
                        </option>
                    <?php endfor; ?>
                </select>
                <?php if (isset($errors['condition_rating'])): ?>
                    <div class="field-error"><?= esc($errors['condition_rating']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('result_status')) ?>">
                <label for="result_status">Inspection outcome</label>
                <select id="result_status" name="result_status" required>
                    <option value="">Select an outcome</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status) ?>" <?= old('result_status', (string) $asset['status']) === $status ? 'selected' : '' ?>>
                            <?= esc($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['result_status'])): ?>
                    <div class="field-error"><?= esc($errors['result_status']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('notes')) ?> full-width">
                <label for="notes">Inspection notes</label>
                <textarea id="notes" name="notes"><?= esc($fieldValue('notes')) ?></textarea>
                <?php if (isset($errors['notes'])): ?>
                    <div class="field-error"><?= esc($errors['notes']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('attachments')) ?> full-width">
                <label for="attachments">Attachments</label>
                <input id="attachments" name="attachments[]" type="file" accept=".jpg,.jpeg,.png,.pdf" multiple>
                <span class="muted">Optional photos or PDFs, up to 5 MB each.</span>
                <?php if (isset($errors['attachments'])): ?>
                    <div class="field-error"><?= esc($errors['attachments']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions-row">
            <button class="button" type="submit">Save inspection</button>
            <a class="button button-secondary" href="<?= esc(site_url('assets/' . $asset['id'])) ?>">Cancel</a>
        </div>

        <div class="panel" style="margin-top: 1rem;">
            <h2>Optional maintenance follow-up</h2>
            <p class="muted">
                If this inspection finds a service issue, you can open a linked maintenance request in the same step.
            </p>

            <div class="form-grid">
                <div class="<?= esc($fieldClass('create_request')) ?> full-width">
                    <label for="create_request" style="display: flex; flex-direction: row; align-items: center; gap: 0.75rem;">
                        <input
                            id="create_request"
                            name="create_request"
                            type="checkbox"
                            value="1"
                            <?= old('create_request') === '1' ? 'checked' : '' ?>
                            style="width: auto;"
                        >
                        Create a linked maintenance request
                    </label>
                    <?php if (isset($errors['create_request'])): ?>
                        <div class="field-error"><?= esc($errors['create_request']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="<?= esc($fieldClass('request_title')) ?>">
                    <label for="request_title">Request title</label>
                    <input
                        id="request_title"
                        name="request_title"
                        type="text"
                        value="<?= esc($fieldValue('request_title')) ?>"
                        placeholder="Example: Replace damaged bench supports"
                    >
                    <?php if (isset($errors['request_title'])): ?>
                        <div class="field-error"><?= esc($errors['request_title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="<?= esc($fieldClass('request_priority')) ?>">
                    <label for="request_priority">Priority</label>
                    <select id="request_priority" name="request_priority">
                        <option value="">Select priority</option>
                        <?php foreach ($requestPriorities as $priority): ?>
                            <option value="<?= esc($priority) ?>" <?= old('request_priority', 'High') === $priority ? 'selected' : '' ?>>
                                <?= esc($priority) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['request_priority'])): ?>
                        <div class="field-error"><?= esc($errors['request_priority']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="<?= esc($fieldClass('request_assigned_department_id')) ?>">
                    <label for="request_assigned_department_id">Assigned department</label>
                    <select id="request_assigned_department_id" name="request_assigned_department_id">
                        <option value="">Select a department</option>
                        <?php foreach ($departments as $department): ?>
                            <option
                                value="<?= esc((string) $department['id']) ?>"
                                <?= old('request_assigned_department_id', (string) $asset['department_id']) === (string) $department['id'] ? 'selected' : '' ?>
                            >
                                <?= esc((string) $department['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['request_assigned_department_id'])): ?>
                        <div class="field-error"><?= esc($errors['request_assigned_department_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="<?= esc($fieldClass('request_due_at')) ?>">
                    <label for="request_due_at">Target due date</label>
                    <input
                        id="request_due_at"
                        name="request_due_at"
                        type="datetime-local"
                        value="<?= esc($fieldValue('request_due_at')) ?>"
                    >
                    <?php if (isset($errors['request_due_at'])): ?>
                        <div class="field-error"><?= esc($errors['request_due_at']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="<?= esc($fieldClass('request_description')) ?> full-width">
                    <label for="request_description">Maintenance details</label>
                    <textarea id="request_description" name="request_description"><?= esc($fieldValue('request_description')) ?></textarea>
                    <?php if (isset($errors['request_description'])): ?>
                        <div class="field-error"><?= esc($errors['request_description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
