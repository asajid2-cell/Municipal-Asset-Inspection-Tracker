<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Covers the dashboard and operational filtering phase.
 *
 * @internal
 */
final class DashboardAndFilterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testDashboardShowsOperationalCardsAndOverdueAsset(): void
    {
        $response = $this->withSession($this->authSession())->get('/');

        $response->assertStatus(200);
        $response->assertSee('Overdue inspections');
        $response->assertSee('Open maintenance requests');
        $response->assertSee('Status breakdown');
        $response->assertSee('FAC-HVAC-003');
    }

    public function testAssetIndexSupportsCombinedFilters(): void
    {
        $departmentId = $this->lookupId('departments', 'code', 'FACILITIES');

        $response = $this->withSession($this->authSession())->get('/assets?department_id=' . $departmentId . '&status=Needs%20Inspection&overdue=1&sort=next_due_asc');

        $response->assertStatus(200);
        $response->assertSee('FAC-HVAC-003');
        $response->assertDontSee('ROAD-LIGHT-045');
        $response->assertDontSee('UTIL-HYDRANT-014');
        $response->assertSee('Mapped assets');
        $response->assertSee('Top asset classes in this result set');
    }

    public function testAssetCsvExportRespectsCurrentFilters(): void
    {
        $response = $this->withSession($this->authSession())->get('/assets?status=Needs%20Repair&export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $response->response()->getBody();

        $this->assertStringContainsString('UTIL-HYDRANT-014', $body);
        $this->assertStringContainsString('Hydrant 14 - Oakview', $body);
        $this->assertStringNotContainsString('PARK-BENCH-001', $body);
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
