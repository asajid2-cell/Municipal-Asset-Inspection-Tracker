<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds sample inspections that drive asset status changes and due dates.
 */
class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-18 15:30:00';
        $inspectorId = $this->userId('inspector@northriver.local');

        $rows = [
            [
                'asset_id' => $this->assetId('UTIL-HYDRANT-014'),
                'inspector_id' => $inspectorId,
                'inspected_at' => '2026-03-01 08:30:00',
                'condition_rating' => 2,
                'result_status' => 'Needs Repair',
                'notes' => 'Flow test showed lower-than-expected pressure; valve may need servicing.',
                'next_due_at' => '2026-08-28 08:30:00',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'asset_id' => $this->assetId('PLAY-STR-007'),
                'inspector_id' => $inspectorId,
                'inspected_at' => '2026-03-05 09:15:00',
                'condition_rating' => 1,
                'result_status' => 'Out of Service',
                'notes' => 'Damaged ladder rail creates safety hazard; close asset until repaired.',
                'next_due_at' => '2026-06-03 09:15:00',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'asset_id' => $this->assetId('LIB-PC-022'),
                'inspector_id' => $inspectorId,
                'inspected_at' => '2026-02-20 14:20:00',
                'condition_rating' => 4,
                'result_status' => 'Active',
                'notes' => 'Station passed hardware and accessibility checks.',
                'next_due_at' => '2026-08-19 14:20:00',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('inspections')->insertBatch($rows);
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
