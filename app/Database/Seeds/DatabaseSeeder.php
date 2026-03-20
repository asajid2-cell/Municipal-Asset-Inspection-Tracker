<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Primary database seeder for local development and demo environments.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OrganizationSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(AssetCategorySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(AssetSeeder::class);
        $this->call(InspectionSeeder::class);
        $this->call(MaintenanceRequestSeeder::class);
        $this->call(ActivityLogSeeder::class);
        $this->call(AdvancedPlatformSeeder::class);
    }
}
