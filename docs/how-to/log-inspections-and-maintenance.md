# Log Inspections and Maintenance

## Log an inspection from the UI

1. Open an asset detail page from `/assets`
2. Choose `Log inspection`
3. Fill in:
   - inspector
   - inspected date/time
   - condition rating
   - result status
   - notes
4. Submit the form

Example inspection:

- inspector: `Riley Chen`
- condition rating: `2`
- result status: `Needs Repair`
- notes: `Valve service still required`

## Create a follow-up request during inspection entry

On the same inspection form, enable the follow-up request fields when the result is a service issue.

Typical example:

- request title: `Hydrant valve repair`
- priority: `High`
- assigned department: `ROADS`
- due date: one week from inspection

## Review the resulting records

After submission, check:

- `/assets/{id}` for updated status and version history
- `/maintenance-requests` for the follow-up queue item
- `/notifications` for captured notification output
- `/audit-log` for traceability

## Update a maintenance request

Open:

- `/maintenance-requests`
- choose a request
- edit fields like status, assignee, work order code, SLA, labor hours, and actual cost

Useful examples:

- move status from `Open` to `In Progress`
- assign to a staff user
- set a `work_order_code`
- record `labor_hours` and `actual_cost`

## Attach evidence to inspections

The inspection form accepts files for evidence upload.

Supported examples:

- JPG photo of asset damage
- PNG screenshot
- PDF field note
