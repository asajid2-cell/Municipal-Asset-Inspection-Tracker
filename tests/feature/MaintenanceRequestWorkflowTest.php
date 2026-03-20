<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\InspectionModel;
use App\Models\MaintenanceRequestModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the maintenance request workflow layered onto inspections and assets.
 *
 * @internal
 */
final class MaintenanceRequestWorkflowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testAssetDetailShowsMaintenanceHistory(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'LIB-PC-022');

        $response = $this->withSession($this->authSession())->get('/assets/' . $assetId);

        $response->assertStatus(200);
        $response->assertSee('Maintenance requests');
        $response->assertSee('Replace children wing keyboard');
        $response->assertSee('Resolved');
        $response->assertSee('New request');
    }

    public function testCanCreateManualMaintenanceRequestFromAsset(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'ROAD-LIGHT-045');
        $openedBy = $this->lookupId('users', 'email', 'ops@northriver.local');
        $departmentId = $this->lookupId('departments', 'code', 'ROADS');

        $response = $this->withSession($this->authSession())->post('/assets/' . $assetId . '/maintenance-requests', [
            'opened_by' => $openedBy,
            'assigned_department_id' => $departmentId,
            'title' => 'Replace cracked light housing',
            'description' => 'Lens is cracked and needs replacement before the next weather event.',
            'priority' => 'High',
            'status' => 'Open',
            'due_at' => '2026-04-02T16:00',
            'resolution_notes' => '',
        ]);

        $response->assertRedirectTo('/assets/' . $assetId);

        $this->seeInDatabase('maintenance_requests', [
            'asset_id' => $assetId,
            'title' => 'Replace cracked light housing',
            'priority' => 'High',
            'status' => 'Open',
            'due_at' => '2026-04-02 16:00:00',
        ]);
        $this->seeInDatabase('activity_logs', [
            'entity_type' => 'maintenance_request',
            'action' => 'created',
            'summary' => 'Opened maintenance request for ROAD-LIGHT-045.',
        ]);
    }

    public function testFailedInspectionCanCreateLinkedMaintenanceRequest(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');
        $departmentId = $this->lookupId('departments', 'code', 'PARKS');

        $response = $this->withSession($this->authSession('inspector@northriver.local'))->post('/assets/' . $assetId . '/inspections', [
            'inspector_id' => $inspectorId,
            'inspected_at' => '2026-04-10T09:30',
            'condition_rating' => '2',
            'result_status' => 'Needs Repair',
            'notes' => 'Seat support bolts need replacement.',
            'create_request' => '1',
            'request_title' => 'Replace damaged bench supports',
            'request_priority' => 'High',
            'request_due_at' => '',
            'request_description' => '',
            'request_assigned_department_id' => $departmentId,
        ]);

        $response->assertRedirectTo('/assets/' . $assetId);

        $inspection = (new InspectionModel())
            ->where('asset_id', $assetId)
            ->orderBy('inspected_at', 'DESC')
            ->first();

        $this->assertNotNull($inspection);

        $request = (new MaintenanceRequestModel())
            ->where('asset_id', $assetId)
            ->where('title', 'Replace damaged bench supports')
            ->first();

        $this->assertNotNull($request);
        $this->assertSame((int) $inspection['id'], (int) $request['inspection_id']);
        $this->assertSame('Open', $request['status']);
        $this->assertSame('2026-04-17 09:30:00', $request['due_at']);
        $this->assertSame('Seat support bolts need replacement.', $request['description']);

        $this->seeInDatabase('activity_logs', [
            'entity_type' => 'maintenance_request',
            'entity_id' => $request['id'],
            'action' => 'created',
        ]);
    }

    public function testMaintenanceQueueSupportsFilters(): void
    {
        $departmentId = $this->lookupId('departments', 'code', 'ROADS');

        $response = $this->withSession($this->authSession())->get('/maintenance-requests?status=Open&priority=High&assigned_department_id=' . $departmentId . '&q=hydrant&active_only=1');

        $response->assertStatus(200);
        $response->assertSee('Maintenance Requests');
        $response->assertSee('Investigate hydrant pressure issue');
        $response->assertDontSee('Replace damaged ladder rail');
        $response->assertDontSee('Replace children wing keyboard');
    }

    public function testCanResolveMaintenanceRequest(): void
    {
        $requestId = $this->lookupId('maintenance_requests', 'title', 'Investigate hydrant pressure issue');
        $existingRequest = (new MaintenanceRequestModel())->findDetailedRequest($requestId);

        $this->assertNotNull($existingRequest);

        $response = $this->withSession($this->authSession())->post('/maintenance-requests/' . $requestId, [
            'opened_by' => $existingRequest['opened_by'],
            'assigned_department_id' => $existingRequest['assigned_department_id'],
            'title' => $existingRequest['title'],
            'description' => $existingRequest['description'],
            'priority' => $existingRequest['priority'],
            'status' => 'Resolved',
            'due_at' => date('Y-m-d\TH:i', strtotime((string) $existingRequest['due_at'])),
            'resolution_notes' => 'Valve serviced and hydrant pressure passed the follow-up test.',
        ]);

        $response->assertRedirectTo('/maintenance-requests');

        $updatedRequest = (new MaintenanceRequestModel())->find($requestId);

        $this->assertNotNull($updatedRequest);
        $this->assertSame('Resolved', $updatedRequest['status']);
        $this->assertSame('Valve serviced and hydrant pressure passed the follow-up test.', $updatedRequest['resolution_notes']);
        $this->assertNotNull($updatedRequest['resolved_at']);

        $this->seeInDatabase('activity_logs', [
            'entity_type' => 'maintenance_request',
            'entity_id' => $requestId,
            'action' => 'resolved',
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
