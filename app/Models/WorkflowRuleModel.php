<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Configurable automation rules applied to inspections and follow-up work.
 */
class WorkflowRuleModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'workflow_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'name',
        'trigger_event',
        'enabled',
        'match_status',
        'min_condition_rating',
        'create_request',
        'default_priority',
        'notification_template_key',
        'assign_department_id',
        'due_in_days',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeForEvent(int $organizationId, string $event): array
    {
        return $this->where('organization_id', $organizationId)
            ->where('trigger_event', $event)
            ->where('enabled', true)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
