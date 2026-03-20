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
            [
                'name' => 'City Tree',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'City-owned trees tracked for urban forest planning and inspections.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Park',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Park spaces and park parcels tracked as public landscape assets.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Spray Park',
                'inspection_interval_days' => 90,
                'default_status' => 'Active',
                'description' => 'Spray decks and water-play assets in public recreation areas.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Drainage Manhole',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Access structures in the underground drainage network.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Catch Basin',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Roadside drainage inlets that collect stormwater runoff.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Pump Station',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Drainage pump stations that move water through the network.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Drainage Outfall',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Outfalls that discharge stormwater into receiving water bodies.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Drainage Inlet/Outlet',
                'inspection_interval_days' => 180,
                'default_status' => 'Active',
                'description' => 'Inlets and outlets connected to stormwater managed features.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Drainage Pipe Segment',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Linear drainage pipe segments represented as network assets.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Catch Basin Lead',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Linear leads that connect catch basins into the drainage network.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Stormwater Facility',
                'inspection_interval_days' => 365,
                'default_status' => 'Active',
                'description' => 'Stormwater ponds and similar managed drainage facilities.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $table = $this->db->table('asset_categories');

        foreach ($rows as $row) {
            $exists = $table->where('name', $row['name'])->get()->getRowArray();

            if ($exists !== null) {
                continue;
            }

            $table->insert($row);
        }
    }
}
