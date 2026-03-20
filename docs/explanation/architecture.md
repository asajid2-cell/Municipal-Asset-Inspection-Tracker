# Architecture

The app started as a municipal asset tracker and grew into a broader municipal operations platform. The architecture reflects that growth without abandoning a clear `CodeIgniter 4` shape.

## High-level structure

The project uses:

- controllers for HTTP request handling and view composition
- models for persistence and reusable queries
- libraries for workflow, sync, export, planning, and reporting services
- migrations and seeders for reproducible environments
- server-rendered views for the UI

## Why the service layer exists

As the app moved beyond CRUD, business logic no longer fit well in controllers alone.

Examples:

- `WorkflowRuleEngine` handles rule-based follow-up from inspections
- `ReportingService` builds executive rollups
- `SyncJobManager` wraps long-running data syncs in durable job records
- `ExportManager` writes large CSV exports in chunks

This keeps controllers thinner and makes the harder logic easier to test and reason about.

## Why the app keeps both internal and imported assets in one inventory

The project deliberately merges:

- internally managed seeded assets
- externally sourced public assets

That supports a more realistic municipal environment, where operational systems often reconcile internal workflows with source systems and public-data feeds instead of assuming one clean dataset.

## Why server-rendered views were kept

The project uses server-rendered views because:

- the focus is backend and product logic
- the internship target is PHP-oriented
- the UI only needs enough richness to support workflows and demonstrations
