<?php
/**
 * @var array<string, mixed> $asset
 * @var array<string, string> $errors
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $categories
 * @var list<string> $statuses
 */

$fieldValue = static function (string $field, string $default = '') use ($asset): string {
    return (string) old($field, (string) ($asset[$field] ?? $default));
};

$selectedValue = static function (string $field) use ($asset): string {
    return (string) old($field, (string) ($asset[$field] ?? ''));
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
            <h1><?= esc($pageTitle) ?></h1>
            <p class="muted">
                Capture the operational details staff need for asset inventory management.
            </p>
        </div>
        <a class="button button-secondary" href="<?= esc(site_url('assets')) ?>">Back to inventory</a>
    </div>

    <form method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="<?= esc($fieldClass('asset_code')) ?>">
                <label for="asset_code">Asset code</label>
                <input id="asset_code" name="asset_code" value="<?= esc($fieldValue('asset_code')) ?>" maxlength="60" required>
                <?php if (isset($errors['asset_code'])): ?>
                    <div class="field-error"><?= esc($errors['asset_code']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('name')) ?>">
                <label for="name">Asset name</label>
                <input id="name" name="name" value="<?= esc($fieldValue('name')) ?>" maxlength="190" required>
                <?php if (isset($errors['name'])): ?>
                    <div class="field-error"><?= esc($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('department_id')) ?>">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" required>
                    <option value="">Select a department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= esc((string) $department['id']) ?>" <?= $selectedValue('department_id') === (string) $department['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $department['name']) ?> (<?= esc((string) $department['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['department_id'])): ?>
                    <div class="field-error"><?= esc($errors['department_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('category_id')) ?>">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= esc((string) $category['id']) ?>" <?= $selectedValue('category_id') === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <div class="field-error"><?= esc($errors['category_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('status')) ?>">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="">Select a status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status) ?>" <?= $selectedValue('status') === $status ? 'selected' : '' ?>>
                            <?= esc($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?>
                    <div class="field-error"><?= esc($errors['status']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('installed_on')) ?>">
                <label for="installed_on">Installed on</label>
                <input id="installed_on" name="installed_on" type="date" value="<?= esc($fieldValue('installed_on')) ?>">
                <?php if (isset($errors['installed_on'])): ?>
                    <div class="field-error"><?= esc($errors['installed_on']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('latitude')) ?>">
                <label for="latitude">Latitude</label>
                <input id="latitude" name="latitude" value="<?= esc($fieldValue('latitude')) ?>" placeholder="53.5452000">
                <?php if (isset($errors['latitude'])): ?>
                    <div class="field-error"><?= esc($errors['latitude']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('longitude')) ?>">
                <label for="longitude">Longitude</label>
                <input id="longitude" name="longitude" value="<?= esc($fieldValue('longitude')) ?>" placeholder="-113.4921000">
                <?php if (isset($errors['longitude'])): ?>
                    <div class="field-error"><?= esc($errors['longitude']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('condition_score')) ?>">
                <label for="condition_score">Condition score</label>
                <input id="condition_score" name="condition_score" type="number" min="0" max="100" value="<?= esc($fieldValue('condition_score')) ?>" placeholder="0-100">
                <?php if (isset($errors['condition_score'])): ?>
                    <div class="field-error"><?= esc($errors['condition_score']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('criticality_score')) ?>">
                <label for="criticality_score">Criticality score</label>
                <input id="criticality_score" name="criticality_score" type="number" min="0" max="100" value="<?= esc($fieldValue('criticality_score')) ?>" placeholder="0-100">
                <?php if (isset($errors['criticality_score'])): ?>
                    <div class="field-error"><?= esc($errors['criticality_score']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('replacement_cost')) ?>">
                <label for="replacement_cost">Replacement cost</label>
                <input id="replacement_cost" name="replacement_cost" type="number" min="0" step="0.01" value="<?= esc($fieldValue('replacement_cost')) ?>">
                <?php if (isset($errors['replacement_cost'])): ?>
                    <div class="field-error"><?= esc($errors['replacement_cost']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('service_level')) ?>">
                <label for="service_level">Service level</label>
                <input id="service_level" name="service_level" value="<?= esc($fieldValue('service_level')) ?>" maxlength="60" placeholder="Road safety, Fire protection, Amenity">
                <?php if (isset($errors['service_level'])): ?>
                    <div class="field-error"><?= esc($errors['service_level']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('location_text')) ?> full-width">
                <label for="location_text">Location</label>
                <input id="location_text" name="location_text" value="<?= esc($fieldValue('location_text')) ?>" maxlength="255" required>
                <?php if (isset($errors['location_text'])): ?>
                    <div class="field-error"><?= esc($errors['location_text']) ?></div>
                <?php endif; ?>
            </div>

            <div class="<?= esc($fieldClass('notes')) ?> full-width">
                <label for="notes">Operational notes</label>
                <textarea id="notes" name="notes"><?= esc($fieldValue('notes')) ?></textarea>
                <?php if (isset($errors['notes'])): ?>
                    <div class="field-error"><?= esc($errors['notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions-row">
            <button class="button" type="submit"><?= esc($submitLabel) ?></button>
            <a class="button button-secondary" href="<?= esc(site_url('assets')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
