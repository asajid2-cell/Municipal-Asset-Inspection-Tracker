<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds example maintenance follow-up generated from inspection results.
 */
class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-18 15:30:00';

        $rows = [
            [
                'asset_id' => $this->assetId('UTIL-HYDRANT-014'),
                'inspection_id' => $this->inspectionIdForAsset('UTIL-HYDRANT-014'),
                'opened_by' => $this->userId('ops@northriver.local'),
                'assigned_department_id' => $this->departmentId('ROADS'),
                'title' => 'Investigate hydrant pressure issue',
                'description' => 'Review valve performance and confirm whether a line obstruction is reducing pressure.',
                'priority' => 'High',
                'status' => 'Open',
                'due_at' => '2026-03-25 17:00:00',
                'resolved_at' => null,
                'resolution_notes' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'asset_id' => $this->assetId('PLAY-STR-007'),
                'inspection_id' => $this->inspectionIdForAsset('PLAY-STR-007'),
                'opened_by' => $this->userId('ops@northriver.local'),
                'assigned_department_id' => $this->departmentId('PARKS'),
                'title' => 'Replace damaged ladder rail',
                'description' => 'Asset remains out of service until ladder rail and adjacent hardware are replaced and re-inspected.',
                'priority' => 'High',
                'status' => 'In Progress',
                'due_at' => '2026-03-22 17:00:00',
                'resolved_at' => null,
                'resolution_notes' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'asset_id' => $this->assetId('LIB-PC-022'),
                'inspection_id' => null,
                'opened_by' => $this->userId('manager@northriver.local'),
                'assigned_department_id' => $this->departmentId('LIBRARY'),
                'title' => 'Replace children wing keyboard',
                'description' => 'A spill damaged the public keyboard. Swap the peripheral and confirm the station passes accessibility checks.',
                'priority' => 'Medium',
                'status' => 'Resolved',
                'due_at' => '2026-03-12 17:00:00',
                'resolved_at' => '2026-03-11 10:15:00',
                'resolution_notes' => 'Keyboard replaced and station tested with staff before reopening.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('maintenance_requests')->insertBatch($rows);
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

    private function departmentId(string $code): int
    {
        $row = $this->db->table('departments')
            ->select('id')
            ->where('code', $code)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing department seed dependency: {$code}");
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
