<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\AssetModel;
use App\Models\InspectionModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the inspection workflow added after the asset inventory MVP.
 *
 * @internal
 */
final class InspectionWorkflowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testAssetDetailShowsInspectionHistory(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'UTIL-HYDRANT-014');

        $response = $this->withSession($this->authSession())->get('/assets/' . $assetId);

        $response->assertStatus(200);
        $response->assertSee('Inspection history');
        $response->assertSee('Riley Chen');
        $response->assertSee('Needs Repair');
        $response->assertSee('Log inspection');
    }

    public function testCanLogInspectionAndUpdateAsset(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');

        $response = $this->withSession($this->authSession('inspector@northriver.local'))->post('/assets/' . $assetId . '/inspections', [
            'inspector_id' => $inspectorId,
            'inspected_at' => '2026-04-10T09:30',
            'condition_rating' => '2',
            'result_status' => 'Needs Repair',
            'notes' => 'Seat support bolts need replacement.',
        ]);

        $response->assertRedirectTo('/assets/' . $assetId);

        $inspection = (new InspectionModel())
            ->where('asset_id', $assetId)
            ->orderBy('inspected_at', 'DESC')
            ->first();

        $this->assertNotNull($inspection);
        $this->assertSame('Needs Repair', $inspection['result_status']);

        $expectedNextDueAt = (new DateTimeImmutable('2026-04-10 09:30:00'))
            ->modify('+365 days')
            ->format('Y-m-d H:i:s');

        $asset = (new AssetModel())->find($assetId);

        $this->assertNotNull($asset);
        $this->assertSame('Needs Repair', $asset['status']);
        $this->assertSame('2026-04-10 09:30:00', $asset['last_inspected_at']);
        $this->assertSame($expectedNextDueAt, $asset['next_inspection_due_at']);

        $this->seeInDatabase('activity_logs', [
            'entity_type' => 'inspection',
            'entity_id' => $inspection['id'],
            'action' => 'created',
        ]);
        $this->seeInDatabase('activity_logs', [
            'entity_type' => 'asset',
            'entity_id' => $assetId,
            'action' => 'status_changed',
        ]);
    }

    public function testInspectionValidationFailureRedirectsBackToForm(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');

        $response = $this->withSession($this->authSession('inspector@northriver.local'))->post('/assets/' . $assetId . '/inspections', [
            'inspector_id' => '',
            'inspected_at' => '',
            'condition_rating' => '',
            'result_status' => '',
        ]);

        $response->assertRedirectTo('/assets/' . $assetId . '/inspections/new');
        $response->assertSessionHas('errors');
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
