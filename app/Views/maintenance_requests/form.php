<?php
/**
 * @var array<string, mixed> $asset
 * @var array<string, string> $errors
 * @var string $cancelUrl
 * @var string $formAction
 * @var array<int, array<string, mixed>> $departments
 * @var list<string> $priorities
 * @var array<string, mixed> $requestData
 * @var array<int, array<string, mixed>> $staff
 * @var list<string> $statuses
 * @var string $submitLabel
 */

$fieldValue = static function (string $field, string $default = '') use ($requestData): string {
    $existingValue = $requestData[$field] ?? $default;

    return (string) old($field, is_string($existingValue) || is_numeric($existingValue) ? (string) $existingValue : $default);
};

$fieldClass = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? 'field has-error' : 'field';
};

$dateTimeFieldValue = static function (string $field) use ($requestData): string {
    $oldValue = old($field);

    if ($oldValue !== null && $oldValue !== '') {
        return (string) $oldValue;
    }

    $existingValue = $requestData[$field] ?? null;

    if (! is_string($existingValue) || $existingValue === '') {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($existingValue));
};
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="form-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow"><?= esc((string) $asset['asset_code']) ?></span>
            <h1><?= esc($pageTitle ?? 'Maintenance Request') ?></h1>
            <p class="muted">
                Track work orders and follow-up activity for <?= esc((string) $asset['name']) ?>.
            </p>
        </div>
        <div class="inline-actions">
            <a class="button button-secondary" href="<?= esc($cancelUrl) ?>">Back</a>
        </div>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <span>Asset</span>
            <strong><?= esc((string) $asset['name']) ?></strong>
        </div>
        <div class="detail-item">
            <span>Current asset status</span>
            <strong><?= esc((string) $asset['status']) ?></strong>
        </div>
        <div class="detail-item">
            <span>Location</span>
            <strong><?= esc((string) $asset['location_text']) ?></strong>
        </div>
        <?php if (! empty($requestData['inspection_id'])): ?>
            <div class="detail-item">
                <span>Linked inspection</span>
                <strong>#<?= esc((string) $requestData['inspection_id']) ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= esc($formAction) ?>" style="margin-top: 1rem;">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="<?= esc($fieldClass('opened_by')) ?>">
                <label for="opened_by">Requested by</label>
                <select id="opened_by" name="opened_by" required>
                    <option value="">Select a staff member</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= esc((string) $member['id']) ?>" <?= $fieldValue('opened_by') === (string) $member['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $member['full_name']) ?> (<?= esc(ucwords(str_replace('_', ' ', (string) $member['role']))) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['opened_by'])): ?>
                    <div class="field-error"><?= esc($errors['opened_by']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('assigned_department_id')) ?>">
                <label for="assigned_department_id">Assigned department</label>
                <select id="assigned_department_id" name="assigned_department_id" required>
                    <option value="">Select a department</option>
                    <?php foreach ($departments as $department): ?>
                        <?php $selectedDepartment = old('assigned_department_id', (string) ($requestData['assigned_department_id'] ?? $asset['department_id'] ?? '')); ?>
                        <option value="<?= esc((string) $department['id']) ?>" <?= $selectedDepartment === (string) $department['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['assigned_department_id'])): ?>
                    <div class="field-error"><?= esc($errors['assigned_department_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('assigned_user_id')) ?>">
                <label for="assigned_user_id">Assigned staff</label>
                <select id="assigned_user_id" name="assigned_user_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= esc((string) $member['id']) ?>" <?= $fieldValue('assigned_user_id') === (string) $member['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $member['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['assigned_user_id'])): ?>
                    <div class="field-error"><?= esc($errors['assigned_user_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('priority')) ?>">
                <label for="priority">Priority</label>
                <select id="priority" name="priority" required>
                    <option value="">Select priority</option>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?= esc($priority) ?>" <?= $fieldValue('priority', 'Medium') === $priority ? 'selected' : '' ?>>
                            <?= esc($priority) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['priority'])): ?>
                    <div class="field-error"><?= esc($errors['priority']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('work_order_code')) ?>">
                <label for="work_order_code">Work order code</label>
                <input id="work_order_code" name="work_order_code" type="text" value="<?= esc($fieldValue('work_order_code')) ?>">
                <?php if (isset($errors['work_order_code'])): ?>
                    <div class="field-error"><?= esc($errors['work_order_code']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('status')) ?>">
                <label for="status">Request status</label>
                <select id="status" name="status" required>
                    <option value="">Select status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status) ?>" <?= $fieldValue('status', 'Open') === $status ? 'selected' : '' ?>>
                            <?= esc($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?>
                    <div class="field-error"><?= esc($errors['status']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('title')) ?> full-width">
                <label for="title">Request title</label>
                <input id="title" name="title" type="text" value="<?= esc($fieldValue('title')) ?>" required>
                <?php if (isset($errors['title'])): ?>
                    <div class="field-error"><?= esc($errors['title']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('due_at')) ?>">
                <label for="due_at">Target due date</label>
                <input id="due_at" name="due_at" type="datetime-local" value="<?= esc($dateTimeFieldValue('due_at')) ?>">
                <?php if (isset($errors['due_at'])): ?>
                    <div class="field-error"><?= esc($errors['due_at']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('sla_target_at')) ?>">
                <label for="sla_target_at">SLA target</label>
                <input id="sla_target_at" name="sla_target_at" type="datetime-local" value="<?= esc($dateTimeFieldValue('sla_target_at')) ?>">
                <?php if (isset($errors['sla_target_at'])): ?>
                    <div class="field-error"><?= esc($errors['sla_target_at']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('started_at')) ?>">
                <label for="started_at">Started at</label>
                <input id="started_at" name="started_at" type="datetime-local" value="<?= esc($dateTimeFieldValue('started_at')) ?>">
                <?php if (isset($errors['started_at'])): ?>
                    <div class="field-error"><?= esc($errors['started_at']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('completed_at')) ?>">
                <label for="completed_at">Completed at</label>
                <input id="completed_at" name="completed_at" type="datetime-local" value="<?= esc($dateTimeFieldValue('completed_at')) ?>">
                <?php if (isset($errors['completed_at'])): ?>
                    <div class="field-error"><?= esc($errors['completed_at']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('labor_hours')) ?>">
                <label for="labor_hours">Labour hours</label>
                <input id="labor_hours" name="labor_hours" type="number" min="0" step="0.01" value="<?= esc($fieldValue('labor_hours')) ?>">
                <?php if (isset($errors['labor_hours'])): ?>
                    <div class="field-error"><?= esc($errors['labor_hours']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('estimated_cost')) ?>">
                <label for="estimated_cost">Estimated cost</label>
                <input id="estimated_cost" name="estimated_cost" type="number" min="0" step="0.01" value="<?= esc($fieldValue('estimated_cost')) ?>">
                <?php if (isset($errors['estimated_cost'])): ?>
                    <div class="field-error"><?= esc($errors['estimated_cost']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('actual_cost')) ?>">
                <label for="actual_cost">Actual cost</label>
                <input id="actual_cost" name="actual_cost" type="number" min="0" step="0.01" value="<?= esc($fieldValue('actual_cost')) ?>">
                <?php if (isset($errors['actual_cost'])): ?>
                    <div class="field-error"><?= esc($errors['actual_cost']) ?></div>
                <?php endif; ?>
            </div>

            <?php if (! empty($requestData['resolved_at'])): ?>
                <div class="field">
                    <label>Resolved at</label>
                    <input type="text" value="<?= esc(date('M j, Y g:i A', strtotime((string) $requestData['resolved_at']))) ?>" disabled>
                </div>
            <?php endif; ?>

            <div class="<?= esc($fieldClass('description')) ?> full-width">
                <label for="description">Work description</label>
                <textarea id="description" name="description"><?= esc($fieldValue('description')) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <div class="field-error"><?= esc($errors['description']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('resolution_notes')) ?> full-width">
                <label for="resolution_notes">Resolution notes</label>
                <textarea id="resolution_notes" name="resolution_notes"><?= esc($fieldValue('resolution_notes')) ?></textarea>
                <?php if (isset($errors['resolution_notes'])): ?>
                    <div class="field-error"><?= esc($errors['resolution_notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions-row">
            <button class="button" type="submit"><?= esc($submitLabel) ?></button>
            <a class="button button-secondary" href="<?= esc($cancelUrl) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
