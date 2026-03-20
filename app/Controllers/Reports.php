<?php

namespace App\Controllers;

use App\Libraries\PerformanceTelemetry;
use App\Libraries\ReportingService;
use App\Models\CapitalPlanScenarioModel;

/**
 * Executive and departmental reporting screens.
 */
class Reports extends BaseController
{
    public function index(): string
    {
        $organizationId = $this->currentOrganizationId();
        $reporting = new ReportingService();
        $summary = $reporting->executiveSummary($organizationId);

        (new PerformanceTelemetry())->capture($organizationId, 'page_render', 'reports_index', 1, [
            'user_id' => $this->currentUserId(),
        ]);

        return view('reports/index', [
            'pageTitle' => 'Executive Reports',
            'activeNav' => 'reports',
            'summary' => $summary,
            'recentScenarios' => (new CapitalPlanScenarioModel())->recentForOrganization($organizationId, 5),
        ]);
    }
}
