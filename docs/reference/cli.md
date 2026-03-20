# CLI Reference

## `php spark sync:open-data`

Syncs configured public-data sources into the asset inventory.

### Usage

```powershell
php spark sync:open-data <source-key> [--limit 100] [--all] [--user admin@northriver.local] [--resume-offset 2000] [--resume-job 14]
```

### Important options

- `--limit`: import only a sample
- `--all`: page through the full dataset
- `--user`: record the syncing actor
- `--resume-offset`: restart from a source offset
- `--resume-job`: restart from a previously recorded sync job

### Examples

Small sample:

```powershell
php spark sync:open-data edmonton-benches --limit 50 --user admin@northriver.local
```

Full import:

```powershell
php spark sync:open-data edmonton-trees --all --user admin@northriver.local
```

Resume from a saved job:

```powershell
php spark sync:open-data --resume-job 8 --user admin@northriver.local
```

## `php spark ops:run-scheduled`

Runs scheduled operational tasks.

### Usage

```powershell
php spark ops:run-scheduled [--source edmonton-benches] [--limit 100] [--all] [--user admin@northriver.local] [--health-only]
```

### What it can do

- source sync through the durable sync-job path
- source-health snapshot capture
- overdue reminder capture
- telemetry capture for asset counts, backlog, overdue totals, and average risk

### Examples

Health-only run:

```powershell
php spark ops:run-scheduled --health-only --user admin@northriver.local
```

Scheduled sample import:

```powershell
php spark ops:run-scheduled --source edmonton-hydrants --limit 250 --user admin@northriver.local
```

## Other useful commands

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
php vendor\bin\phpunit
php spark serve
```
