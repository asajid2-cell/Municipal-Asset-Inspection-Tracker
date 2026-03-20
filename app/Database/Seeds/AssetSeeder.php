<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds a realistic municipal asset inventory across multiple departments.
 */
class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $createdBy = $this->userId('admin@northriver.local');
        $updatedBy = $this->userId('ops@northriver.local');
        $timestamp = '2026-03-18 15:30:00';

        $rows = [
            [
                'organization_id' => 1,
                'asset_code' => 'PARK-BENCH-001',
                'department_id' => $this->departmentId('PARKS'),
                'category_id' => $this->categoryId('Park Bench'),
                'name' => 'Riverfront Bench A',
                'status' => 'Active',
                'location_text' => 'Riverfront Park, southeast trail loop',
                'latitude' => 53.5452000,
                'longitude' => -113.4921000,
                'installed_on' => '2022-06-14',
                'last_inspected_at' => '2026-02-10 10:00:00',
                'next_inspection_due_at' => '2027-02-10 10:00:00',
                'condition_score' => 87,
                'criticality_score' => 28,
                'risk_score' => 24.36,
                'lifecycle_state' => 'Operate',
                'replacement_cost' => 2400.00,
                'actual_cost_to_date' => 300.00,
                'service_level' => 'Neighbourhood amenity',
                'notes' => 'Paint refreshed in 2025.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'asset_code' => 'UTIL-HYDRANT-014',
                'department_id' => $this->departmentId('ROADS'),
                'category_id' => $this->categoryId('Fire Hydrant'),
                'name' => 'Hydrant 14 - Oakview',
                'status' => 'Needs Repair',
                'location_text' => 'Corner of Oakview Road and 3rd Street',
                'latitude' => 53.5463000,
                'longitude' => -113.4879000,
                'installed_on' => '2019-09-22',
                'last_inspected_at' => '2026-03-01 08:30:00',
                'next_inspection_due_at' => '2026-08-28 08:30:00',
                'condition_score' => 41,
                'criticality_score' => 91,
                'risk_score' => 74.62,
                'lifecycle_state' => 'Renew',
                'replacement_cost' => 18500.00,
                'actual_cost_to_date' => 4200.00,
                'service_level' => 'Fire protection',
                'notes' => 'Low pressure noted in March inspection.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'asset_code' => 'PLAY-STR-007',
                'department_id' => $this->departmentId('PARKS'),
                'category_id' => $this->categoryId('Playground Structure'),
                'name' => 'Aspen Grove Climber',
                'status' => 'Out of Service',
                'location_text' => 'Aspen Grove Park main playground',
                'latitude' => 53.5481000,
                'longitude' => -113.4950000,
                'installed_on' => '2021-05-03',
                'last_inspected_at' => '2026-03-05 09:15:00',
                'next_inspection_due_at' => '2026-06-03 09:15:00',
                'condition_score' => 33,
                'criticality_score' => 76,
                'risk_score' => 66.40,
                'lifecycle_state' => 'Repair',
                'replacement_cost' => 62500.00,
                'actual_cost_to_date' => 11800.00,
                'service_level' => 'Play safety',
                'notes' => 'Temporarily closed after damaged ladder rail was found.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'asset_code' => 'LIB-PC-022',
                'department_id' => $this->departmentId('LIBRARY'),
                'category_id' => $this->categoryId('Library Computer'),
                'name' => 'Children Wing Station 2',
                'status' => 'Active',
                'location_text' => 'North River Public Library, children wing',
                'latitude' => null,
                'longitude' => null,
                'installed_on' => '2024-01-15',
                'last_inspected_at' => '2026-02-20 14:20:00',
                'next_inspection_due_at' => '2026-08-19 14:20:00',
                'condition_score' => 78,
                'criticality_score' => 22,
                'risk_score' => 19.16,
                'lifecycle_state' => 'Operate',
                'replacement_cost' => 2100.00,
                'actual_cost_to_date' => 700.00,
                'service_level' => 'Public access',
                'notes' => 'Patron keyboard replaced in January.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'asset_code' => 'FAC-HVAC-003',
                'department_id' => $this->departmentId('FACILITIES'),
                'category_id' => $this->categoryId('HVAC Unit'),
                'name' => 'Community Centre Roof Unit 3',
                'status' => 'Needs Inspection',
                'location_text' => 'North River Community Centre roof access',
                'latitude' => null,
                'longitude' => null,
                'installed_on' => '2018-10-08',
                'last_inspected_at' => '2025-10-15 11:30:00',
                'next_inspection_due_at' => '2026-02-12 11:30:00',
                'condition_score' => 49,
                'criticality_score' => 82,
                'risk_score' => 66.26,
                'lifecycle_state' => 'Inspect',
                'replacement_cost' => 52000.00,
                'actual_cost_to_date' => 16400.00,
                'service_level' => 'Facility climate control',
                'notes' => 'Inspection overdue at seed time to support dashboard use cases.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'organization_id' => 1,
                'asset_code' => 'ROAD-LIGHT-045',
                'department_id' => $this->departmentId('ROADS'),
                'category_id' => $this->categoryId('Streetlight'),
                'name' => 'Pedestrian Crossing Light 45',
                'status' => 'Active',
                'location_text' => '107 Avenue pedestrian crossing',
                'latitude' => 53.5494000,
                'longitude' => -113.4826000,
                'installed_on' => '2020-11-28',
                'last_inspected_at' => '2026-01-14 07:45:00',
                'next_inspection_due_at' => '2026-07-13 07:45:00',
                'condition_score' => 74,
                'criticality_score' => 63,
                'risk_score' => 41.58,
                'lifecycle_state' => 'Operate',
                'replacement_cost' => 9800.00,
                'actual_cost_to_date' => 2400.00,
                'service_level' => 'Road safety',
                'notes' => 'LED array replaced in late 2025.',
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('assets')->insertBatch($rows);
    }

    private function departmentId(string $code): int
    {
        $row = $this->db->table('departments')
            ->select('id')
            ->where('code', $code)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing department seed dependency: {$code}");
        }

        return (int) $row['id'];
    }

    private function categoryId(string $name): int
    {
        $row = $this->db->table('asset_categories')
            ->select('id')
            ->where('name', $name)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing category seed dependency: {$name}");
        }

        return (int) $row['id'];
    }

    private function userId(string $email): int
    {
        $row = $this->db->table('users')
            ->select('id')
            ->where('email', $email)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException("Missing user seed dependency: {$email}");
        }

        return (int) $row['id'];
    }
}
