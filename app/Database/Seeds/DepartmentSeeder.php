<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds a believable municipal department structure for demo scenarios.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-18 15:30:00';

        $rows = [
            [
                'name' => 'Parks and Recreation',
                'code' => 'PARKS',
                'contact_email' => 'parks@northriver.local',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Facilities',
                'code' => 'FACILITIES',
                'contact_email' => 'facilities@northriver.local',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Roads and Utilities',
                'code' => 'ROADS',
                'contact_email' => 'roads@northriver.local',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Library Services',
                'code' => 'LIBRARY',
                'contact_email' => 'library@northriver.local',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('departments')->insertBatch($rows);
    }
}
