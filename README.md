# Municipal Asset & Inspection Tracker

This repository is a `CodeIgniter 4` municipal operations platform built as a portfolio project. It tracks assets, inspections, maintenance follow-up, open-data syncs, reporting, exports, mobile/offline field packets, and audit history.

## What the app includes

- asset inventory with list, full-table, and map views
- inspection workflow with attachments, audit history, and automated follow-up rules
- maintenance queue with lifecycle, assignment, SLA, labor, and cost fields
- open-data sync from Edmonton public datasets with progress, deduping, and resumable offsets
- executive reporting, capital planning, source-health diagnostics, and a digital twin style operations view
- export jobs, notification outbox, offline sync packets/conflicts, and role-based access control
- local `SQLite` workflow and containerized demo setup

## Quick start

### Local PHP run

```powershell
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve
```

Open `http://localhost:8080/login`.

### Docker run

```powershell
docker compose build
docker compose up
```

Then open `http://localhost:8080/login`.

## Demo accounts

All seeded accounts use:

- `Password123!`

Available users:

- `admin@northriver.local`
- `ops@northriver.local`
- `inspector@northriver.local`
- `manager@northriver.local`
- `viewer@northriver.local`
- `fieldtech@northriver.local`

## Common commands

Reset the local database:

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

Run tests:

```powershell
php vendor\bin\phpunit
```

Sync public data:

```powershell
php spark sync:open-data edmonton-benches --limit 100 --user admin@northriver.local
php spark sync:open-data edmonton-trees --all --user admin@northriver.local
php spark sync:open-data --resume-job 12 --user admin@northriver.local
```

Run scheduled operational jobs:

```powershell
php spark ops:run-scheduled --health-only --user admin@northriver.local
php spark ops:run-scheduled --source edmonton-hydrants --limit 500 --user admin@northriver.local
```

## Documentation

The project includes a Diataxis documentation set in [docs/README.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\README.md).

If you want one end-to-end runbook first, start with [docs/how-to/run-everything.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\how-to\run-everything.md).

Recommended reading order:

1. [docs/tutorials/first-day-walkthrough.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\tutorials\first-day-walkthrough.md)
2. [docs/tutorials/operations-demo.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\tutorials\operations-demo.md)
3. [docs/explanation/architecture.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\explanation\architecture.md)
4. [docs/reference/cli.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\reference\cli.md)

## Why this project is useful in a job application

It is not just CRUD. The codebase demonstrates:

- maintainable PHP service/model/controller boundaries
- relational schema design for operational workflows
- real-world public-data ingestion and synchronization concerns
- production-oriented features like resumable jobs, telemetry, exports, and audit history
- a municipal SaaS domain instead of a generic demo app
