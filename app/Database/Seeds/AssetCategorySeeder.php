<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds configurable asset categories and inspection cadences.
 */
class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-18 15:30:00';

        $rows = [
            [
                'name' => 'Park Bench',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Benches installed in parks, trails, and public gathering areas.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Fire Hydrant',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Hydrants requiring periodic utility and safety inspection.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Playground Structure',
                'inspection_interval_days' => 90,
                'default_status' => 'Active',
                'description' => 'Play equipment that needs routine safety checks.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Library Computer',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Patron-facing computer stations in branches and community hubs.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'HVAC Unit',
                'inspection_interval_days' => 120,
                'default_status' => 'Active',
                'description' => 'Heating and cooling equipment in municipal facilities.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Streetlight',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Roadway and pedestrian lighting assets.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('asset_categories')->insertBatch($rows);
    }
}
