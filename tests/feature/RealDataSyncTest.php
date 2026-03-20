<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\OpenDataSyncService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the real-data sync workflow without depending on live network calls.
 *
 * @internal
 */
final class RealDataSyncTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

    public function testSyncServiceImportsThenUpdatesThenRestoresSamePublicRecord(): void
    {
        $adminId = $this->lookupId('users', 'email', 'admin@northriver.local');
        $service = new OpenDataSyncService();

        $firstReport = $service->importRecords('edmonton-benches', [[
            'asset_id' => 'BENCH-1001',
            'type' => 'bench',
            'surface_material' => 'wood',
            'structure_material' => 'metal',
            'owner' => 'city of edmonton',
            'maintainer' => 'parks',
            'geom' => [
                'latitude' => '53.5461000',
                'longitude' => '-113.4937000',
            ],
        ]], $adminId, 1);

        $this->assertSame(1, $firstReport['imported_count']);
        $this->assertSame(0, $firstReport['updated_count']);

        $assetRow = $this->db->table('assets')
            ->where('asset_code', 'EDM-BENCH-BENCH-1001')
            ->get()
            ->getRowArray();

        $this->assertNotNull($assetRow);

        $secondReport = $service->importRecords('edmonton-benches', [[
            'asset_id' => 'BENCH-1001',
            'type' => 'bench',
            'surface_material' => 'wood',
            'structure_material' => 'metal',
            'owner' => 'city of edmonton',
            'maintainer' => 'parks',
            'geom' => [
                'latitude' => '53.5461000',
                'longitude' => '-113.4937000',
            ],
        ]], $adminId, 1);

        $this->assertSame(0, $secondReport['imported_count']);
        $this->assertSame(0, $secondReport['updated_count']);
        $this->assertSame(1, $secondReport['unchanged_count']);

        $thirdReport = $service->importRecords('edmonton-benches', [[
            'asset_id' => 'BENCH-1001',
            'type' => 'bench',
            'surface_material' => 'recycled plastic',
            'structure_material' => 'steel',
            'owner' => 'city of edmonton',
            'maintainer' => 'parks',
            'geom' => [
                'latitude' => '53.5461000',
                'longitude' => '-113.4937000',
            ],
        ]], $adminId, 1);

        $this->assertSame(0, $thirdReport['imported_count']);
        $this->assertSame(1, $thirdReport['updated_count']);
        $this->assertSame(1, $this->db->table('assets')->where('asset_code', 'EDM-BENCH-BENCH-1001')->countAllResults());

        $this->db->table('assets')
            ->where('id', $assetRow['id'])
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        $fourthReport = $service->importRecords('edmonton-benches', [[
            'asset_id' => 'BENCH-1001',
            'type' => 'bench',
            'surface_material' => 'recycled plastic',
            'structure_material' => 'steel',
            'owner' => 'city of edmonton',
            'maintainer' => 'parks',
            'geom' => [
                'latitude' => '53.5461000',
                'longitude' => '-113.4937000',
            ],
        ]], $adminId, 1);

        $this->assertSame(1, $fourthReport['restored_count']);

        $restoredRow = $this->db->table('assets')
            ->where('id', $assetRow['id'])
            ->get()
            ->getRowArray();

        $this->assertSame(null, $restoredRow['deleted_at']);
        $this->assertStringContainsString('Recycled Plastic', (string) $restoredRow['notes']);
    }

    public function testInventoryScreenCanTriggerOpenDataSync(): void
    {
        $service = new OpenDataSyncService(
            static fn (string $endpoint, array $query, array $headers): array => [[
                'hydrant_number' => 'H-2201',
                'dome_colour' => 'red',
                'flow_rate' => '125 L/s',
                'installation_year' => '2016',
                'latitude' => '53.5401000',
                'longitude' => '-113.5002000',
                'nearest_address' => '9914 101 Street',
                'owner' => 'city of edmonton',
                'life_cycle_indicator' => 'active',
            ]]
        );

        Services::injectMock('openDataSyncService', $service);

        $response = $this->withSession($this->authSession())->post('/assets/open-data-sync', [
            'source_key' => 'edmonton-hydrants',
            'limit' => '1',
        ]);

        $response->assertRedirectTo('/assets');
        $response->assertSessionHas('syncReport');

        $this->seeInDatabase('assets', [
            'asset_code' => 'EDM-HYDRANT-H-2201',
            'source_dataset' => 'x4n2-2ke2',
        ]);
    }

    public function testSyncServiceCanPageThroughFullDataset(): void
    {
        $adminId = $this->lookupId('users', 'email', 'admin@northriver.local');
        $service = new OpenDataSyncService(
            static function (string $endpoint, array $query, array $headers): array {
                $offset = (int) ($query['$offset'] ?? 0);
                $limit = (int) ($query['$limit'] ?? 1000);
                $allRows = [
                    [
                        'hydrant_number' => 'H-3001',
                        'dome_colour' => 'red',
                        'flow_rate' => '110 L/s',
                        'installation_year' => '2015',
                        'latitude' => '53.5411000',
                        'longitude' => '-113.5012000',
                        'nearest_address' => '10001 Jasper Avenue',
                        'owner' => 'city of edmonton',
                        'life_cycle_indicator' => 'active',
                    ],
                    [
                        'hydrant_number' => 'H-3002',
                        'dome_colour' => 'yellow',
                        'flow_rate' => '90 L/s',
                        'installation_year' => '2014',
                        'latitude' => '53.5421000',
                        'longitude' => '-113.5022000',
                        'nearest_address' => '10011 Jasper Avenue',
                        'owner' => 'city of edmonton',
                        'life_cycle_indicator' => 'active',
                    ],
                    [
                        'hydrant_number' => 'H-3003',
                        'dome_colour' => 'green',
                        'flow_rate' => '80 L/s',
                        'installation_year' => '2013',
                        'latitude' => '53.5431000',
                        'longitude' => '-113.5032000',
                        'nearest_address' => '10021 Jasper Avenue',
                        'owner' => 'city of edmonton',
                        'life_cycle_indicator' => 'active',
                    ],
                ];

                return array_slice($allRows, $offset, $limit);
            }
        );

        $report = $service->syncSource('edmonton-hydrants', 0, $adminId, true);

        $this->assertSame('all', $report['requested_limit']);
        $this->assertSame(3, $report['fetched_count']);
        $this->assertSame(3, $report['imported_count']);
        $this->seeInDatabase('assets', [
            'asset_code' => 'EDM-HYDRANT-H-3003',
        ]);
    }

    public function testSyncServiceReportsBatchProgress(): void
    {
        $adminId = $this->lookupId('users', 'email', 'admin@northriver.local');
        $progressSnapshots = [];
        $config = new \Config\OpenData();
        $config->sources['edmonton-hydrants']['default_batch_size'] = 1;
        $service = new OpenDataSyncService(
            static function (string $endpoint, array $query, array $headers): array {
                $offset = (int) ($query['$offset'] ?? 0);

                return match ($offset) {
                    0 => [[
                        'hydrant_number' => 'H-4001',
                        'dome_colour' => 'red',
                        'flow_rate' => '70 L/s',
                        'installation_year' => '2011',
                        'latitude' => '53.5411000',
                        'longitude' => '-113.5012000',
                        'nearest_address' => '10101 100 Street',
                        'owner' => 'city of edmonton',
                        'life_cycle_indicator' => 'active',
                    ]],
                    1 => [[
                        'hydrant_number' => 'H-4002',
                        'dome_colour' => 'yellow',
                        'flow_rate' => '75 L/s',
                        'installation_year' => '2012',
                        'latitude' => '53.5421000',
                        'longitude' => '-113.5022000',
                        'nearest_address' => '10111 100 Street',
                        'owner' => 'city of edmonton',
                        'life_cycle_indicator' => 'active',
                    ]],
                    default => [],
                };
            },
            $config
        );

        $report = $service->syncSource(
            'edmonton-hydrants',
            0,
            $adminId,
            true,
            static function (array $progress) use (&$progressSnapshots): void {
                $progressSnapshots[] = $progress;
            }
        );

        $this->assertCount(2, $progressSnapshots);
        $this->assertSame(1, $progressSnapshots[0]['fetched_count']);
        $this->assertSame(2, $progressSnapshots[1]['fetched_count']);
        $this->assertSame(2, $report['imported_count']);
    }

    public function testSyncServiceImportsPolygonSourceWithSyntheticIdentifierAndGeometry(): void
    {
        $adminId = $this->lookupId('users', 'email', 'admin@northriver.local');
        $service = new OpenDataSyncService();
        $record = [
            'geometry_multipolygon' => [
                'type' => 'MultiPolygon',
                'coordinates' => [
                    [[
                        [-113.50, 53.54],
                        [-113.49, 53.54],
                        [-113.49, 53.55],
                        [-113.50, 53.55],
                        [-113.50, 53.54],
                    ]],
                ],
            ],
            'type' => 'storm',
            'latitude' => '53.5450000',
            'longitude' => '-113.4950000',
            'year' => '2015',
            'ward' => 'O-day\'min',
            'neighbourhood_name' => 'Downtown',
            'street_avenue' => '104 Avenue NW',
            'facility_type' => 'Stormwater Pond',
            'storm_type' => 'Storm',
            'facility_owner' => 'EPCOR',
            'description' => 'Demo stormwater feature',
        ];

        $firstReport = $service->importRecords('edmonton-stormwater-facilities', [$record], $adminId, 1);
        $secondReport = $service->importRecords('edmonton-stormwater-facilities', [$record], $adminId, 1);

        $this->assertSame(1, $firstReport['imported_count']);
        $this->assertSame(1, $secondReport['unchanged_count']);

        $asset = $this->db->table('assets')
            ->where('source_dataset', '72ee-mmkx')
            ->get()
            ->getRowArray();

        $this->assertNotNull($asset);
        $this->assertStringStartsWith('SWF-', (string) $asset['source_record_id']);
        $this->assertSame('MultiPolygon', $asset['source_geometry_type']);
        $this->assertStringContainsString('Stormwater Pond', (string) $asset['notes']);
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
