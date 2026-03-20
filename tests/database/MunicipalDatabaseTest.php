<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifies that the application's real seed data and relationships load correctly.
 *
 * @internal
 */
final class MunicipalDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testDatabaseSeederCreatesExpectedCoreData(): void
    {
        $assetCount = $this->db->table('assets')->countAllResults();

        $this->assertSame(6, $assetCount);
        $this->seeNumRecords(4, 'departments', []);
        $this->seeNumRecords(7, 'asset_categories', []);
        $this->seeInDatabase('assets', [
            'asset_code' => 'FAC-HVAC-003',
            'status'     => 'Needs Inspection',
        ]);
    }

    public function testFailedInspectionScenarioCreatesMaintenanceFollowUp(): void
    {
        $failedInspection = $this->db->table('inspections')
            ->where('result_status', 'Out of Service')
            ->countAllResults();

        $this->assertSame(1, $failedInspection);
        $this->seeInDatabase('maintenance_requests', [
            'title'  => 'Replace damaged ladder rail',
            'status' => 'In Progress',
        ]);
    }
}
