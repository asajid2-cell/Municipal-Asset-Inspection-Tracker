<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds a default municipal tenant so the app can demonstrate multi-tenant structure.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-19 18:00:00';

        $this->db->table('organizations')->insert([
            'id' => 1,
            'name' => 'North River Municipal Operations',
            'slug' => 'north-river',
            'region' => 'Edmonton Region, Alberta',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
