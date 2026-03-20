<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds additional categories used by the expanded Edmonton open-data catalog.
 */
class AddExpandedAssetCategories extends Migration
{
    /**
     * @var array<int, array<string, int|string>>
     */
    private array $rows = [
        [
            'name' => 'Park',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Park spaces and park parcels tracked as public landscape assets.',
        ],
        [
            'name' => 'Spray Park',
            'inspection_interval_days' => 90,
            'default_status' => 'Active',
            'description' => 'Spray decks and water-play assets in public recreation areas.',
        ],
        [
            'name' => 'Drainage Manhole',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Access structures in the underground drainage network.',
        ],
        [
            'name' => 'Catch Basin',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Roadside drainage inlets that collect stormwater runoff.',
        ],
        [
            'name' => 'Pump Station',
            'inspection_interval_days' => 180,
            'default_status' => 'Active',
            'description' => 'Drainage pump stations that move water through the network.',
        ],
        [
            'name' => 'Drainage Outfall',
            'inspection_interval_days' => 180,
            'default_status' => 'Active',
            'description' => 'Outfalls that discharge stormwater into receiving water bodies.',
        ],
        [
            'name' => 'Drainage Inlet/Outlet',
            'inspection_interval_days' => 180,
            'default_status' => 'Active',
            'description' => 'Inlets and outlets connected to stormwater managed features.',
        ],
        [
            'name' => 'Drainage Pipe Segment',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Linear drainage pipe segments represented as network assets.',
        ],
        [
            'name' => 'Catch Basin Lead',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Linear leads that connect catch basins into the drainage network.',
        ],
        [
            'name' => 'Stormwater Facility',
            'inspection_interval_days' => 365,
            'default_status' => 'Active',
            'description' => 'Stormwater ponds and similar managed drainage facilities.',
        ],
    ];

    public function up(): void
    {
        $table = $this->db->table('asset_categories');

        foreach ($this->rows as $row) {
            $exists = $table->where('name', $row['name'])->get()->getRowArray();

            if ($exists !== null) {
                continue;
            }

            $table->insert($row + [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $table = $this->db->table('asset_categories');

        foreach ($this->rows as $row) {
            $table->where('name', $row['name'])->delete();
        }
    }
}
