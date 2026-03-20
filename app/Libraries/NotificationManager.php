<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;
use App\Models\NotificationDeliveryModel;
use App\Models\NotificationTemplateModel;
use Config\Notifications;

/**
 * Captures operational email notifications in a development-safe outbox.
 */
class NotificationManager
{
    private NotificationDeliveryModel $deliveryModel;
    private ActivityLogModel $activityLogModel;
    private Notifications $config;

    public function __construct()
    {
        $this->deliveryModel = new NotificationDeliveryModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->config = config(Notifications::class);
    }

    /**
     * Captures a failed-inspection notification for the owning department.
     */
    public function captureFailedInspectionAlert(array $asset, array $inspection, ?int $actorUserId): ?int
    {
        return $this->captureTemplateDelivery('inspection_followup', $asset, $inspection, $actorUserId);
    }

    /**
     * Captures overdue inspection reminders for all assets with a contact email.
     *
     * @param array<int, array<string, mixed>> $assets
     */
    public function captureOverdueInspectionReminders(array $assets, ?int $actorUserId): int
    {
        $count = 0;

        foreach ($assets as $asset) {
            $recipientEmail = trim((string) ($asset['department_contact_email'] ?? ''));

            if ($recipientEmail === '') {
                continue;
            }

            $deliveryId = $this->captureTemplateDelivery('overdue_reminder', $asset, [], $actorUserId);

            if ($deliveryId !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Renders a template-driven delivery for an asset-centric context.
     *
     * @param array<string, mixed> $asset
     * @param array<string, mixed> $context
     */
    public function captureTemplateDelivery(string $templateKey, array $asset, array $context, ?int $actorUserId): ?int
    {
        $recipientEmail = trim((string) ($asset['department_contact_email'] ?? ''));

        if ($recipientEmail === '') {
            return null;
        }

        $organizationId = (int) ($asset['organization_id'] ?? 1);
        $template = (new NotificationTemplateModel())->findActiveTemplate($organizationId, $templateKey);
        $data = $this->templateData($asset, $context);

        if ($template !== null) {
            $subject = $this->renderTemplate((string) $template['subject_template'], $data);
            $body = $this->renderTemplate((string) $template['body_template'], $data);

            return $this->captureDelivery(
                $organizationId,
                $recipientEmail,
                (string) ($asset['department_name'] ?? $asset['department_code'] ?? ''),
                $subject,
                $body,
                isset($context['id']) ? 'inspection' : 'asset',
                isset($context['id']) ? (int) $context['id'] : (int) $asset['id'],
                $actorUserId,
                $templateKey
            );
        }

        $subject = $this->config->subjectPrefix . 'Notification for ' . $asset['asset_code'];
        $body = $this->renderTemplate("Asset: {{asset_code}} - {{asset_name}}\nDepartment: {{department_name}}", $data);

        return $this->captureDelivery(
            $organizationId,
            $recipientEmail,
            (string) ($asset['department_name'] ?? ''),
            $subject,
            $body,
            isset($context['id']) ? 'inspection' : 'asset',
            isset($context['id']) ? (int) $context['id'] : (int) $asset['id'],
            $actorUserId,
            $templateKey
        );
    }

    /**
     * Persists a delivery record and writes a matching audit entry.
     */
    private function captureDelivery(
        int $organizationId,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $bodyText,
        string $contextType,
        ?int $contextId,
        ?int $actorUserId,
        ?string $templateKey = null
    ): ?int {
        $this->deliveryModel->insert([
            'organization_id' => $organizationId,
            'channel' => 'email',
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'body_text' => $bodyText,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'status' => $this->config->captureOnly ? 'Captured' : 'Queued',
            'template_key' => $templateKey,
            'created_by' => $actorUserId,
            'sent_at' => $this->config->captureOnly ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s'),
        ], false);

        $deliveryId = (int) $this->deliveryModel->getInsertID();

        $this->activityLogModel->recordEntry(
            $actorUserId,
            'notification_delivery',
            $deliveryId,
            'captured',
            'Captured email notification for ' . $recipientEmail . '.',
            [
                'context_type' => $contextType,
                'context_id' => $contextId,
                'subject' => $subject,
            ],
            $organizationId
        );

        return $deliveryId;
    }

    /**
     * @param array<string, mixed> $asset
     * @param array<string, mixed> $context
     *
     * @return array<string, string>
     */
    private function templateData(array $asset, array $context): array
    {
        return [
            'asset_code' => (string) ($asset['asset_code'] ?? ''),
            'asset_name' => (string) ($asset['name'] ?? ''),
            'department_name' => (string) ($asset['department_name'] ?? ''),
            'category_name' => (string) ($asset['category_name'] ?? ''),
            'location_text' => (string) ($asset['location_text'] ?? ''),
            'status' => (string) ($asset['status'] ?? ''),
            'next_inspection_due_at' => (string) ($asset['next_inspection_due_at'] ?? ''),
            'result_status' => (string) ($context['result_status'] ?? ''),
            'inspected_at' => (string) ($context['inspected_at'] ?? ''),
            'next_due_at' => (string) ($context['next_due_at'] ?? ''),
            'notes' => (string) (($context['notes'] ?? '') !== '' ? $context['notes'] : 'No notes provided.'),
        ];
    }

    /**
     * @param array<string, string> $data
     */
    private function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }
}
