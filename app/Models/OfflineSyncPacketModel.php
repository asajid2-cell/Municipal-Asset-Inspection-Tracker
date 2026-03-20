<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tracks offline field packets prepared for inspectors and mobile crews.
 */
class OfflineSyncPacketModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'offline_sync_packets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'assigned_user_id',
        'packet_name',
        'status',
        'scope_json',
        'payload_json',
        'generated_at',
        'synced_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentForOrganization(int $organizationId, int $limit = 10): array
    {
        return $this->select('offline_sync_packets.*, users.full_name AS assigned_user_name')
            ->join('users', 'users.id = offline_sync_packets.assigned_user_id', 'left')
            ->where('offline_sync_packets.organization_id', $organizationId)
            ->orderBy('offline_sync_packets.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
