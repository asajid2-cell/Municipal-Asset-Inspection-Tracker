<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for the application's audit trail records.
 */
class ActivityLogModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'summary',
        'metadata_json',
        'created_at',
    ];

    /**
     * Writes a compact audit entry for workflow actions.
     *
     * @param array<string, mixed>|null $metadata
     */
    public function recordEntry(
        ?int $actorUserId,
        string $entityType,
        int $entityId,
        string $action,
        string $summary,
        ?array $metadata = null,
        int $organizationId = 1
    ): bool {
        return $this->insert([
            'organization_id' => $organizationId,
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'summary' => $summary,
            'metadata_json' => $metadata === null ? null : json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
        ], false) !== false;
    }

    /**
     * Applies the joined audit log query used by the audit screen.
     */
    public function forAuditList(array $filters = []): self
    {
        $this->select(
            'activity_logs.*, users.full_name AS actor_name, users.email AS actor_email, users.role AS actor_role'
        )
            ->join('users', 'users.id = activity_logs.actor_user_id', 'left');

        $search = trim((string) ($filters['q'] ?? ''));
        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));

        if ($search !== '') {
            $this->groupStart()
                ->like('activity_logs.summary', $search)
                ->orLike('activity_logs.entity_type', $search)
                ->orLike('users.full_name', $search)
                ->groupEnd();
        }

        if ($entityType !== '') {
            $this->where('activity_logs.entity_type', $entityType);
        }

        if ($action !== '') {
            $this->where('activity_logs.action', $action);
        }

        $this->orderBy('activity_logs.created_at', 'DESC');

        return $this;
    }
}
