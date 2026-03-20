<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pageTitle ?? 'Municipal Asset & Inspection Tracker') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->renderSection('head') ?>
    <style {csp-style-nonce}>
        :root {
            color-scheme: light;
            --bg: #ecf1ef;
            --paper: #ffffff;
            --paper-muted: #f7faf8;
            --line: #d3ddd8;
            --text: #19322d;
            --muted: #617770;
            --brand: #1f6b5e;
            --brand-dark: #174f45;
            --brand-soft: #dcefeb;
            --danger-soft: #f7e1de;
            --danger-text: #8a2f24;
            --warn-soft: #faefcf;
            --warn-text: #6f5308;
            --shadow: 0 14px 32px rgba(21, 41, 36, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(31, 107, 94, 0.10), transparent 26%),
                linear-gradient(180deg, #f3f7f5 0%, var(--bg) 35%, #e7eeeb 100%);
            color: var(--text);
        }

        a {
            color: inherit;
        }

        .shell {
            max-width: 1120px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(211, 221, 216, 0.9);
            border-radius: 18px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .brand {
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
        }

        .brand span {
            display: block;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav a {
            padding: 0.55rem 0.9rem;
            border-radius: 999px;
            text-decoration: none;
            color: var(--muted);
            font-weight: 600;
        }

        .nav a.is-active {
            background: var(--brand-soft);
            color: var(--brand-dark);
        }

        .user-meta {
            text-align: right;
        }

        .user-meta strong {
            display: block;
        }

        .user-meta span {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--brand-soft);
            color: var(--brand-dark);
            border-color: rgba(31, 107, 94, 0.18);
        }

        .alert-warning {
            background: var(--warn-soft);
            color: var(--warn-text);
            border-color: rgba(111, 83, 8, 0.16);
        }

        .hero,
        .panel,
        .table-card,
        .form-card,
        .detail-card,
        .stat-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(260px, 1fr);
            gap: 1.25rem;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .hero h1,
        .panel h2,
        .detail-card h1,
        .detail-card h2,
        .table-card h1,
        .form-card h1 {
            margin-top: 0;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .lede,
        .page-note,
        .muted {
            color: var(--muted);
        }

        .hero-actions,
        .actions-row,
        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .button,
        button.button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.35rem;
            padding: 0.75rem 1rem;
            border: 0;
            border-radius: 12px;
            background: var(--brand);
            color: #ffffff;
            text-decoration: none;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .button:hover,
        button.button:hover {
            background: var(--brand-dark);
        }

        .button-secondary {
            background: var(--paper-muted);
            color: var(--text);
            border: 1px solid var(--line);
        }

        .button-danger {
            background: #a44134;
        }

        .button-danger:hover {
            background: #873327;
        }

        .stats-grid,
        .content-grid,
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .content-grid {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .stat-card,
        .panel {
            padding: 1.25rem;
        }

        .stat-label {
            display: block;
            margin-bottom: 0.45rem;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .stat-value {
            display: block;
            font-size: 2rem;
            line-height: 1;
            margin-bottom: 0.6rem;
        }

        .table-card,
        .form-card,
        .detail-card {
            padding: 1.4rem;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .field,
        .search-field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .field label,
        .search-field label {
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid #c9d4cf;
            border-radius: 12px;
            background: #ffffff;
            color: var(--text);
            font: inherit;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        .field-error {
            color: var(--danger-text);
            font-size: 0.92rem;
        }

        .field.has-error input,
        .field.has-error select,
        .field.has-error textarea {
            border-color: rgba(138, 47, 36, 0.35);
            background: #fff8f7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .field.full-width {
            grid-column: 1 / -1;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.9rem 0.85rem;
            border-bottom: 1px solid #e1e8e5;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .text-link {
            color: var(--brand-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .pill-active {
            background: #d9f0e7;
            color: #196047;
        }

        .pill-neutral {
            background: #e6eef0;
            color: #31555d;
        }

        .pill-inspection {
            background: #faefcf;
            color: #6f5308;
        }

        .pill-repair {
            background: #f7e1de;
            color: #8a2f24;
        }

        .pill-out {
            background: #eadff8;
            color: #5d2e8c;
        }

        .empty-state {
            padding: 1.1rem;
            border-radius: 16px;
            background: var(--paper-muted);
            color: var(--muted);
        }

        .pagination {
            margin-top: 1rem;
        }

        .pagination nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            min-width: 2.3rem;
            justify-content: center;
            align-items: center;
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #ffffff;
            text-decoration: none;
            color: var(--text);
        }

        .pagination .active span,
        .pagination a:hover {
            background: var(--brand-soft);
            color: var(--brand-dark);
        }

        .detail-grid {
            margin-top: 1rem;
        }

        .detail-item {
            padding: 1rem;
            border-radius: 16px;
            background: var(--paper-muted);
            border: 1px solid #e0e7e4;
        }

        .detail-item span {
            display: block;
            margin-bottom: 0.3rem;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .danger-note {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 16px;
            background: #fff7f5;
            border: 1px solid #ecd3cf;
        }

        .bullet-list {
            margin: 0;
            padding-left: 1.1rem;
        }

        .panel-inline {
            padding: 0.9rem 1rem;
        }

        .split-view {
            display: grid;
            grid-template-columns: minmax(320px, 1fr) minmax(0, 2fr);
            gap: 1rem;
        }

        .map-shell {
            display: grid;
            grid-template-columns: minmax(300px, 340px) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .map-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .map-canvas {
            min-height: 72vh;
            border-radius: 20px;
            border: 1px solid var(--line);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .map-asset-list {
            max-height: 420px;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .map-asset-card {
            padding: 0.9rem 1rem;
            border-radius: 14px;
            background: var(--paper-muted);
            border: 1px solid #dfe7e3;
        }

        .map-asset-card.is-selected {
            border-color: rgba(31, 107, 94, 0.4);
            background: #edf7f4;
        }

        .page-note {
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        code {
            padding: 0.15rem 0.35rem;
            border-radius: 6px;
            background: #edf3f0;
        }

        @media (max-width: 720px) {
            .shell {
                padding: 1rem;
            }

            .topbar,
            .hero {
                grid-template-columns: 1fr;
            }

            .toolbar {
                align-items: stretch;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .split-view,
            .map-shell {
                grid-template-columns: 1fr;
            }

            .button,
            button.button {
                width: 100%;
            }

            .search-form,
            .actions-row,
            .inline-actions,
            .hero-actions,
            .filter-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <a class="brand" href="<?= esc(site_url('/')) ?>">
            Municipal Asset Tracker
            <span>North River Operations</span>
        </a>
        <div class="topbar-right">
            <nav class="nav" aria-label="Primary navigation">
                <a class="<?= ($activeNav ?? '') === 'home' ? 'is-active' : '' ?>" href="<?= esc(site_url('/')) ?>">Overview</a>
                <a class="<?= ($activeNav ?? '') === 'reports' ? 'is-active' : '' ?>" href="<?= esc(site_url('reports')) ?>">Reports</a>
                <a class="<?= ($activeNav ?? '') === 'twin' ? 'is-active' : '' ?>" href="<?= esc(site_url('digital-twin')) ?>">Twin</a>
                <a class="<?= ($activeNav ?? '') === 'plans' ? 'is-active' : '' ?>" href="<?= esc(site_url('capital-planning')) ?>">Plans</a>
                <a class="<?= ($activeNav ?? '') === 'assets' ? 'is-active' : '' ?>" href="<?= esc(site_url('assets')) ?>">Assets</a>
                <a class="<?= ($activeNav ?? '') === 'maintenance' ? 'is-active' : '' ?>" href="<?= esc(site_url('maintenance-requests')) ?>">Maintenance</a>
                <a class="<?= ($activeNav ?? '') === 'mobile' ? 'is-active' : '' ?>" href="<?= esc(site_url('mobile-ops')) ?>">Mobile</a>
                <a class="<?= ($activeNav ?? '') === 'notifications' ? 'is-active' : '' ?>" href="<?= esc(site_url('notifications')) ?>">Notifications</a>
                <a class="<?= ($activeNav ?? '') === 'audit' ? 'is-active' : '' ?>" href="<?= esc(site_url('audit-log')) ?>">Audit</a>
                <?php if (($authUser['role'] ?? '') === 'admin'): ?>
                    <a class="<?= ($activeNav ?? '') === 'admin' ? 'is-active' : '' ?>" href="<?= esc(site_url('admin')) ?>">Admin</a>
                <?php endif; ?>
            </nav>

            <?php $authUser = session()->get('auth_user'); ?>
            <?php if (is_array($authUser)): ?>
                <div class="user-meta">
                    <strong><?= esc((string) $authUser['full_name']) ?></strong>
                    <span><?= esc(ucwords(str_replace('_', ' ', (string) $authUser['role']))) ?></span>
                </div>
                <a class="button button-secondary" href="<?= esc(site_url('logout')) ?>">Sign out</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
        <div class="alert alert-warning"><?= esc((string) session()->getFlashdata('warning')) ?></div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</div>
<?= $this->renderSection('scripts') ?>
</body>
</html>
