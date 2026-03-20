<?php

namespace App\Libraries;

use App\Models\PerformanceSnapshotModel;

/**
 * Captures lightweight operational metrics for diagnostics and admin reporting.
 */
class PerformanceTelemetry
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function capture(int $organizationId, string $snapshotType, string $metricKey, float|int $metricValue, ?array $context = null): void
    {
        (new PerformanceSnapshotModel())->insert([
            'organization_id' => $organizationId,
            'snapshot_type' => $snapshotType,
            'metric_key' => $metricKey,
            'metric_value' => $metricValue,
            'context_json' => $context === null ? null : json_encode($context, JSON_UNESCAPED_SLASHES),
            'captured_at' => date('Y-m-d H:i:s'),
        ], false);
    }
}
