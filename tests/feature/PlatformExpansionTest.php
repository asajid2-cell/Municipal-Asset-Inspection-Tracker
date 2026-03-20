<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the advanced reporting, planning, export, mobile, and workflow expansion pass.
 *
 * @internal
 */
final class PlatformExpansionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testReportsAdminAndDigitalTwinPagesLoad(): void
    {
        $reports = $this->withSession($this->authSession())->get('/reports');
        $reports->assertStatus(200);
        $reports->assertSee('Executive Reports');
        $reports->assertSee('Top capital candidates');

        $admin = $this->withSession($this->authSession())->get('/admin');
        $admin->assertStatus(200);
        $admin->assertSee('Admin Console');
        $admin->assertSee('Workflow rules');

        $twin = $this->withSession($this->authSession())->get('/digital-twin');
        $twin->assertStatus(200);
        $twin->assertSee('Digital Twin');
        $twin->assertSee('Linear network context');
    }

    public function testCanGenerateCapitalScenarioAndExportJob(): void
    {
        $capital = $this->withSession($this->authSession())->post('/capital-planning', [
            'name' => 'High risk renewal set',
            'planning_horizon_years' => '15',
            'annual_budget' => '300000',
            'strategy_notes' => 'Prioritize high-risk renewals.',
        ]);

        $capital->assertRedirectTo('/capital-planning');
        $this->seeInDatabase('capital_plan_scenarios', [
            'name' => 'High risk renewal set',
            'planning_horizon_years' => 15,
        ]);

        $export = $this->withSession($this->authSession())->post('/exports/assets', [
            'name' => 'Needs repair export',
            'status' => 'Needs Repair',
            'sort' => 'asset_code_asc',
        ]);

        $export->assertRedirectTo('/exports');
        $this->seeInDatabase('export_jobs', [
            'name' => 'Needs repair export',
            'status' => 'Completed',
        ]);
    }

    public function testCanPrepareOfflinePacketAndRecordConflict(): void
    {
        $assignedUserId = $this->lookupId('users', 'email', 'fieldtech@northriver.local');

        $packetResponse = $this->withSession($this->authSession())->post('/mobile-ops/packets', [
            'packet_name' => 'Field packet A',
            'assigned_user_id' => $assignedUserId,
        ]);

        $packetResponse->assertRedirectTo('/mobile-ops');
        $this->seeInDatabase('offline_sync_packets', [
            'packet_name' => 'Field packet A',
            'status' => 'Prepared',
        ]);

        $packetId = $this->lookupId('offline_sync_packets', 'packet_name', 'Field packet A');
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');

        $conflictResponse = $this->withSession($this->authSession())->post('/mobile-ops/conflicts', [
            'packet_id' => $packetId,
            'asset_id' => $assetId,
            'conflict_type' => 'inspection_status_conflict',
            'local_payload_json' => '{"result_status":"Needs Repair"}',
            'server_payload_json' => '{"result_status":"Active"}',
        ]);

        $conflictResponse->assertRedirectTo('/mobile-ops');
        $this->seeInDatabase('offline_sync_conflicts', [
            'packet_id' => $packetId,
            'asset_id' => $assetId,
            'conflict_type' => 'inspection_status_conflict',
        ]);
    }

    public function testInspectionWorkflowAutoCreatesFollowUpAndNotification(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');

        $response = $this->withSession($this->authSession('inspector@northriver.local'))->post('/assets/' . $assetId . '/inspections', [
            'inspector_id' => $inspectorId,
            'inspected_at' => '2026-04-15T09:00',
            'condition_rating' => '1',
            'result_status' => 'Needs Repair',
            'notes' => 'Bench slat failed during field inspection.',
        ]);

        $response->assertRedirectTo('/assets/' . $assetId);

        $this->seeInDatabase('maintenance_requests', [
            'asset_id' => $assetId,
            'title' => 'Automated follow-up for PARK-BENCH-001',
            'status' => 'Open',
        ]);
        $this->seeInDatabase('notification_deliveries', [
            'template_key' => 'inspection_followup',
            'context_type' => 'inspection',
        ]);
        $this->seeInDatabase('asset_versions', [
            'asset_id' => $assetId,
            'version_type' => 'inspection_updated',
        ]);
    }

    private function lookupId(string $table, string $column, string $value): int
    {
        $row = $this->db->table($table)
            ->select('id')
            ->where($column, $value)
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);

        return (int) $row['id'];
    }
}
