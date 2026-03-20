# HTTP Routes and Screens

## Authentication

- `GET /login`
- `POST /login`
- `GET /logout`

## Main authenticated screens

- `GET /`
- `GET /reports`
- `GET /digital-twin`
- `GET /capital-planning`
- `GET /mobile-ops`
- `GET /assets`
- `GET /assets/full`
- `GET /assets/map`
- `GET /assets/{id}`
- `GET /maintenance-requests`
- `GET /notifications`
- `GET /audit-log`
- `GET /exports`

## Editor routes

These require one of:

- `admin`
- `operations_coordinator`
- `inspector`
- `department_manager`

Routes:

- `GET /assets/new`
- `POST /assets`
- `POST /assets/import`
- `POST /assets/open-data-sync`
- `GET /assets/{id}/inspections/new`
- `POST /assets/{id}/inspections`
- `GET /assets/{id}/maintenance-requests/new`
- `POST /assets/{id}/maintenance-requests`
- `GET /assets/{id}/edit`
- `POST /assets/{id}`
- `POST /assets/{id}/archive`
- `GET /maintenance-requests/{id}/edit`
- `POST /maintenance-requests/{id}`
- `POST /notifications/overdue-reminders`
- `POST /exports/assets`
- `POST /capital-planning`
- `POST /mobile-ops/packets`
- `POST /mobile-ops/conflicts`
- `GET /exports/{id}/download`

## Admin route

- `GET /admin`

## JSON API

- `GET /api/assets`
- `GET /api/assets/map`
- `GET /api/assets/{id}`
- `GET /api/assets/{id}/inspections`
