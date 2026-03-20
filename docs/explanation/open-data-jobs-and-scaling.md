# Open Data, Jobs, and Scaling

This project imports large public datasets, so the design has to account for scale even when the local demo uses `SQLite`.

## Why the sync layer uses adapters

Public datasets do not share one schema. Benches, hydrants, trees, parks, and drainage networks all arrive differently.

The adapter registry exists so each source can:

- fetch through a shared job path
- normalize its own fields
- map source identifiers and geometry correctly
- evolve without rewriting the whole sync system

## Why sync jobs are durable

Large imports need more than a request/response cycle.

`sync_jobs` provide:

- status
- processed offset
- fetched/imported/updated/unchanged counts
- actor tracking
- error capture

That supports both resume behavior and admin visibility.

## Why deduping is explicit

The app keeps source identifiers and a source checksum so repeat syncs can tell the difference between:

- new records
- updated records
- restored records
- unchanged records

## Why the map and export layers are bounded

Large municipal datasets can overwhelm a local PHP process if every request tries to materialize everything at once.

The project uses:

- map feature limits for dense viewports
- clustering for dense point layers
- paged full-table inventory
- chunked export jobs
- scheduled and resumable sync paths

## Why source-health snapshots exist

Source-health snapshots make integration quality visible. They track issues like:

- unmapped assets
- invalid geometry
- duplicates
- last sync activity
