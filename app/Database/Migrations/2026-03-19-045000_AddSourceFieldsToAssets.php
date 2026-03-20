<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds source metadata so live public data syncs can be re-run safely.
 */
class AddSourceFieldsToAssets extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('assets', [
            'source_system' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'notes',
            ],
            'source_dataset' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'source_system',
            ],
            'source_record_id' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'source_dataset',
            ],
            'source_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'source_record_id',
            ],
        ]);

        $this->db->query('CREATE INDEX idx_assets_source_dataset ON assets (source_dataset)');
        $this->db->query('CREATE UNIQUE INDEX uq_assets_source_record ON assets (source_dataset, source_record_id)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX IF EXISTS idx_assets_source_dataset');
        $this->db->query('DROP INDEX IF EXISTS uq_assets_source_record');
        $this->forge->dropColumn('assets', [
            'source_system',
            'source_dataset',
            'source_record_id',
            'source_url',
        ]);
    }
}
