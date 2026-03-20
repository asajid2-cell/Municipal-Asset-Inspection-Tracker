<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores generated capital planning scenarios and portfolio summaries.
 */
class CapitalPlanScenarioModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'capital_plan_scenarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'name',
        'planning_horizon_years',
        'annual_budget',
        'strategy_notes',
        'summary_json',
        'generated_at',
        'created_by',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentForOrganization(int $organizationId, int $limit = 10): array
    {
        return $this->where('organization_id', $organizationId)
            ->orderBy('generated_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
