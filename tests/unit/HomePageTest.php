<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthSessionTrait;

/**
 * Basic smoke test for the project landing page.
 *
 * @internal
 */
final class HomePageTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthSessionTrait;

    protected $DBGroup = 'default';
    protected $namespace = null;
    protected $seed = DatabaseSeeder::class;
    protected $refresh = true;

    public function testHomePageShowsProjectContext(): void
    {
        $result = $this->withSession($this->authSession())->get('/');

        $result->assertStatus(200);
        $result->assertSee('Municipal Asset');
        $result->assertSee('Overdue inspections');
        $result->assertSee('Open maintenance requests');
    }
}
