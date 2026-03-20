<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores linear-network metadata for corridors, pipes, and roadway segments.
 */
class LinearAssetModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'linear_assets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'asset_id',
        'corridor_name',
        'network_type',
        'measure_start',
        'measure_end',
        'segment_length_m',
        'geometry_json',
    ];

    public function findForAsset(int $assetId): ?array
    {
        return $this->where('asset_id', $assetId)->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forNetwork(int $organizationId, ?string $networkType = null, int $limit = 50): array
    {
        $query = $this->select('linear_assets.*, assets.asset_code, assets.name AS asset_name')
            ->join('assets', 'assets.id = linear_assets.asset_id')
            ->where('linear_assets.organization_id', $organizationId)
            ->orderBy('linear_assets.corridor_name', 'ASC')
            ->limit($limit);

        if ($networkType !== null && $networkType !== '') {
            $query->where('linear_assets.network_type', $networkType);
        }

        return $query->findAll();
    }
}
