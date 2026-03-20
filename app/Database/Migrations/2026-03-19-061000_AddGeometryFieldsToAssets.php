<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stores source-backed geometry so map views can render points, lines, and polygons.
 */
class AddGeometryFieldsToAssets extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('assets', [
            'source_geometry_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'source_url',
            ],
            'source_geometry' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'source_geometry_type',
            ],
        ]);

        $this->db->query('CREATE INDEX idx_assets_source_geometry_type ON assets (source_geometry_type)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX IF EXISTS idx_assets_source_geometry_type');
        $this->forge->dropColumn('assets', [
            'source_geometry_type',
            'source_geometry',
        ]);
    }
}
