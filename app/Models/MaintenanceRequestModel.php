<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for follow-up maintenance work raised from inspections or manual reports.
 */
class MaintenanceRequestModel extends Model
{
    protected $table = 'maintenance_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'asset_id',
        'inspection_id',
        'opened_by',
        'assigned_department_id',
        'title',
        'description',
        'priority',
        'status',
        'due_at',
        'resolved_at',
    ];
}
