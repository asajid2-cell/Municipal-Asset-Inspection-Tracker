<?php

namespace App\Libraries;

use App\Models\CapitalPlanScenarioModel;
use RuntimeException;

/**
 * Generates simple capital planning scenarios from current asset risk and cost data.
 */
class CapitalPlanningService
{
    /**
     * @return array<string, mixed>
     */
    public function generateScenario(
        int $organizationId,
        ?int $createdBy,
        string $name,
        int $planningHorizonYears,
        float $annualBudget,
        ?string $strategyNotes = null
    ): array {
        $candidates = db_connect('default')->table('assets')
            ->select(
                'asset_code, name, status, lifecycle_state, '
                . 'COALESCE(risk_score, CASE WHEN status = "Out of Service" THEN 90 WHEN status = "Needs Repair" THEN 75 WHEN status = "Needs Inspection" THEN 55 ELSE 25 END) AS effective_risk, '
                . 'COALESCE(replacement_cost, 5000) AS replacement_cost'
            )
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->orderBy('effective_risk', 'DESC', false)
            ->orderBy('replacement_cost', 'DESC')
            ->limit(25)
            ->get()
            ->getResultArray();

        $recommendedProjects = [];
        $committed = 0.0;

        foreach ($candidates as $candidate) {
            $cost = (float) $candidate['replacement_cost'];

            if ($committed + $cost > $annualBudget) {
                continue;
            }

            $recommendedProjects[] = [
                'asset_code' => (string) $candidate['asset_code'],
                'name' => (string) $candidate['name'],
                'action' => $this->recommendedAction((string) $candidate['status'], (string) $candidate['lifecycle_state']),
                'risk_score' => round((float) $candidate['effective_risk'], 2),
                'estimated_cost' => round($cost, 2),
            ];
            $committed += $cost;
        }

        $summary = [
            'planning_horizon_years' => $planningHorizonYears,
            'annual_budget' => round($annualBudget, 2),
            'committed_budget' => round($committed, 2),
            'remaining_budget' => round(max(0, $annualBudget - $committed), 2),
            'recommended_projects' => $recommendedProjects,
        ];

        $model = new CapitalPlanScenarioModel();
        $model->insert([
            'organization_id' => $organizationId,
            'name' => $name,
            'planning_horizon_years' => $planningHorizonYears,
            'annual_budget' => $annualBudget,
            'strategy_notes' => $strategyNotes,
            'summary_json' => json_encode($summary, JSON_UNESCAPED_SLASHES),
            'generated_at' => date('Y-m-d H:i:s'),
            'created_by' => $createdBy,
        ], false);

        $scenario = $model->find((int) $model->getInsertID());

        if (! is_array($scenario)) {
            throw new RuntimeException('Capital planning scenario could not be stored.');
        }

        return $scenario;
    }

    private function recommendedAction(string $status, string $lifecycleState): string
    {
        if ($status === 'Out of Service') {
            return 'Replace or rehabilitate immediately';
        }

        if ($status === 'Needs Repair') {
            return 'Renew and repair';
        }

        if ($lifecycleState === 'Inspect') {
            return 'Investigate and plan renewal';
        }

        return 'Plan future renewal';
    }
}
