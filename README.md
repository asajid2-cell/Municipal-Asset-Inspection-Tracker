# Municipal Asset & Inspection Tracker

This repository is a `CodeIgniter 4` portfolio project for tracking municipal assets, inspections, and maintenance follow-up. It is designed to demonstrate the kind of backend work that matters for an internship: maintainable PHP structure, relational database design, incremental feature delivery, and realistic operational workflows.

## Current build

Implemented so far:

- real `CodeIgniter 4` project scaffold
- migrations for the core municipal domain tables
- seeders for departments, categories, users, assets, inspections, maintenance requests, and activity logs
- a lightweight home page that reflects the actual project
- basic automated tests for the home page and database foundation

## Planned stack

- Backend: `PHP 8.2+`
- Framework: `CodeIgniter 4`
- Database target: `MySQL 8`
- Local default for quick setup: `SQLite`
- Frontend: server-rendered views
- Testing: `PHPUnit`

## Local setup

These commands assume `PHP 8.2+` is installed and that `php` is available on your shell `PATH`.

1. Copy `.env.example` to `.env`
2. Choose either MySQL or SQLite in `.env` if you want to override the defaults
3. Create the SQLite file if you want the default local setup
4. Run migrations and seed the database
5. Start the local server

Example commands:

```powershell
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve
```

## Testing what exists now

Automated checks:

```powershell
php vendor\bin\phpunit
```

Manual smoke test:

1. Start the app with `php spark serve`
2. Open `http://localhost:8080`
3. Confirm the landing page loads and shows the project overview

Database reset for repeatable testing:

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

## Seeded demo users

These are development-only accounts seeded for local testing:

- `admin@northriver.local`
- `ops@northriver.local`
- `inspector@northriver.local`
- `manager@northriver.local`

Default password for all seeded users:

- `Password123!`

## Why this project fits the target role

- Matches the municipal domain Box Clever already serves
- Uses `PHP`, `MySQL`, and an MVC structure aligned with `CodeIgniter`
- Demonstrates CRUD foundations, relational schema design, filtering, validation, and workflow modeling
- Leaves room for later waves like audit logs, permissions, CSV import/export, and notifications
