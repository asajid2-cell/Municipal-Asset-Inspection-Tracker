<?php

namespace App\Controllers;

use App\Models\ExportJobModel;
use App\Models\NotificationTemplateModel;
use App\Models\OfflineSyncConflictModel;
use App\Models\OfflineSyncPacketModel;
use App\Models\OrganizationModel;
use App\Models\PerformanceSnapshotModel;
use App\Models\SourceHealthSnapshotModel;
use App\Models\SyncJobModel;
use App\Models\WorkflowRuleModel;

/**
 * Admin console for platform configuration, diagnostics, and source operations.
 */
class Admin extends BaseController
{
    public function index(): string
    {
        $organizationId = $this->currentOrganizationId();

        return view('admin/index', [
            'pageTitle' => 'Admin Console',
            'activeNav' => 'admin',
            'organization' => (new OrganizationModel())->find($organizationId),
            'templates' => (new NotificationTemplateModel())->where('organization_id', $organizationId)->findAll(),
            'workflowRules' => (new WorkflowRuleModel())->where('organization_id', $organizationId)->findAll(),
            'syncJobs' => (new SyncJobModel())->recentForOrganization($organizationId, 8),
            'exportJobs' => (new ExportJobModel())->recentForOrganization($organizationId, 8),
            'sourceHealth' => (new SourceHealthSnapshotModel())->latestForOrganization($organizationId, 12),
            'packets' => (new OfflineSyncPacketModel())->recentForOrganization($organizationId, 6),
            'conflicts' => (new OfflineSyncConflictModel())->openForOrganization($organizationId, 6),
            'performance' => (new PerformanceSnapshotModel())->recentForOrganization($organizationId, 12),
        ]);
    }
}
