<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for historical inspection records tied to assets.
 */
class InspectionModel extends Model
{
    protected $table = 'inspections';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'asset_id',
        'inspector_id',
        'inspected_at',
        'condition_rating',
        'result_status',
        'notes',
        'next_due_at',
    ];
}
