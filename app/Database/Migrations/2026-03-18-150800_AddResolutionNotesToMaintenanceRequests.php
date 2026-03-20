<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds closure context so maintenance requests can capture how work was completed.
 */
class AddResolutionNotesToMaintenanceRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('maintenance_requests', [
            'resolution_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'resolved_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('maintenance_requests', 'resolution_notes');
    }
}
