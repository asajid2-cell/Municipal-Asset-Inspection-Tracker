<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\InspectionModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the access-control, attachment, and audit-log wave.
 *
 * @internal
 */
final class AccessAndAuditTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testLoginPageRendersAndAcceptsValidCredentials(): void
    {
        $page = $this->get('/login');

        $page->assertStatus(200);
        $page->assertSee('Demo accounts');
        $page->assertSee('Password123!');

        $response = $this->post('/login', [
            'email' => 'admin@northriver.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirectTo('/');
        $response->assertSessionHas('auth_user');
    }

    public function testViewerCannotAccessAssetCreateForm(): void
    {
        $response = $this->withSession($this->authSession('viewer@northriver.local'))->get('/assets/new');

        $response->assertRedirectTo('/');
        $response->assertSessionHas('warning');
    }

    public function testAuditLogPageShowsSeededHistory(): void
    {
        $response = $this->withSession($this->authSession('manager@northriver.local'))->get('/audit-log');

        $response->assertStatus(200);
        $response->assertSee('Activity Log');
        $response->assertSee('Opened maintenance request for hydrant pressure issue.');
        $response->assertSee('Marked Aspen Grove Climber as out of service after failed inspection.');
    }

    public function testInspectionUploadStoresAttachmentAndShowsItOnAssetPage(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpFile, "%PDF-1.4\nInspection evidence\n");

        try {
            $files = [
                'attachments' => [
                    'name' => ['bench-report.pdf'],
                    'type' => ['application/pdf'],
                    'tmp_name' => [$tmpFile],
                    'error' => [UPLOAD_ERR_OK],
                    'size' => [filesize($tmpFile)],
                ],
            ];

            $response = $this->postWithFiles('/assets/' . $assetId . '/inspections', [
                'inspector_id' => $inspectorId,
                'inspected_at' => '2026-04-12T08:45',
                'condition_rating' => '4',
                'result_status' => 'Active',
                'notes' => 'Bench passed the post-repair inspection.',
            ], $files, 'inspector@northriver.local');

            $response->assertRedirectTo('/assets/' . $assetId);

            $inspection = (new InspectionModel())
                ->where('asset_id', $assetId)
                ->orderBy('inspected_at', 'DESC')
                ->first();

            $this->assertNotNull($inspection);

            $this->seeInDatabase('attachments', [
                'inspection_id' => $inspection['id'],
                'original_name' => 'bench-report.pdf',
                'uploaded_by' => $inspectorId,
            ]);

            $detailResponse = $this->withSession($this->authSession('inspector@northriver.local'))->get('/assets/' . $assetId);

            $detailResponse->assertStatus(200);
            $detailResponse->assertSee('bench-report.pdf');
        } finally {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testUnsupportedInspectionAttachmentIsRejected(): void
    {
        $assetId = $this->lookupId('assets', 'asset_code', 'PARK-BENCH-001');
        $inspectorId = $this->lookupId('users', 'email', 'inspector@northriver.local');
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpFile, "not an allowed attachment");

        try {
            $files = [
                'attachments' => [
                    'name' => ['bad-file.exe'],
                    'type' => ['application/octet-stream'],
                    'tmp_name' => [$tmpFile],
                    'error' => [UPLOAD_ERR_OK],
                    'size' => [filesize($tmpFile)],
                ],
            ];

            $response = $this->postWithFiles('/assets/' . $assetId . '/inspections', [
                'inspector_id' => $inspectorId,
                'inspected_at' => '2026-04-12T08:45',
                'condition_rating' => '4',
                'result_status' => 'Active',
                'notes' => 'Attachment validation should block this file.',
            ], $files, 'inspector@northriver.local');

            $response->assertRedirectTo('/assets/' . $assetId . '/inspections/new');
            $response->assertSessionHas('errors');
            $this->dontSeeInDatabase('attachments', [
                'original_name' => 'bad-file.exe',
            ]);
        } finally {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }
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
