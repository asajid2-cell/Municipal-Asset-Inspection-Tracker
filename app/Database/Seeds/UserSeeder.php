<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Seeds development users that represent the app's initial operating roles.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = '2026-03-18 15:30:00';
        $passwordHash = password_hash('Password123!', PASSWORD_DEFAULT);

        $rows = [
            [
                'department_id' => $this->departmentId('FACILITIES'),
                'full_name' => 'Alex Morgan',
                'email' => 'admin@northriver.local',
                'password_hash' => $passwordHash,
                'role' => 'admin',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => $this->departmentId('PARKS'),
                'full_name' => 'Jamie Patel',
                'email' => 'ops@northriver.local',
                'password_hash' => $passwordHash,
                'role' => 'operations_coordinator',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => $this->departmentId('ROADS'),
                'full_name' => 'Riley Chen',
                'email' => 'inspector@northriver.local',
                'password_hash' => $passwordHash,
                'role' => 'inspector',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'department_id' => $this->departmentId('FACILITIES'),
                'full_name' => 'Taylor Brooks',
                'email' => 'manager@northriver.local',
                'password_hash' => $passwordHash,
                'role' => 'department_manager',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        $this->db->table('users')->insertBatch($rows);
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
}
