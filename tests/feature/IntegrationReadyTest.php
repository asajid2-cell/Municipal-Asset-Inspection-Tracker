<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers CSV import, captured notifications, and authenticated JSON API reads.
 *
 * @internal
 */
final class IntegrationReadyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testCanImportAssetsFromCsvAndReportRowIssues(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'asset-import');
        file_put_contents($tmpFile, implode("\n", [
            'asset_code,name,department_code,category_name,location_text,status,installed_on,latitude,longitude,notes',
            'PARK-BENCH-120,Trail Rest Bench 120,PARKS,Park Bench,Riverfront Park west path,Active,2026-03-20,53.5471,-113.4889,Imported from csv',
            'BAD-ASSET-001,Broken Row,NOPE,Park Bench,Unknown location,Active,,,,Missing department',
        ]));

        try {
            $files = [
                'asset_import' => [
                    'name' => 'assets.csv',
                    'type' => 'text/csv',
                    'tmp_name' => $tmpFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmpFile),
                ],
            ];

            $response = $this->postWithFiles('/assets/import', [], $files);

            $response->assertRedirectTo('/assets');
            $response->assertSessionHas('importReport');

            $this->seeInDatabase('assets', [
                'asset_code' => 'PARK-BENCH-120',
                'name' => 'Trail Rest Bench 120',
            ]);
            $this->dontSeeInDatabase('assets', [
                'asset_code' => 'BAD-ASSET-001',
            ]);
        } finally {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testFailedInspectionCapturesNotificationDelivery(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'UTIL-HYDRANT-014');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');

        $response = $this->withSession($this->authSession('inspector@northriver.local'))->post('/assets/' . $assetId . '/inspections', [
            'inspector_id' => $inspectorId,
            'inspected_at' => '2026-04-13T10:15',
            'condition_rating' => '2',
            'result_status' => 'Needs Repair',
            'notes' => 'Hydrant still needs valve service.',
        ]);

        $response->assertRedirectTo('/assets/' . $assetId);

        $this->seeInDatabase('notification_deliveries', [
            'recipient_email' => 'roads@northriver.local',
            'context_type' => 'inspection',
            'status' => 'Captured',
        ]);
    }

    public function testCanCaptureOverdueInspectionReminders(): void
    {
        $response = $this->withSession($this->authSession())->post('/notifications/overdue-reminders');

        $response->assertRedirectTo('/notifications');

        $this->seeInDatabase('notification_deliveries', [
            'recipient_email' => 'facilities@northriver.local',
            'context_type' => 'asset',
            'status' => 'Captured',
        ]);
    }

    public function testApiRequiresAuthentication(): void
    {
        $response = $this->get('/api/assets');

        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('Authentication required', $response->response()->getBody());
    }

    public function testApiReturnsStableAssetAndInspectionJson(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'UTIL-HYDRANT-014');

        $listResponse = $this->withSession($this->authSession('viewer@northriver.local'))->get('/api/assets?status=Needs%20Repair&per_page=1');

        $listResponse->assertStatus(200);
        $listResponse->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $listPayload = json_decode($listResponse->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('UTIL-HYDRANT-014', $listPayload['data'][0]['asset_code']);
        $this->assertSame(1, $listPayload['meta']['per_page']);
        $this->assertSame(1, $listPayload['meta']['returned_count']);
        $this->assertGreaterThanOrEqual(1, $listPayload['meta']['total_count']);
        $this->assertGreaterThanOrEqual(1, $listPayload['meta']['page_count']);

        $detailResponse = $this->withSession($this->authSession('viewer@northriver.local'))->get('/api/assets/' . $assetId);

        $detailResponse->assertStatus(200);
        $detailPayload = json_decode($detailResponse->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('ROADS', $detailPayload['data']['department_code']);
        $this->assertSame(53.5463, $detailPayload['data']['latitude']);

        $historyResponse = $this->withSession($this->authSession('viewer@northriver.local'))->get('/api/assets/' . $assetId . '/inspections');

        $historyResponse->assertStatus(200);
        $historyPayload = json_decode($historyResponse->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('UTIL-HYDRANT-014', $historyPayload['meta']['asset_code']);
        $this->assertSame('Riley Chen', $historyPayload['data'][0]['inspector_name']);
    }

    public function testApiCapsRequestedPageSize(): void
    {
        $response = $this->withSession($this->authSession('viewer@northriver.local'))->get('/api/assets?per_page=5000');

        $response->assertStatus(200);
        $payload = json_decode($response->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1000, $payload['meta']['per_page']);
        $this->assertLessThanOrEqual(1000, $payload['meta']['returned_count']);
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
