<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Expands the demo into a broader municipal operations platform with tenant,
 * reporting, workflow, export, planning, and mobile sync support.
 */
class CreateAdvancedOperationsPlatform extends Migration
{
    public function up(): void
    {
        $this->createOrganizationsTable();
        $this->extendCoreTables();
        $this->createAssetVersionsTable();
        $this->createWorkflowRulesTable();
        $this->createNotificationTemplatesTable();
        $this->createSyncJobsTable();
        $this->createExportJobsTable();
        $this->createCapitalPlanScenariosTable();
        $this->createLinearAssetsTable();
        $this->createOfflineSyncTables();
        $this->createPerformanceSnapshotsTable();
        $this->createSourceHealthSnapshotsTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('source_health_snapshots', true);
        $this->forge->dropTable('performance_snapshots', true);
        $this->forge->dropTable('offline_sync_conflicts', true);
        $this->forge->dropTable('offline_sync_packets', true);
        $this->forge->dropTable('linear_assets', true);
        $this->forge->dropTable('capital_plan_scenarios', true);
        $this->forge->dropTable('export_jobs', true);
        $this->forge->dropTable('sync_jobs', true);
        $this->forge->dropTable('notification_templates', true);
        $this->forge->dropTable('workflow_rules', true);
        $this->forge->dropTable('asset_versions', true);
        $this->forge->dropTable('organizations', true);
    }

    private function createOrganizationsTable(): void
    {
        if ($this->tableExists('organizations')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
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
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('organizations');
    }

    private function extendCoreTables(): void
    {
        $this->addColumnsIfMissing('users', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
        ]);

        $this->addColumnsIfMissing('assets', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
            'condition_score' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'criticality_score' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'risk_score' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'null' => true,
            ],
            'lifecycle_state' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'Operate',
            ],
            'replacement_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
            ],
            'actual_cost_to_date' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
            ],
            'service_level' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'source_checksum' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
        ]);

        $this->addColumnsIfMissing('inspections', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
            'offline_packet_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'sync_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'Live',
            ],
        ]);

        $this->addColumnsIfMissing('maintenance_requests', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
            'assigned_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'work_order_code' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'sla_target_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'labor_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'null' => true,
            ],
            'estimated_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
            ],
            'actual_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
            ],
        ]);

        $this->addColumnsIfMissing('attachments', [
            'evidence_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'retention_class' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
        ]);

        $this->addColumnsIfMissing('notification_deliveries', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
            'template_key' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
        ]);

        $this->addColumnsIfMissing('activity_logs', [
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 1,
            ],
        ]);
    }

    private function createAssetVersionsTable(): void
    {
        if ($this->tableExists('asset_versions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'asset_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'version_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
            ],
            'snapshot_json' => [
                'type' => 'TEXT',
            ],
            'reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'changed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['organization_id', 'asset_id']);
        $this->forge->createTable('asset_versions');
    }

    private function createWorkflowRulesTable(): void
    {
        if ($this->tableExists('workflow_rules')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'trigger_event' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'enabled' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'match_status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'min_condition_rating' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'create_request' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'default_priority' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'notification_template_key' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'assign_department_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'due_in_days' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addKey(['organization_id', 'trigger_event', 'enabled']);
        $this->forge->createTable('workflow_rules');
    }

    private function createNotificationTemplatesTable(): void
    {
        if ($this->tableExists('notification_templates')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'template_key' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'channel' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'email',
            ],
            'subject_template' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'body_template' => [
                'type' => 'TEXT',
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
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
        $this->forge->addUniqueKey(['organization_id', 'template_key']);
        $this->forge->createTable('notification_templates');
    }

    private function createSyncJobsTable(): void
    {
        if ($this->tableExists('sync_jobs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'source_key' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'source_label' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'Queued',
            ],
            'mode' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'sample',
            ],
            'requested_limit' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'processed_offset' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'fetched_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'imported_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'updated_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'restored_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'unchanged_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'skipped_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'finished_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
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
        $this->forge->addKey(['organization_id', 'status']);
        $this->forge->createTable('sync_jobs');
    }

    private function createExportJobsTable(): void
    {
        if ($this->tableExists('export_jobs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'requested_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'format' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'csv',
            ],
            'filters_json' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'Queued',
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'row_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'finished_at' => [
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
        $this->forge->addKey(['organization_id', 'status']);
        $this->forge->createTable('export_jobs');
    }

    private function createCapitalPlanScenariosTable(): void
    {
        if ($this->tableExists('capital_plan_scenarios')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'planning_horizon_years' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'annual_budget' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'strategy_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'summary_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'generated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
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
        $this->forge->addKey(['organization_id', 'generated_at']);
        $this->forge->createTable('capital_plan_scenarios');
    }

    private function createLinearAssetsTable(): void
    {
        if ($this->tableExists('linear_assets')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'asset_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'corridor_name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'network_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
            ],
            'measure_start' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'measure_end' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'segment_length_m' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'geometry_json' => [
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
        $this->forge->addUniqueKey(['organization_id', 'asset_id']);
        $this->forge->createTable('linear_assets');
    }

    private function createOfflineSyncTables(): void
    {
        if (! $this->tableExists('offline_sync_packets')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'organization_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                ],
                'assigned_user_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ],
                'packet_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 190,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'Prepared',
                ],
                'scope_json' => [
                    'type' => 'TEXT',
                ],
                'payload_json' => [
                    'type' => 'TEXT',
                ],
                'generated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'synced_at' => [
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
            $this->forge->addKey(['organization_id', 'status']);
            $this->forge->createTable('offline_sync_packets');
        }

        if (! $this->tableExists('offline_sync_conflicts')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'organization_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                ],
                'packet_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                ],
                'asset_id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ],
                'conflict_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                ],
                'local_payload_json' => [
                    'type' => 'TEXT',
                ],
                'server_payload_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'resolution_notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'resolved_by' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ],
                'resolved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['organization_id', 'packet_id']);
            $this->forge->createTable('offline_sync_conflicts');
        }
    }

    private function createPerformanceSnapshotsTable(): void
    {
        if ($this->tableExists('performance_snapshots')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'snapshot_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'metric_key' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'metric_value' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
            ],
            'context_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'captured_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['organization_id', 'snapshot_type', 'metric_key']);
        $this->forge->createTable('performance_snapshots');
    }

    private function createSourceHealthSnapshotsTable(): void
    {
        if ($this->tableExists('source_health_snapshots')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'organization_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'source_key' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'source_label' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'total_assets' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'unmapped_assets' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'invalid_geometry_assets' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'duplicate_assets' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'captured_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['organization_id', 'source_key', 'captured_at']);
        $this->forge->createTable('source_health_snapshots');
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     */
    private function addColumnsIfMissing(string $table, array $columns): void
    {
        foreach ($columns as $name => $definition) {
            if ($this->db->fieldExists($name, $table)) {
                continue;
            }

            $this->forge->addColumn($table, [$name => $definition]);
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }
}
