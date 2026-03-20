<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for files attached to inspections or maintenance requests.
 */
class AttachmentModel extends Model
{
    /**
     * File types allowed for inspection evidence uploads.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
    ];

    /**
     * Maximum attachment size in bytes.
     */
    public const MAX_FILE_SIZE_BYTES = 5_242_880;

    protected $DBGroup = 'default';
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
        'evidence_type',
        'retention_class',
        'created_at',
    ];

    /**
     * Returns attachments grouped by inspection ID for the asset detail screen.
     *
     * @param list<int> $inspectionIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function groupedByInspectionIds(array $inspectionIds): array
    {
        if ($inspectionIds === []) {
            return [];
        }

        $rows = $this->whereIn('inspection_id', $inspectionIds)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['inspection_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * Returns one attachment row for secure downloads.
     *
     * @return array<string, mixed>|null
     */
    public function findDownloadable(int $id): ?array
    {
        return $this->where('id', $id)->first();
    }
}
