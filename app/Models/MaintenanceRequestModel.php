<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for follow-up maintenance work raised from inspections or manual reports.
 */
class MaintenanceRequestModel extends Model
{
    /**
     * Priority options exposed to operations staff.
     *
     * @var list<string>
     */
    public const PRIORITY_OPTIONS = [
        'Low',
        'Medium',
        'High',
        'Critical',
    ];

    /**
     * Status values used through the request lifecycle.
     *
     * @var list<string>
     */
    public const STATUS_OPTIONS = [
        'Open',
        'In Progress',
        'Resolved',
        'Closed',
    ];

    /**
     * Statuses considered active on dashboards and queue screens.
     *
     * @var list<string>
     */
    public const ACTIVE_STATUSES = [
        'Open',
        'In Progress',
    ];

    /**
     * Statuses considered completed for lifecycle tracking.
     *
     * @var list<string>
     */
    public const COMPLETED_STATUSES = [
        'Resolved',
        'Closed',
    ];

    protected $DBGroup = 'default';
    protected $table = 'maintenance_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'asset_id',
        'inspection_id',
        'opened_by',
        'assigned_department_id',
        'assigned_user_id',
        'work_order_code',
        'title',
        'description',
        'priority',
        'status',
        'due_at',
        'sla_target_at',
        'started_at',
        'completed_at',
        'resolved_at',
        'labor_hours',
        'estimated_cost',
        'actual_cost',
        'resolution_notes',
    ];

    /**
     * Counts active maintenance requests shown on the dashboard.
     */
    public function openRequestCount(): int
    {
        return $this->whereIn('status', self::ACTIVE_STATUSES)->countAllResults();
    }

    /**
     * Returns active requests ordered by urgency for the dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function openQueue(int $limit = 5): array
    {
        return $this->baseDetailQuery()
            ->whereIn('maintenance_requests.status', self::ACTIVE_STATUSES)
            ->orderBy('maintenance_requests.due_at', 'ASC')
            ->orderBy($this->priorityOrderSql(), 'ASC', false)
            ->limit($limit)
            ->findAll();
    }

    /**
     * Applies the maintenance queue query used by the operational list screen.
     */
    public function forQueueList(array $filters = []): self
    {
        $this->baseDetailQuery();

        $search = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $priority = trim((string) ($filters['priority'] ?? ''));
        $departmentId = $filters['assigned_department_id'] ?? null;
        $activeOnly = (string) ($filters['active_only'] ?? '') === '1';

        if ($search !== '') {
            $this->groupStart()
                ->like('maintenance_requests.title', $search)
                ->orLike('maintenance_requests.description', $search)
                ->orLike('assets.asset_code', $search)
                ->orLike('assets.name', $search)
                ->groupEnd();
        }

        if ($status !== '') {
            $this->where('maintenance_requests.status', $status);
        }

        if ($priority !== '') {
            $this->where('maintenance_requests.priority', $priority);
        }

        if ($departmentId !== null) {
            $this->where('maintenance_requests.assigned_department_id', $departmentId);
        }

        if ($activeOnly) {
            $this->whereIn('maintenance_requests.status', self::ACTIVE_STATUSES);
        }

        $this->orderBy('maintenance_requests.due_at IS NULL', 'ASC', false)
            ->orderBy('maintenance_requests.due_at', 'ASC')
            ->orderBy($this->priorityOrderSql(), 'ASC', false)
            ->orderBy('maintenance_requests.created_at', 'DESC');

        return $this;
    }

    /**
     * Returns requests linked to an asset for the detail page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forAssetHistory(int $assetId): array
    {
        return $this->baseDetailQuery()
            ->where('maintenance_requests.asset_id', $assetId)
            ->orderBy($this->statusOrderSql(), 'ASC', false)
            ->orderBy('maintenance_requests.due_at IS NULL', 'ASC', false)
            ->orderBy('maintenance_requests.due_at', 'ASC')
            ->orderBy('maintenance_requests.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Returns a single request with related asset, user, and department labels.
     *
     * @return array<string, mixed>|null
     */
    public function findDetailedRequest(int $id): ?array
    {
        return $this->baseDetailQuery()
            ->where('maintenance_requests.id', $id)
            ->first();
    }

    /**
     * Applies the joined request query shared across queue and detail screens.
     */
    private function baseDetailQuery(): self
    {
        $this->select(
            'maintenance_requests.*, assets.asset_code, assets.name AS asset_name, assets.status AS asset_status, '
            . 'assets.location_text, departments.name AS assigned_department_name, '
            . 'opened_by_user.full_name AS opened_by_name, opened_by_user.role AS opened_by_role, '
            . 'assigned_user.full_name AS assigned_user_name'
        )
            ->join('assets', 'assets.id = maintenance_requests.asset_id')
            ->join('departments', 'departments.id = maintenance_requests.assigned_department_id', 'left')
            ->join('users AS opened_by_user', 'opened_by_user.id = maintenance_requests.opened_by')
            ->join('users AS assigned_user', 'assigned_user.id = maintenance_requests.assigned_user_id', 'left');

        return $this;
    }

    /**
     * Orders higher-priority work ahead of routine issues.
     */
    private function priorityOrderSql(): string
    {
        return "CASE maintenance_requests.priority
            WHEN 'Critical' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
            ELSE 5
        END";
    }

    /**
     * Keeps active items above completed work on the asset history screen.
     */
    private function statusOrderSql(): string
    {
        return "CASE maintenance_requests.status
            WHEN 'Open' THEN 1
            WHEN 'In Progress' THEN 2
            WHEN 'Resolved' THEN 3
            WHEN 'Closed' THEN 4
            ELSE 5
        END";
    }
}
