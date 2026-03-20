<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for internal staff users who will later authenticate into the system.
 */
class UserModel extends Model
{
    /**
     * Roles allowed to create or change operational records.
     *
     * @var list<string>
     */
    public const EDITOR_ROLES = [
        'admin',
        'operations_coordinator',
        'inspector',
        'department_manager',
    ];

    protected $DBGroup = 'default';
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'department_id',
        'full_name',
        'email',
        'password_hash',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * Returns active staff members who can be selected on the inspection form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function inspectionStaff(): array
    {
        return $this->where('is_active', true)
            ->whereIn('role', self::EDITOR_ROLES)
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    /**
     * Returns an active user by email for session login.
     */
    public function findActiveByEmail(string $email): ?array
    {
        return $this->where('email', $email)
            ->where('is_active', true)
            ->first();
    }
}
