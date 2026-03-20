<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores email deliveries captured during development-friendly notification flows.
 */
class NotificationDeliveryModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'notification_deliveries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'channel',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_text',
        'context_type',
        'context_id',
        'status',
        'template_key',
        'created_by',
        'sent_at',
        'created_at',
    ];

    /**
     * Applies the list query used by the notification outbox screen.
     */
    public function forOutbox(array $filters = []): self
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $contextType = trim((string) ($filters['context_type'] ?? ''));

        if ($search !== '') {
            $this->groupStart()
                ->like('recipient_email', $search)
                ->orLike('subject', $search)
                ->orLike('body_text', $search)
                ->groupEnd();
        }

        if ($contextType !== '') {
            $this->where('context_type', $contextType);
        }

        return $this->orderBy('created_at', 'DESC');
    }

    /**
     * Returns the latest captured deliveries for the dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 5): array
    {
        return $this->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
