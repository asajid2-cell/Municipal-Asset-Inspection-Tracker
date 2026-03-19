<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Basic smoke test for the project landing page.
 *
 * @internal
 */
final class HomePageTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHomePageShowsProjectContext(): void
    {
        $result = $this->get('/');

        $result->assertStatus(200);
        $result->assertSee('Municipal Asset');
        $result->assertSee('Current build: foundation, schema, and demo data');
    }
}
