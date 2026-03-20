<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores lightweight operational telemetry snapshots for diagnostics screens.
 */
class PerformanceSnapshotModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'performance_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'organization_id',
        'snapshot_type',
        'metric_key',
        'metric_value',
        'context_json',
        'captured_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentForOrganization(int $organizationId, int $limit = 20): array
    {
        return $this->where('organization_id', $organizationId)
            ->orderBy('captured_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
