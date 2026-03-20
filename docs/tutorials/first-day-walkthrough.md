# First-Day Walkthrough

This tutorial is for someone who has just cloned the project and wants to understand the main user-facing workflows in one sitting.

## Goal

By the end of this walkthrough, you will:

- boot the app locally
- sign in with a seeded account
- review the inventory
- sync in public data
- log an inspection
- see the maintenance and reporting side update

## 1. Prepare the local database

```powershell
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
php spark migrate
php spark db:seed DatabaseSeeder
```

## 2. Start the app

```powershell
php spark serve
```

Open `http://localhost:8080/login` and sign in with:

- email: `admin@northriver.local`
- password: `Password123!`

## 3. Review the inventory

Open:

- `/assets`
- `/assets/full`
- `/assets/map`

Try these filters:

- status = `Needs Repair`
- status = `Needs Inspection`
- source dataset after a sync run

## 4. Bring in real public data

```powershell
php spark sync:open-data edmonton-benches --limit 100 --user admin@northriver.local
```

Refresh `/assets` and `/assets/map`. You should now see seeded internal assets mixed with imported public assets.

## 5. Log an inspection

Open a seeded asset such as `UTIL-HYDRANT-014`, choose `Log inspection`, and submit:

- inspector: `Riley Chen`
- result status: `Needs Repair`
- note: `Valve service still required`

What should happen:

- the inspection is stored
- the asset status is updated
- an asset version is recorded
- a workflow rule can open a maintenance request
- a captured notification appears in the outbox

## 6. Review follow-up screens

Open:

- `/maintenance-requests`
- `/notifications`
- `/audit-log`
- `/reports`
- `/admin`

This shows the project as an operations platform rather than just a CRUD demo.
