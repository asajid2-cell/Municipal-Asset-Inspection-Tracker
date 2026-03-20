<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\AssetModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the main asset management flow introduced in the MVP phase.
 *
 * @internal
 */
final class AssetManagementTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testAssetIndexShowsSeedDataAndSearchFiltersResults(): void
    {
        $listResponse = $this->withSession($this->authSession())->get('/assets');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('Asset Inventory');
        $listResponse->assertSee('FAC-HVAC-003');

        $searchResponse = $this->withSession($this->authSession())->get('/assets?q=HVAC');

        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('FAC-HVAC-003');
        $searchResponse->assertDontSee('PARK-BENCH-001');
    }

    public function testCanOpenFullAndMapInventoryViews(): void
    {
        $fullResponse = $this->withSession($this->authSession())->get('/assets/full?status=Needs%20Inspection');

        $fullResponse->assertStatus(200);
        $fullResponse->assertSee('Full Asset Inventory');
        $fullResponse->assertSee('FAC-HVAC-003');
        $fullResponse->assertDontSee('PARK-BENCH-001');

        $mapResponse = $this->withSession($this->authSession())->get('/assets/map?category_id=' . $this->lookupId('asset_categories', 'name', 'Park Bench'));

        $mapResponse->assertStatus(200);
        $mapResponse->assertSee('Map Inventory');
        $mapResponse->assertSee('asset-map');
    }

    public function testFullViewHonoursLargeTablePageSize(): void
    {
        $departmentId = $this->lookupId('departments', 'code', 'PARKS');
        $categoryId = $this->lookupId('asset_categories', 'name', 'Park Bench');

        for ($index = 0; $index < 130; $index++) {
            $this->db->table('assets')->insert([
                'asset_code' => sprintf('PARK-BULK-%03d', $index),
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'name' => 'Bulk bench ' . $index,
                'status' => 'Active',
                'location_text' => 'Bulk import area',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $response = $this->withSession($this->authSession())->get('/assets/full?per_page=100');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            '<strong>100</strong> rows on this page.',
            $response->response()->getBody()
        );
    }

    public function testCanCreateAsset(): void
    {
        $departmentId = $this->lookupId('departments', 'code', 'PARKS');
        $categoryId   = $this->lookupId('asset_categories', 'name', 'Park Bench');

        $response = $this->withSession($this->authSession())->post('/assets', [
            'asset_code' => 'PARK-BENCH-099',
            'department_id' => $departmentId,
            'category_id' => $categoryId,
            'name' => 'Trail Rest Bench 99',
            'status' => 'Active',
            'location_text' => 'Riverfront Park north lookout',
            'installed_on' => '2026-03-18',
            'latitude' => '53.5500000',
            'longitude' => '-113.4900000',
            'notes' => 'Installed as part of the spring refresh.',
        ]);

        $asset = (new AssetModel())->where('asset_code', 'PARK-BENCH-099')->first();

        $this->assertNotNull($asset);
        $response->assertRedirectTo('/assets/' . $asset['id']);
        $this->seeInDatabase('assets', [
            'asset_code' => 'PARK-BENCH-099',
            'name' => 'Trail Rest Bench 99',
        ]);
    }

    public function testCreateValidationFailureRedirectsBackToForm(): void
    {
        $response = $this->withSession($this->authSession())->post('/assets', [
            'asset_code' => '',
            'department_id' => '',
            'category_id' => '',
            'name' => '',
            'status' => '',
            'location_text' => '',
        ]);

        $response->assertRedirectTo('/assets/new');
        $response->assertSessionHas('errors');
    }

    public function testArchiveSoftDeletesAsset(): void
    {
        $assetId  = $this->lookupId('assets', 'asset_code', 'ROAD-LIGHT-045');
        $response = $this->withSession($this->authSession())->post('/assets/' . $assetId . '/archive');

        $response->assertRedirectTo('/assets');

        $asset = (new AssetModel())->withDeleted()->find($assetId);

        $this->assertNotNull($asset);
        $this->assertNotNull($asset['deleted_at']);
    }

    public function testMapApiReturnsCoordinateFilteredAssets(): void
    {
        $categoryId = $this->lookupId('asset_categories', 'name', 'Park Bench');

        $response = $this->withSession($this->authSession('viewer@northriver.local'))->get(
            '/api/assets/map?category_id=' . $categoryId . '&north=53.6&south=53.5&east=-113.3&west=-113.6'
        );

        $response->assertStatus(200);
        $payload = json_decode($response->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $payload['meta']['mapped_count']);
        $this->assertSame('PARK-BENCH-001', $payload['data'][0]['asset_code']);
    }

    public function testInventoryCanFilterBySourceDatasetAndGeometryFamily(): void
    {
        $this->db->table('assets')->insert([
            'asset_code' => 'EDM-PARK-DEMO',
            'department_id' => $this->lookupId('departments', 'code', 'PARKS'),
            'category_id' => $this->lookupId('asset_categories', 'name', 'Park'),
            'name' => 'Demo Park Polygon',
            'status' => 'Active',
            'location_text' => 'Downtown',
            'latitude' => '53.5450000',
            'longitude' => '-113.4950000',
            'source_system' => 'City of Edmonton Open Data',
            'source_dataset' => 'gdd9-eqv9',
            'source_record_id' => '3157',
            'source_url' => 'https://data.edmonton.ca/resource/gdd9-eqv9.json',
            'source_geometry_type' => 'MultiPolygon',
            'source_geometry' => '{"type":"MultiPolygon","coordinates":[[[[-113.50,53.54],[-113.49,53.54],[-113.49,53.55],[-113.50,53.55],[-113.50,53.54]]]]}',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withSession($this->authSession())->get('/assets/full?source_dataset=gdd9-eqv9&geometry_family=polygon');

        $response->assertStatus(200);
        $response->assertSee('Demo Park Polygon');
        $response->assertDontSee('FAC-HVAC-003');
    }

    public function testMapApiReportsTruncationWhenViewportExceedsLimit(): void
    {
        $departmentId = $this->lookupId('departments', 'code', 'PARKS');
        $categoryId = $this->lookupId('asset_categories', 'name', 'Park Bench');
        $rows = [];

        for ($index = 0; $index < AssetModel::MAP_RENDER_LIMIT + 5; $index++) {
            $rows[] = [
                'asset_code' => sprintf('MAP-LIMIT-%04d', $index),
                'department_id' => $departmentId,
                'category_id' => $categoryId,
                'name' => 'Map limit asset ' . $index,
                'status' => 'Active',
                'location_text' => 'Viewport stress test',
                'latitude' => '53.5500000',
                'longitude' => '-113.4900000',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('assets')->insertBatch($rows);

        $response = $this->withSession($this->authSession('viewer@northriver.local'))->get(
            '/api/assets/map?north=53.6&south=53.5&east=-113.3&west=-113.6'
        );

        $response->assertStatus(200);
        $payload = json_decode($response->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['meta']['truncated']);
        $this->assertSame(AssetModel::MAP_RENDER_LIMIT, $payload['meta']['mapped_count']);
        $this->assertGreaterThan($payload['meta']['mapped_count'], $payload['meta']['viewport_total_count']);
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
