# Operations Demo

This tutorial is for demonstrating the project to a recruiter, interviewer, or teammate in 10 to 15 minutes.

## Recommended flow

## 1. Start at the dashboard

Use `admin@northriver.local` and open `/`.

Explain:

- asset totals
- backlog
- overdue work
- recent planning and export activity

## 2. Show inventory breadth

Open:

- `/assets`
- `/assets/full`
- `/assets/map`

Good filters to use live:

- `status=Needs Repair`
- `geometry_family=point`
- `source_dataset=x4n2-2ke2`

## 3. Show real-world data sync

```powershell
php spark sync:open-data edmonton-benches --limit 50 --user admin@northriver.local
```

Mention:

- progress output
- imported versus unchanged counts
- resume support with `--resume-offset` and `--resume-job`

## 4. Show inspection to maintenance flow

Open a hydrant or bench, log an inspection with `Needs Repair`, then open:

- `/maintenance-requests`
- `/notifications`
- `/audit-log`

## 5. Show executive and admin depth

Open:

- `/reports`
- `/capital-planning`
- `/digital-twin`
- `/mobile-ops`
- `/admin`

This sequence shows reporting, planning, offline ops, and diagnostics in addition to inventory management.
