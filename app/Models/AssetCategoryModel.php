<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for asset categories and their default inspection cadence.
 */
class AssetCategoryModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'asset_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'name',
        'inspection_interval_days',
        'default_status',
        'description',
    ];
}
