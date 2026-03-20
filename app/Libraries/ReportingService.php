<?php

namespace App\Libraries;

use App\Models\AssetModel;
use App\Models\DepartmentModel;
use App\Models\MaintenanceRequestModel;
use App\Models\SourceHealthSnapshotModel;
use CodeIgniter\Database\BaseBuilder;

/**
 * Builds the cross-cutting reporting views used by executives and admins.
 */
class ReportingService
{
    /**
     * @return array<string, mixed>
     */
    public function executiveSummary(int $organizationId): array
    {
        $assetModel = new AssetModel();
        $db = db_connect('default');

        $assetCount = (int) $db->table('assets')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->countAllResults();

        $overdueCount = (int) $db->table('assets')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->where('next_inspection_due_at <', date('Y-m-d H:i:s'))
            ->where('next_inspection_due_at IS NOT NULL', null, false)
            ->countAllResults();

        $repairBacklog = (int) $db->table('maintenance_requests')
            ->where('organization_id', $organizationId)
            ->whereIn('status', MaintenanceRequestModel::ACTIVE_STATUSES)
            ->countAllResults();

        $riskExposure = (float) ($db->table('assets')
            ->select('AVG(COALESCE(risk_score, 0)) AS avg_risk')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray()['avg_risk'] ?? 0);

        return [
            'asset_count' => $assetCount,
            'overdue_count' => $overdueCount,
            'repair_backlog_count' => $repairBacklog,
            'average_risk' => round($riskExposure, 2),
            'status_breakdown' => $assetModel->statusBreakdown(),
            'department_scorecards' => $this->departmentScorecards($organizationId),
            'top_capital_candidates' => $this->capitalCandidates($organizationId, 5),
            'source_health' => $this->sourceHealth($organizationId, false),
            'maintenance_age_buckets' => $this->maintenanceAgeBuckets($organizationId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function departmentScorecards(int $organizationId): array
    {
        $rows = db_connect('default')->table('departments')
            ->select(
                'departments.id, departments.name, departments.code, '
                . 'COUNT(assets.id) AS asset_total, '
                . 'SUM(CASE WHEN assets.status = "Needs Inspection" THEN 1 ELSE 0 END) AS needs_inspection_total, '
                . 'SUM(CASE WHEN assets.status = "Needs Repair" THEN 1 ELSE 0 END) AS needs_repair_total, '
                . 'AVG(COALESCE(assets.risk_score, 0)) AS avg_risk'
            )
            ->join('assets', 'assets.department_id = departments.id AND assets.deleted_at IS NULL', 'left')
            ->where('assets.organization_id', $organizationId)
            ->groupBy('departments.id')
            ->orderBy('avg_risk', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'name' => (string) $row['name'],
                'code' => (string) $row['code'],
                'asset_total' => (int) $row['asset_total'],
                'needs_inspection_total' => (int) $row['needs_inspection_total'],
                'needs_repair_total' => (int) $row['needs_repair_total'],
                'avg_risk' => round((float) $row['avg_risk'], 2),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function capitalCandidates(int $organizationId, int $limit = 10): array
    {
        $query = db_connect('default')->table('assets')
            ->select(
                'assets.id, assets.asset_code, assets.name, assets.status, assets.location_text, '
                . 'COALESCE(assets.risk_score, CASE '
                . 'WHEN assets.status = "Out of Service" THEN 90 '
                . 'WHEN assets.status = "Needs Repair" THEN 75 '
                . 'WHEN assets.status = "Needs Inspection" THEN 55 '
                . 'ELSE 25 END) AS effective_risk, '
                . 'COALESCE(assets.replacement_cost, 5000) AS replacement_cost, '
                . 'assets.lifecycle_state, departments.name AS department_name, asset_categories.name AS category_name'
            )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('assets.organization_id', $organizationId)
            ->where('assets.deleted_at', null)
            ->orderBy('effective_risk', 'DESC', false)
            ->orderBy('replacement_cost', 'DESC')
            ->limit($limit);

        return $query->get()->getResultArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sourceHealth(int $organizationId, bool $capture = true): array
    {
        $db = db_connect('default');
        $sources = $db->table('assets')
            ->select(
                'source_dataset, COUNT(*) AS total_assets, '
                . 'SUM(CASE WHEN latitude IS NULL OR longitude IS NULL THEN 1 ELSE 0 END) AS unmapped_assets, '
                . 'SUM(CASE WHEN source_geometry_type IS NOT NULL AND source_geometry IS NULL THEN 1 ELSE 0 END) AS invalid_geometry_assets, '
                . 'MAX(updated_at) AS last_synced_at'
            )
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->where('source_dataset IS NOT NULL', null, false)
            ->groupBy('source_dataset')
            ->orderBy('total_assets', 'DESC')
            ->get()
            ->getResultArray();

        $duplicateLookup = $this->duplicateSourceCounts($organizationId);
        $model = new SourceHealthSnapshotModel();
        $capturedAt = date('Y-m-d H:i:s');
        $result = [];

        foreach ($sources as $row) {
            $sourceKey = (string) $row['source_dataset'];
            $entry = [
                'source_key' => $sourceKey,
                'source_label' => $sourceKey,
                'total_assets' => (int) $row['total_assets'],
                'unmapped_assets' => (int) $row['unmapped_assets'],
                'invalid_geometry_assets' => (int) $row['invalid_geometry_assets'],
                'duplicate_assets' => (int) ($duplicateLookup[$sourceKey] ?? 0),
                'last_synced_at' => $row['last_synced_at'],
            ];

            if ($capture) {
                $model->insert([
                    'organization_id' => $organizationId,
                    'source_key' => $entry['source_key'],
                    'source_label' => $entry['source_label'],
                    'total_assets' => $entry['total_assets'],
                    'unmapped_assets' => $entry['unmapped_assets'],
                    'invalid_geometry_assets' => $entry['invalid_geometry_assets'],
                    'duplicate_assets' => $entry['duplicate_assets'],
                    'last_synced_at' => $entry['last_synced_at'],
                    'captured_at' => $capturedAt,
                ], false);
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function duplicateSourceCounts(int $organizationId): array
    {
        $rows = db_connect('default')->table('assets')
            ->select('source_dataset, source_record_id, COUNT(*) AS total')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->where('source_dataset IS NOT NULL', null, false)
            ->groupBy(['source_dataset', 'source_record_id'])
            ->having('COUNT(*) >', 1)
            ->get()
            ->getResultArray();

        $counts = [];

        foreach ($rows as $row) {
            $sourceKey = (string) $row['source_dataset'];
            $counts[$sourceKey] = ($counts[$sourceKey] ?? 0) + ((int) $row['total'] - 1);
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    private function maintenanceAgeBuckets(int $organizationId): array
    {
        $rows = db_connect('default')->table('maintenance_requests')
            ->select(
                'SUM(CASE WHEN julianday("now") - julianday(created_at) < 8 THEN 1 ELSE 0 END) AS under_7, '
                . 'SUM(CASE WHEN julianday("now") - julianday(created_at) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) AS under_30, '
                . 'SUM(CASE WHEN julianday("now") - julianday(created_at) > 30 THEN 1 ELSE 0 END) AS over_30'
            )
            ->where('organization_id', $organizationId)
            ->whereIn('status', MaintenanceRequestModel::ACTIVE_STATUSES)
            ->get()
            ->getRowArray() ?? [];

        return [
            'under_7_days' => (int) ($rows['under_7'] ?? 0),
            'between_8_and_30_days' => (int) ($rows['under_30'] ?? 0),
            'over_30_days' => (int) ($rows['over_30'] ?? 0),
        ];
    }
}
