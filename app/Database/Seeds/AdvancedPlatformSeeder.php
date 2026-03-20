<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds workflow, template, linear, and planning data for the advanced platform pass.
 */
class AdvancedPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-19 18:10:00';

        $this->db->table('workflow_rules')->insertBatch([
            [
                'organization_id' => 1,
                'name' => 'Failed inspection auto repair',
                'trigger_event' => 'inspection.logged',
                'enabled' => true,
                'match_status' => 'Needs Repair',
                'min_condition_rating' => 1,
                'create_request' => true,
                'default_priority' => 'High',
                'notification_template_key' => 'inspection_followup',
                'assign_department_id' => $this->departmentId('ROADS'),
                'due_in_days' => 7,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'name' => 'Out of service escalation',
                'trigger_event' => 'inspection.logged',
                'enabled' => true,
                'match_status' => 'Out of Service',
                'min_condition_rating' => 1,
                'create_request' => true,
                'default_priority' => 'Critical',
                'notification_template_key' => 'inspection_escalation',
                'assign_department_id' => $this->departmentId('PARKS'),
                'due_in_days' => 2,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);

        $this->db->table('notification_templates')->insertBatch([
            [
                'organization_id' => 1,
                'template_key' => 'inspection_followup',
                'channel' => 'email',
                'subject_template' => '[North River Ops] Inspection follow-up for {{asset_code}}',
                'body_template' => "A failed inspection requires follow-up.\nAsset: {{asset_code}} - {{asset_name}}\nDepartment: {{department_name}}\nResult: {{result_status}}\nInspected at: {{inspected_at}}\nNext due: {{next_due_at}}\nNotes: {{notes}}",
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'template_key' => 'inspection_escalation',
                'channel' => 'email',
                'subject_template' => '[North River Ops] Service escalation for {{asset_code}}',
                'body_template' => "An asset was marked out of service.\nAsset: {{asset_code}} - {{asset_name}}\nDepartment: {{department_name}}\nResult: {{result_status}}\nInspected at: {{inspected_at}}\nNext due: {{next_due_at}}\nNotes: {{notes}}",
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'template_key' => 'overdue_reminder',
                'channel' => 'email',
                'subject_template' => '[North River Ops] Overdue inspection reminder for {{asset_code}}',
                'body_template' => "This asset is overdue for inspection.\nAsset: {{asset_code}} - {{asset_name}}\nDepartment: {{department_name}}\nCategory: {{category_name}}\nLocation: {{location_text}}\nDue date: {{next_inspection_due_at}}\nStatus: {{status}}",
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);

        $this->db->table('capital_plan_scenarios')->insert([
            'organization_id' => 1,
            'name' => 'Base 10-year renewal plan',
            'planning_horizon_years' => 10,
            'annual_budget' => 250000.00,
            'strategy_notes' => 'Seed scenario for capital planning demonstrations.',
            'summary_json' => json_encode([
                'recommended_projects' => [
                    ['asset_code' => 'UTIL-HYDRANT-014', 'action' => 'Renew', 'estimated_cost' => 18500.00],
                    ['asset_code' => 'FAC-HVAC-003', 'action' => 'Inspect and rehabilitate', 'estimated_cost' => 52000.00],
                ],
                'priority_mix' => [
                    'Critical' => 1,
                    'High' => 1,
                ],
            ], JSON_UNESCAPED_SLASHES),
            'generated_at' => $timestamp,
            'created_by' => $this->userId('admin@northriver.local'),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->db->table('linear_assets')->insert([
            'organization_id' => 1,
            'asset_id' => $this->assetId('ROAD-LIGHT-045'),
            'corridor_name' => '107 Avenue Corridor',
            'network_type' => 'Road lighting',
            'measure_start' => 0.00,
            'measure_end' => 135.40,
            'segment_length_m' => 135.40,
            'geometry_json' => json_encode([
                'type' => 'LineString',
                'coordinates' => [
                    [-113.4831000, 53.5491000],
                    [-113.4826000, 53.5494000],
                    [-113.4819000, 53.5497000],
                ],
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function departmentId(string $code): int
    {
        $row = $this->db->table('departments')->select('id')->where('code', $code)->get()->getRowArray();

        if ($row === null) {
            throw new RuntimeException('Missing department dependency: ' . $code);
        }

        return (int) $row['id'];
    }

    private function userId(string $email): int
    {
        $row = $this->db->table('users')->select('id')->where('email', $email)->get()->getRowArray();

        if ($row === null) {
            throw new RuntimeException('Missing user dependency: ' . $email);
        }

        return (int) $row['id'];
    }

    private function assetId(string $assetCode): int
    {
        $row = $this->db->table('assets')->select('id')->where('asset_code', $assetCode)->get()->getRowArray();

        if ($row === null) {
            throw new RuntimeException('Missing asset dependency: ' . $assetCode);
        }

        return (int) $row['id'];
    }
}
