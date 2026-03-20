<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;

/**
 * Renders the audit trail used to review operational changes.
 */
class ActivityLogs extends BaseController
{
    private const PER_PAGE = 12;

    public function index(): string
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'entity_type' => trim((string) $this->request->getGet('entity_type')),
            'action' => trim((string) $this->request->getGet('action')),
        ];

        $activityModel = new ActivityLogModel();
        $entries = $activityModel->forAuditList($filters)->paginate(self::PER_PAGE);
        $pager = $activityModel->pager->only(['q', 'entity_type', 'action']);

        return view('activity_logs/index', [
            'pageTitle' => 'Audit Log',
            'activeNav' => 'audit',
            'entries' => $entries,
            'pager' => $pager,
            'filters' => $filters,
            'resultTotal' => $pager->getTotal(),
        ]);
    }
}
