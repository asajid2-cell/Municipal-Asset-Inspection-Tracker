<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates inspection history records for assets.
 */
class CreateInspectionsTable extends Migration
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
            'asset_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'inspector_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'inspected_at' => [
                'type' => 'DATETIME',
            ],
            'condition_rating' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
            ],
            'result_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'next_due_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('asset_id');
        $this->forge->addKey('inspected_at');
        $this->forge->addForeignKey('asset_id', 'assets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('inspector_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('inspections');
    }

    public function down(): void
    {
        $this->forge->dropTable('inspections', true);
    }
}
