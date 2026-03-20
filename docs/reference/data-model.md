# Data Model

This is a practical reference, not an exhaustive schema dump.

## Core operational tables

### `organizations`

Represents the tenant or operating organization.

### `departments`

Examples:

- `PARKS`
- `FACILITIES`
- `ROADS`
- `LIBRARY`

### `users`

Key fields:

- `organization_id`
- `department_id`
- `email`
- `role`
- `is_active`

### `asset_categories`

Defines asset type and inspection interval behavior.

### `assets`

Important fields:

- `asset_code`
- `department_id`
- `category_id`
- `status`
- `location_text`
- `latitude`
- `longitude`
- `last_inspected_at`
- `next_inspection_due_at`
- `condition_score`
- `criticality_score`
- `risk_score`
- `lifecycle_state`
- `replacement_cost`
- `source_dataset`
- `source_record_id`
- `source_geometry_type`

## Workflow tables

### `inspections`

- `asset_id`
- `inspector_id`
- `condition_rating`
- `result_status`
- `next_due_at`
- `offline_packet_id`
- `sync_status`

### `maintenance_requests`

- `asset_id`
- `inspection_id`
- `assigned_department_id`
- `assigned_user_id`
- `priority`
- `status`
- `work_order_code`
- `sla_target_at`
- `estimated_cost`
- `actual_cost`
- `labor_hours`

### `attachments`

- `inspection_id`
- `uploaded_by`
- `storage_path`
- `mime_type`
- `evidence_type`
- `retention_class`

### `activity_logs`

Audit trail for operational events.

### `asset_versions`

Immutable snapshots of asset state changes.

## Integration and admin tables

- `workflow_rules`
- `notification_templates`
- `notification_deliveries`
- `sync_jobs`
- `export_jobs`
- `source_health_snapshots`
- `performance_snapshots`

## Advanced domain tables

- `capital_plan_scenarios`
- `linear_assets`
- `offline_sync_packets`
- `offline_sync_conflicts`
