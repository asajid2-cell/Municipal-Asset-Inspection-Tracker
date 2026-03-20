<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Represents a municipality or operating tenant in the platform.
 */
class OrganizationModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'organizations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'slug',
        'region',
    ];
}
