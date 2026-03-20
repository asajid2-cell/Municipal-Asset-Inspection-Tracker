<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tracks import and sync runs so long-running source jobs can be inspected later.
 */
class SyncJobModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'sync_jobs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'source_key',
        'source_label',
        'status',
        'mode',
        'requested_limit',
        'processed_offset',
        'fetched_count',
        'imported_count',
        'updated_count',
        'restored_count',
        'unchanged_count',
        'skipped_count',
        'error_message',
        'started_at',
        'finished_at',
        'created_by',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentForOrganization(int $organizationId, int $limit = 10): array
    {
        return $this->where('organization_id', $organizationId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
