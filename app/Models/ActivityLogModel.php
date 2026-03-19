<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for the application's audit trail records.
 */
class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'summary',
        'metadata_json',
        'created_at',
    ];
}
