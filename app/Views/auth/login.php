<?php
/**
 * @var array<int, array<string, mixed>> $demoUsers
 * @var array<string, string> $errors
 */

$fieldValue = static function (string $field): string {
    return (string) old($field);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pageTitle ?? 'Sign In') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            color-scheme: light;
            --bg: #ecf1ef;
            --paper: #ffffff;
            --line: #d3ddd8;
            --text: #19322d;
            --muted: #617770;
            --brand: #1f6b5e;
            --brand-dark: #174f45;
            --brand-soft: #dcefeb;
            --warn-soft: #faefcf;
            --warn-text: #6f5308;
            --shadow: 0 20px 42px rgba(21, 41, 36, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(31, 107, 94, 0.12), transparent 30%),
                linear-gradient(180deg, #f4f8f6 0%, #e9f0ed 100%);
            color: var(--text);
        }

        .login-shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.95fr);
            gap: 1rem;
        }

        .panel {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        h1, h2 { margin-top: 0; }

        .muted { color: var(--muted); }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1rem;
        }

        label { font-weight: 600; }

        input {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid #c9d4cf;
            border-radius: 12px;
            background: #ffffff;
            color: var(--text);
            font: inherit;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 0.85rem 1rem;
            border: 0;
            border-radius: 12px;
            background: var(--brand);
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .button:hover { background: var(--brand-dark); }

        .alert {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            background: var(--warn-soft);
            color: var(--warn-text);
        }

        .field-error {
            color: #8a2f24;
            font-size: 0.92rem;
        }

        .demo-list {
            margin: 0;
            padding-left: 1.2rem;
        }

        code {
            padding: 0.1rem 0.35rem;
            border-radius: 6px;
            background: #edf3f0;
        }

        @media (max-width: 800px) {
            .login-shell {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="login-shell">
    <section class="panel">
        <span class="eyebrow">Internal sign-in</span>
        <h1>Municipal Asset & Inspection Tracker</h1>
        <p class="muted">
            Sign in with a seeded staff account to access the inventory, maintenance queue, attachments, and audit log.
        </p>

        <?php if (session()->getFlashdata('warning')): ?>
            <div class="alert"><?= esc((string) session()->getFlashdata('warning')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert" style="background: #dcefeb; color: #174f45;"><?= esc((string) session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= esc(site_url('login')) ?>">
            <?= csrf_field() ?>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= esc($fieldValue('email')) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="field-error"><?= esc($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error"><?= esc($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <button class="button" type="submit">Sign in</button>
        </form>
    </section>

    <aside class="panel">
        <h2>Demo accounts</h2>
        <p class="muted">All seeded accounts use the same password: <code>Password123!</code></p>
        <ul class="demo-list">
            <?php foreach ($demoUsers as $user): ?>
                <li>
                    <?= esc((string) $user['full_name']) ?>,
                    <code><?= esc((string) $user['email']) ?></code>,
                    <?= esc(ucwords(str_replace('_', ' ', (string) $user['role']))) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>
</body>
</html>
