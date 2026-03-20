<?php
/**
 * @var array<int, array<string, mixed>> $packets
 * @var array<int, array<string, mixed>> $conflicts
 * @var array<int, array<string, mixed>> $staff
 */
?>
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="table-card">
    <div class="toolbar">
        <div>
            <span class="eyebrow">Offline field work</span>
            <h1>Mobile Field Ops</h1>
            <p class="page-note">Prepare offline inspection packets and record sync conflicts from field uploads.</p>
        </div>
    </div>

    <section class="content-grid">
        <article class="panel">
            <h2>Prepare packet</h2>
            <form method="post" action="<?= esc(site_url('mobile-ops/packets')) ?>" class="form-grid">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="packet_name">Packet name</label>
                    <input id="packet_name" name="packet_name" type="text" value="<?= esc((string) old('packet_name', 'Overdue inspection packet')) ?>">
                </div>
                <div class="field">
                    <label for="assigned_user_id">Assigned user</label>
                    <select id="assigned_user_id" name="assigned_user_id">
                        <option value="">Select a field worker</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?= esc((string) $member['id']) ?>"><?= esc((string) $member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="button" type="submit">Create offline packet</button>
                </div>
            </form>
        </article>
        <article class="panel">
            <h2>Record conflict</h2>
            <form method="post" action="<?= esc(site_url('mobile-ops/conflicts')) ?>" class="form-grid">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="packet_id">Packet ID</label>
                    <input id="packet_id" name="packet_id" type="number" min="1">
                </div>
                <div class="field">
                    <label for="asset_id">Asset ID</label>
                    <input id="asset_id" name="asset_id" type="number" min="1">
                </div>
                <div class="field">
                    <label for="conflict_type">Conflict type</label>
                    <input id="conflict_type" name="conflict_type" type="text" value="inspection_status_conflict">
                </div>
                <div class="field full-width">
                    <label for="local_payload_json">Local payload JSON</label>
                    <textarea id="local_payload_json" name="local_payload_json">{"result_status":"Needs Repair","notes":"Captured offline in field."}</textarea>
                </div>
                <div class="field full-width">
                    <label for="server_payload_json">Server payload JSON</label>
                    <textarea id="server_payload_json" name="server_payload_json">{"result_status":"Active","notes":"Existing server version."}</textarea>
                </div>
                <div class="filter-actions">
                    <button class="button" type="submit">Record conflict</button>
                </div>
            </form>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <h2>Recent packets</h2>
            <ul class="bullet-list">
                <?php foreach ($packets as $packet): ?>
                    <li>#<?= esc((string) $packet['id']) ?> <?= esc((string) $packet['packet_name']) ?>, <?= esc((string) $packet['status']) ?>, assigned to <?= esc((string) ($packet['assigned_user_name'] ?? 'Unassigned')) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="panel">
            <h2>Open conflicts</h2>
            <?php if ($conflicts === []): ?>
                <p class="muted">No open conflicts.</p>
            <?php else: ?>
                <ul class="bullet-list">
                    <?php foreach ($conflicts as $conflict): ?>
                        <li>Packet #<?= esc((string) $conflict['packet_id']) ?>: <?= esc((string) $conflict['conflict_type']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</section>
<?= $this->endSection() ?>
