<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores source quality snapshots so sync health can be reviewed over time.
 */
class SourceHealthSnapshotModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'source_health_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'organization_id',
        'source_key',
        'source_label',
        'total_assets',
        'unmapped_assets',
        'invalid_geometry_assets',
        'duplicate_assets',
        'last_synced_at',
        'captured_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestForOrganization(int $organizationId, int $limit = 20): array
    {
        return $this->where('organization_id', $organizationId)
            ->orderBy('captured_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
