<?php

namespace App\Libraries;

use App\Models\MaintenanceRequestModel;
use App\Models\WorkflowRuleModel;
use DateTimeImmutable;

/**
 * Applies configurable automation rules to inspections and follow-up operations.
 */
class WorkflowRuleEngine
{
    /**
     * @param array<string, mixed> $asset
     * @param array<string, mixed> $inspection
     *
     * @return array{requests_created: int, notifications_sent: int}
     */
    public function handleInspectionLogged(array $asset, array $inspection, ?int $actorUserId): array
    {
        $organizationId = (int) ($asset['organization_id'] ?? 1);
        $rules = (new WorkflowRuleModel())->activeForEvent($organizationId, 'inspection.logged');
        $requestsCreated = 0;
        $notificationsSent = 0;

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $inspection)) {
                continue;
            }

            if ((bool) $rule['create_request']) {
                $existing = (new MaintenanceRequestModel())
                    ->where('inspection_id', (int) $inspection['id'])
                    ->first();

                if ($existing === null) {
                    $dueAt = $this->dueAtFromRule((string) $inspection['inspected_at'], $rule['due_in_days'] === null ? 7 : (int) $rule['due_in_days']);

                    (new MaintenanceRequestModel())->insert([
                        'organization_id' => $organizationId,
                        'asset_id' => (int) $asset['id'],
                        'inspection_id' => (int) $inspection['id'],
                        'opened_by' => $actorUserId,
                        'assigned_department_id' => (int) ($rule['assign_department_id'] ?? $asset['department_id']),
                        'title' => 'Automated follow-up for ' . $asset['asset_code'],
                        'description' => 'Created by workflow rule: ' . $rule['name'],
                        'priority' => (string) ($rule['default_priority'] ?? 'Medium'),
                        'status' => 'Open',
                        'due_at' => $dueAt,
                        'sla_target_at' => $dueAt,
                    ], false);

                    $requestsCreated++;
                }
            }

            if ((string) ($rule['notification_template_key'] ?? '') !== '') {
                $deliveryId = (new NotificationManager())->captureTemplateDelivery(
                    (string) $rule['notification_template_key'],
                    $asset,
                    $inspection,
                    $actorUserId
                );

                if ($deliveryId !== null) {
                    $notificationsSent++;
                }
            }
        }

        return [
            'requests_created' => $requestsCreated,
            'notifications_sent' => $notificationsSent,
        ];
    }

    /**
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $inspection
     */
    private function matches(array $rule, array $inspection): bool
    {
        if ($rule['match_status'] !== null && (string) $rule['match_status'] !== (string) $inspection['result_status']) {
            return false;
        }

        if (
            $rule['min_condition_rating'] !== null
            && (int) $inspection['condition_rating'] < (int) $rule['min_condition_rating']
        ) {
            return false;
        }

        return true;
    }

    private function dueAtFromRule(string $inspectedAt, int $dueInDays): string
    {
        return (new DateTimeImmutable($inspectedAt))
            ->modify('+' . $dueInDays . ' days')
            ->format('Y-m-d H:i:s');
    }
}
