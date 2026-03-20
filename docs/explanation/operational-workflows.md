# Operational Workflows

This document explains how the main workflows fit together conceptually.

## Asset lifecycle

An asset moves through several overlapping concerns:

- inventory identity and location
- inspection cadence
- condition and risk
- maintenance follow-up
- audit and history

That is why the asset record stores both simple inventory fields and operational fields like `risk_score`, `lifecycle_state`, and `next_inspection_due_at`.

## Inspection-driven updates

An inspection is not a passive note. It is meant to change the operational state of the asset.

Typical chain:

1. an inspector logs a result
2. the asset status and next due date are updated
3. an asset version snapshot is recorded
4. an audit entry is written
5. workflow rules are evaluated
6. follow-up work and notifications may be created

## Maintenance as a queue

Maintenance requests exist because operational work needs its own lifecycle. A failed inspection should not remain buried inside free-text notes.

The request model supports:

- priority
- assignment
- due dates and SLA target
- work order code
- labor and cost tracking
- status progression

## Why asset version history exists alongside audit logs

Audit logs answer:

- who did what
- when did they do it

Asset version history answers:

- what did the asset look like before and after a change

Those are related but not identical needs, so the project keeps both.

## Offline and field workflows

The `mobile-ops` features exist to represent field reality:

- preparing a packet of work
- storing the payload for offline use
- recording synchronization conflicts later
