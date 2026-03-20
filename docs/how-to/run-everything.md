# Run Everything

This is the practical runbook for the whole project. Use it when you want one place that explains how to start the app, seed data, log in, sync public data, run scheduled jobs, export data, and run tests.

## What this guide covers

- local setup with PHP and SQLite
- optional Docker setup
- database reset and reseed
- how to sign in
- how to sync real public data
- how to run scheduled operations
- how to run tests
- a simple manual smoke-test flow

## Before you start

You need:

- `PHP 8.2+`
- Composer dependencies installed
- write access to the repo directory

If `php` is not on your shell `PATH`, use the full PHP executable path instead of `php`.

Example:

```powershell
& 'C:\path\to\php.exe' spark migrate
```

## Option 1: Run locally with PHP and SQLite

### 1. Install dependencies

Run this once per fresh clone:

```powershell
composer install
```

What it does:

- installs CodeIgniter and project dependencies
- makes `spark` commands and PHPUnit available

### 2. Prepare the local SQLite database

```powershell
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
```

What it does:

- creates the database folder expected by the local config
- creates the SQLite file the app will use by default

### 3. Run migrations

```powershell
php spark migrate
```

What it does:

- creates all tables
- builds the inventory, inspection, maintenance, reporting, admin, and offline-ops schema

### 4. Seed demo data

```powershell
php spark db:seed DatabaseSeeder
```

What it does:

- creates the demo organization
- seeds departments, users, categories, assets, inspections, maintenance requests, and activity logs
- seeds workflow rules, notification templates, and planning/demo platform data

### 5. Start the local server

```powershell
php spark serve
```

What it does:

- starts the local CodeIgniter development server
- usually serves the app at `http://localhost:8080`

### 6. Sign in

Open:

- `http://localhost:8080/login`

Use:

- email: `admin@northriver.local`
- password: `Password123!`

Other useful demo accounts are listed in [demo-accounts.md](z:\328\CMPUT328-A2\codexworks\301\WorkRepo\Municipal%20Asset%20%26%20Inspection%20Tracker\docs\reference\demo-accounts.md).

## Option 2: Run with Docker

### 1. Build and start the container

```powershell
docker compose build
docker compose up
```

What it does:

- builds the PHP 8.2 Apache image
- serves the app through the container

### 2. Run migrations and seed data inside the container

```powershell
docker compose exec app php spark migrate
docker compose exec app php spark db:seed DatabaseSeeder
```

### 3. Open the app

Open:

- `http://localhost:8080/login`

## Reset everything to a known demo state

If your data is messy or you want a predictable demo:

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

What it does:

- drops and recreates the schema
- reseeds a known clean state

Use this before:

- demos
- screenshots
- testing workflows again from the beginning

## Import public data

### Small sample import

```powershell
php spark sync:open-data edmonton-benches --limit 100 --user admin@northriver.local
```

Use this when:

- you want fast demo data
- you want to test the sync path without loading a huge dataset

### Full import

```powershell
php spark sync:open-data edmonton-trees --all --user admin@northriver.local
```

Use this when:

- you want a high-volume dataset
- you want to test pagination, map density, and large inventories

### Resume a stopped import

Resume from a known offset:

```powershell
php spark sync:open-data edmonton-trees --all --resume-offset 200000 --user admin@northriver.local
```

Resume from a saved job:

```powershell
php spark sync:open-data --resume-job 12 --user admin@northriver.local
```

Use resume when:

- a full dataset is too large for one sitting
- you interrupted a previous run
- you want to continue from the last recorded sync position

## Run scheduled operations

### Health-only scheduled run

```powershell
php spark ops:run-scheduled --health-only --user admin@northriver.local
```

What it does:

- captures source-health snapshots
- captures overdue reminder notifications
- captures operational telemetry

### Scheduled run with source sync

```powershell
php spark ops:run-scheduled --source edmonton-hydrants --limit 500 --user admin@northriver.local
```

Or full mode:

```powershell
php spark ops:run-scheduled --source edmonton-benches --all --user admin@northriver.local
```

Use this when:

- you want one command that behaves like a cron or scheduler entry point
- you want to demo the operational side of the platform, not just manual imports

## Run tests

### Full PHPUnit run

```powershell
php vendor\bin\phpunit
```

What it does:

- runs the test suite against the configured test environment

### Run one test file

Examples:

```powershell
php vendor\bin\phpunit tests\feature\PlatformExpansionTest.php
php vendor\bin\phpunit tests\feature\IntegrationReadyTest.php
```

Use single-file runs when:

- you are working on one feature area
- you want quicker feedback

## Manual smoke-test flow

After the app is running, this is a good minimum manual check:

1. Sign in as `admin@northriver.local`
2. Open `/assets`
3. Open `/assets/map`
4. Run a small public-data sync
5. Open one asset and log an inspection
6. Confirm updates appear in `/maintenance-requests`
7. Confirm notifications appear in `/notifications`
8. Confirm changes appear in `/audit-log`
9. Open `/reports` and `/admin`

## Where to look after each command

- after `migrate` and `db:seed`: open `/login`
- after `sync:open-data`: check `/assets`, `/assets/map`, `/admin`
- after `ops:run-scheduled`: check `/admin`, `/reports`, `/notifications`
- after inspection entry: check `/assets/{id}`, `/maintenance-requests`, `/notifications`, `/audit-log`
- after export creation from the UI: check `/exports`

## Troubleshooting

### `php` is not recognized

Use the full PHP path:

```powershell
& 'C:\path\to\php.exe' spark serve
```

### The app opens but login fails

Reset and reseed:

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

Then try `admin@northriver.local` with `Password123!` again.

### A large sync takes a long time

Start with:

```powershell
php spark sync:open-data edmonton-benches --limit 100 --user admin@northriver.local
```

Then use `--all` only when you really want a full dataset. For interrupted runs, use `--resume-offset` or `--resume-job`.

## Short version

If you only want the smallest working local flow:

```powershell
composer install
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve
```

Then sign in at `http://localhost:8080/login` with:

- `admin@northriver.local`
- `Password123!`
