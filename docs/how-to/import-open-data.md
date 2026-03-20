# Import Open Data

## Import a sample of records

Example:

```powershell
php spark sync:open-data edmonton-benches --limit 100 --user admin@northriver.local
```

This is the best way to:

- test the sync path quickly
- populate a light demo database
- validate one dataset mapping

## Import a full dataset

Example:

```powershell
php spark sync:open-data edmonton-trees --all --user admin@northriver.local
```

Use full imports carefully on local SQLite because some datasets are very large.

## Resume a long sync

Resume from an offset:

```powershell
php spark sync:open-data edmonton-trees --all --resume-offset 200000 --user admin@northriver.local
```

Resume from a recorded sync job:

```powershell
php spark sync:open-data --resume-job 14 --user admin@northriver.local
```

## Trigger sync from the UI

Use:

- `/assets`

The asset inventory page includes a public-data sync panel. This is useful for interactive demos when you want to show:

- configured sources
- limited versus full syncs
- inventory results immediately after import

## Verify that data arrived

Good checks:

- inventory count changes on `/assets`
- source dataset filters on `/assets/full`
- mapped assets appear on `/assets/map`
- sync job history appears on `/admin`
