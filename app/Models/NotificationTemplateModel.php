<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores reusable notification templates for captured or queued deliveries.
 */
class NotificationTemplateModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'notification_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'organization_id',
        'template_key',
        'channel',
        'subject_template',
        'body_template',
        'is_active',
    ];

    public function findActiveTemplate(int $organizationId, string $templateKey): ?array
    {
        return $this->where('organization_id', $organizationId)
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();
    }
}
