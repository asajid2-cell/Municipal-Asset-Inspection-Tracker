<?php

namespace App\Controllers;

use App\Libraries\NotificationManager;
use App\Models\AssetModel;
use App\Models\NotificationDeliveryModel;

/**
 * Lists captured deliveries and triggers development-safe reminder runs.
 */
class Notifications extends BaseController
{
    private const PER_PAGE = 12;

    public function index(): string
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'context_type' => trim((string) $this->request->getGet('context_type')),
        ];

        $deliveryModel = new NotificationDeliveryModel();
        $deliveries = $deliveryModel->forOutbox($filters)->paginate(self::PER_PAGE);
        $pager = $deliveryModel->pager->only(['q', 'context_type']);

        return view('notifications/index', [
            'pageTitle' => 'Notification Outbox',
            'activeNav' => 'notifications',
            'deliveries' => $deliveries,
            'pager' => $pager,
            'filters' => $filters,
            'resultTotal' => $pager->getTotal(),
        ]);
    }

    public function sendOverdueReminders()
    {
        $assets = (new AssetModel())->overdueAssetsForNotifications();
        $count = (new NotificationManager())->captureOverdueInspectionReminders($assets, $this->currentUserId());

        if ($count === 0) {
            return redirect()
                ->to(site_url('notifications'))
                ->with('warning', 'No overdue reminders were captured because no overdue assets had a department contact email.');
        }

        return redirect()
            ->to(site_url('notifications'))
            ->with('success', $count . ' overdue reminder' . ($count === 1 ? '' : 's') . ' captured.');
    }
}
