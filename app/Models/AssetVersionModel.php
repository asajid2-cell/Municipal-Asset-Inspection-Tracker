<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores immutable snapshots for asset history and source-sync reconciliation.
 */
class AssetVersionModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'asset_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'organization_id',
        'asset_id',
        'version_type',
        'snapshot_json',
        'reason',
        'changed_by',
        'created_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forAsset(int $assetId, int $limit = 10): array
    {
        return $this->where('asset_id', $assetId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
