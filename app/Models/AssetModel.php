<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for municipal assets tracked in the inventory.
 */
class AssetModel extends Model
{
    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'asset_code',
        'department_id',
        'category_id',
        'name',
        'status',
        'location_text',
        'latitude',
        'longitude',
        'installed_on',
        'last_inspected_at',
        'next_inspection_due_at',
        'notes',
        'created_by',
        'updated_by',
    ];
}
