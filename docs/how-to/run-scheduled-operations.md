# Run Scheduled Operations

The app includes a CLI path for recurring operational tasks.

## Capture health and telemetry only

```powershell
php spark ops:run-scheduled --health-only --user admin@northriver.local
```

This does three useful things without importing new public data:

- captures source-health snapshots
- captures overdue reminder notifications
- captures operational telemetry metrics

## Run a scheduled sync with the same command

```powershell
php spark ops:run-scheduled --source edmonton-hydrants --limit 500 --user admin@northriver.local
```

For a full source import:

```powershell
php spark ops:run-scheduled --source edmonton-benches --all --user admin@northriver.local
```

## Where to verify results

After a scheduled run, check:

- `/admin` for sync jobs, source-health snapshots, and telemetry
- `/notifications` for captured reminders
- `/reports` for updated rollups
