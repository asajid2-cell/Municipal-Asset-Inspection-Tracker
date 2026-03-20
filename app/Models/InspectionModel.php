<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for historical inspection records tied to assets.
 */
class InspectionModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'inspections';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'asset_id',
        'inspector_id',
        'inspected_at',
        'condition_rating',
        'result_status',
        'notes',
        'next_due_at',
        'offline_packet_id',
        'sync_status',
    ];

    /**
     * Returns the inspection history for a single asset with inspector context.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forAssetHistory(int $assetId): array
    {
        return $this->select(
            'inspections.*, users.full_name AS inspector_name, users.role AS inspector_role'
        )
            ->join('users', 'users.id = inspections.inspector_id')
            ->where('inspections.asset_id', $assetId)
            ->orderBy('inspections.inspected_at', 'DESC')
            ->findAll();
    }

    /**
     * Returns inspection history in a stable projection for JSON reads.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forApiAssetHistory(int $assetId): array
    {
        return $this->select(
            'inspections.id, inspections.asset_id, inspections.inspected_at, inspections.condition_rating, '
            . 'inspections.result_status, inspections.notes, inspections.next_due_at, users.full_name AS inspector_name'
        )
            ->join('users', 'users.id = inspections.inspector_id')
            ->where('inspections.asset_id', $assetId)
            ->orderBy('inspections.inspected_at', 'DESC')
            ->findAll();
    }
}
