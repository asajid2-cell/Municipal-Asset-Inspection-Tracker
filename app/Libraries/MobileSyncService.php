<?php

namespace App\Libraries;

use App\Models\AssetModel;
use App\Models\InspectionModel;
use App\Models\OfflineSyncConflictModel;
use App\Models\OfflineSyncPacketModel;

/**
 * Packages field assignments for offline work and records sync conflicts on upload.
 */
class MobileSyncService
{
    /**
     * @return array<string, mixed>
     */
    public function createInspectionPacket(int $organizationId, int $assignedUserId, string $packetName, array $filters): array
    {
        $assets = (new AssetModel())->forInventoryList($filters + ['overdue' => '1'])
            ->paginate(50, 'offline', 1);

        $payload = [
            'generated_at' => date(DATE_ATOM),
            'packet_type' => 'inspection_offline_packet',
            'filters' => $filters,
            'assets' => array_map(static function (array $asset): array {
                return [
                    'id' => (int) $asset['id'],
                    'asset_code' => (string) $asset['asset_code'],
                    'name' => (string) $asset['name'],
                    'status' => (string) $asset['status'],
                    'location_text' => (string) $asset['location_text'],
                    'next_inspection_due_at' => $asset['next_inspection_due_at'],
                ];
            }, $assets),
        ];

        $model = new OfflineSyncPacketModel();
        $model->insert([
            'organization_id' => $organizationId,
            'assigned_user_id' => $assignedUserId,
            'packet_name' => $packetName,
            'status' => 'Prepared',
            'scope_json' => json_encode($filters, JSON_UNESCAPED_SLASHES),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            'generated_at' => date('Y-m-d H:i:s'),
        ], false);

        return $model->find((int) $model->getInsertID()) ?? [];
    }

    /**
     * @param array<string, mixed> $localPayload
     */
    public function recordConflict(int $organizationId, int $packetId, ?int $assetId, string $conflictType, array $localPayload, ?array $serverPayload = null): void
    {
        (new OfflineSyncConflictModel())->insert([
            'organization_id' => $organizationId,
            'packet_id' => $packetId,
            'asset_id' => $assetId,
            'conflict_type' => $conflictType,
            'local_payload_json' => json_encode($localPayload, JSON_UNESCAPED_SLASHES),
            'server_payload_json' => $serverPayload === null ? null : json_encode($serverPayload, JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
        ], false);
    }
}
