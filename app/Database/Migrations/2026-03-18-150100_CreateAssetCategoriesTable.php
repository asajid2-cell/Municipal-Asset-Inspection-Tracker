<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates configurable categories that define asset defaults and inspection cadence.
 */
class CreateAssetCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'inspection_interval_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'default_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'Active',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('asset_categories');
    }

    public function down(): void
    {
        $this->forge->dropTable('asset_categories', true);
    }
}
