<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Captures development-safe outbound notification deliveries.
 */
class CreateNotificationDeliveriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'channel' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'email',
            ],
            'recipient_email' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'recipient_name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'body_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'context_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
            ],
            'context_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'Captured',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['channel', 'status']);
        $this->forge->addKey(['context_type', 'context_id']);
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('notification_deliveries');
    }

    public function down(): void
    {
        $this->forge->dropTable('notification_deliveries', true);
    }
}
