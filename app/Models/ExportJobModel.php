<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores generated exports so large filtered datasets can be downloaded safely.
 */
class ExportJobModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'export_jobs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'requested_by',
        'name',
        'format',
        'filters_json',
        'status',
        'file_path',
        'row_count',
        'error_message',
        'started_at',
        'finished_at',
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
