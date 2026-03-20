<?php

namespace App\Controllers;

use App\Libraries\ReportingService;
use App\Models\AssetModel;
use App\Models\CapitalPlanScenarioModel;
use App\Models\ExportJobModel;
use App\Models\MaintenanceRequestModel;
use App\Models\NotificationDeliveryModel;

/**
 * Public landing page for the portfolio project.
 */
class Home extends BaseController
{
    public function index(): string
    {
        $assetModel = new AssetModel();
        $maintenanceModel = new MaintenanceRequestModel();
        $notificationModel = new NotificationDeliveryModel();
        $reporting = new ReportingService();
        $summary = $reporting->executiveSummary($this->currentOrganizationId());

        return view('home', [
            'projectName'  => 'Municipal Asset & Inspection Tracker',
            'pageTitle'    => 'Municipal Asset & Inspection Tracker',
            'activeNav'    => 'home',
            'currentBuild' => 'Current build: reporting, admin console, export jobs, workflow rules, capital planning, mobile packets, digital twin, and public open-data sync',
            'assetCount'   => $assetModel->countAllResults(),
            'overdueCount' => $assetModel->overdueCount(),
            'openMaintenanceCount' => $maintenanceModel->openRequestCount(),
            'statusBreakdown' => $assetModel->statusBreakdown(),
            'overdueAssets' => $assetModel->overdueAssets(),
            'openMaintenanceRequests' => $maintenanceModel->openQueue(),
            'recentNotifications' => $notificationModel->recent(),
            'reportSummary' => $summary,
            'recentScenarios' => (new CapitalPlanScenarioModel())->recentForOrganization($this->currentOrganizationId(), 3),
            'recentExports' => (new ExportJobModel())->recentForOrganization($this->currentOrganizationId(), 3),
        ]);
    }
}
