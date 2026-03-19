<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($projectName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style {csp-style-nonce}>
        :root {
            color-scheme: light;
            --bg: #f4f7f6;
            --card: #ffffff;
            --line: #d7e1dd;
            --text: #1d2b28;
            --muted: #526763;
            --accent: #1b6b5d;
            --accent-soft: #d7ece8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #e8f0ee 0%, var(--bg) 40%, #edf3f1 100%);
            color: var(--text);
        }

        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }

        .hero,
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(17, 33, 29, 0.06);
        }

        .hero {
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 0.9rem;
            font-weight: 600;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        p {
            line-height: 1.6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .card {
            padding: 1.25rem;
        }

        ul {
            margin: 0.75rem 0 0;
            padding-left: 1.1rem;
        }

        code {
            background: #eff5f3;
            border-radius: 6px;
            padding: 0.15rem 0.4rem;
        }

        .footer-note {
            margin-top: 1.5rem;
            color: var(--muted);
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
<main>
    <section class="hero">
        <span class="eyebrow"><?= esc($currentBuild) ?></span>
        <h1><?= esc($projectName) ?></h1>
        <p>
            This project is being built as a realistic municipal operations tool in <code>CodeIgniter 4</code>.
            The current milestone focuses on foundation work: project setup, schema design, and seeded demo data
            that will support the next feature builds.
        </p>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Implemented now</h2>
            <ul>
                <li>Framework scaffold and project scripts</li>
                <li>Core migrations for municipal asset tracking</li>
                <li>Seeders for departments, categories, users, and assets</li>
                <li>Operational sample data for inspections and maintenance flow</li>
            </ul>
        </article>
        <article class="card">
            <h2>Next up</h2>
            <ul>
                <li>Asset CRUD interfaces</li>
                <li>Inspection history views</li>
                <li>Status transitions and due date logic</li>
                <li>Search, filters, and dashboard summaries</li>
            </ul>
        </article>
        <article class="card">
            <h2>Useful commands</h2>
            <ul>
                <li><code>php spark migrate</code></li>
                <li><code>php spark db:seed DatabaseSeeder</code></li>
                <li><code>php spark serve</code></li>
                <li><code>php vendor/bin/phpunit</code></li>
            </ul>
        </article>
    </section>

    <p class="footer-note">
        Environment: <?= esc(ENVIRONMENT) ?>. Page rendered in {elapsed_time} seconds using {memory_usage} MB.
    </p>
</main>
</body>
</html>
