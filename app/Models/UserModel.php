<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for internal staff users who will later authenticate into the system.
 */
class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'department_id',
        'full_name',
        'email',
        'password_hash',
        'role',
        'is_active',
        'last_login_at',
    ];
}
