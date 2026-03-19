<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds a small audit trail so later list and dashboard work has realistic history.
 */
class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'actor_user_id' => $this->userId('admin@northriver.local'),
                'entity_type' => 'asset',
                'entity_id' => $this->assetId('PARK-BENCH-001'),
                'action' => 'created',
                'summary' => 'Created Riverfront Bench A asset record.',
                'metadata_json' => json_encode(['asset_code' => 'PARK-BENCH-001']),
                'created_at' => '2026-03-18 15:31:00',
            ],
            [
                'actor_user_id' => $this->userId('inspector@northriver.local'),
                'entity_type' => 'inspection',
                'entity_id' => $this->inspectionIdForAsset('UTIL-HYDRANT-014'),
                'action' => 'created',
                'summary' => 'Logged hydrant inspection with repair follow-up.',
                'metadata_json' => json_encode(['result_status' => 'Needs Repair']),
                'created_at' => '2026-03-18 15:32:00',
            ],
            [
                'actor_user_id' => $this->userId('ops@northriver.local'),
                'entity_type' => 'maintenance_request',
                'entity_id' => $this->maintenanceRequestId('Investigate hydrant pressure issue'),
                'action' => 'created',
                'summary' => 'Opened maintenance request for hydrant pressure issue.',
                'metadata_json' => json_encode(['priority' => 'High']),
                'created_at' => '2026-03-18 15:33:00',
            ],
            [
                'actor_user_id' => $this->userId('inspector@northriver.local'),
                'entity_type' => 'asset',
                'entity_id' => $this->assetId('PLAY-STR-007'),
                'action' => 'status_changed',
                'summary' => 'Marked Aspen Grove Climber as out of service after failed inspection.',
                'metadata_json' => json_encode(['status' => 'Out of Service']),
                'created_at' => '2026-03-18 15:34:00',
            ],
        ];

        $this->db->table('activity_logs')->insertBatch($rows);
    }

    private function assetId(string $assetCode): int
    {
        $row = $this->db->table('assets')
            ->select('id')
            ->where('asset_code', $assetCode)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing asset seed dependency: {$assetCode}");
        }

        return (int) $row['id'];
    }

    private function inspectionIdForAsset(string $assetCode): int
    {
        $row = $this->db->table('inspections')
            ->select('inspections.id')
            ->join('assets', 'assets.id = inspections.asset_id')
            ->where('assets.asset_code', $assetCode)
            ->orderBy('inspected_at', 'DESC')
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing inspection seed dependency for asset: {$assetCode}");
        }

        return (int) $row['id'];
    }

    private function maintenanceRequestId(string $title): int
    {
        $row = $this->db->table('maintenance_requests')
            ->select('id')
            ->where('title', $title)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing maintenance request seed dependency: {$title}");
        }

        return (int) $row['id'];
    }

    private function userId(string $email): int
    {
        $row = $this->db->table('users')
            ->select('id')
            ->where('email', $email)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing user seed dependency: {$email}");
        }

        return (int) $row['id'];
    }
}
