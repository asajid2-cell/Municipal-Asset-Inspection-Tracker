<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for municipal departments that own or manage assets.
 */
class DepartmentModel extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'name',
        'code',
        'contact_email',
    ];
}
