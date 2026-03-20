<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Captures offline sync conflicts that need review before field data is applied.
 */
class OfflineSyncConflictModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'offline_sync_conflicts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'organization_id',
        'packet_id',
        'asset_id',
        'conflict_type',
        'local_payload_json',
        'server_payload_json',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'created_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function openForOrganization(int $organizationId, int $limit = 10): array
    {
        return $this->where('organization_id', $organizationId)
            ->where('resolved_at', null)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
