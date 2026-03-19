<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for files attached to inspections or maintenance requests.
 */
class AttachmentModel extends Model
{
    protected $table = 'attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'inspection_id',
        'maintenance_request_id',
        'uploaded_by',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size_bytes',
        'created_at',
    ];
}
