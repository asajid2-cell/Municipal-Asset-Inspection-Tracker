<?php

namespace App\Controllers;

use App\Libraries\ReportingService;
use App\Models\LinearAssetModel;

/**
 * Operations-twin style view that combines asset, risk, and network context.
 */
class DigitalTwin extends BaseController
{
    public function index(): string
    {
        $organizationId = $this->currentOrganizationId();

        return view('digital_twin/index', [
            'pageTitle' => 'Digital Twin',
            'activeNav' => 'twin',
            'summary' => (new ReportingService())->executiveSummary($organizationId),
            'linearAssets' => (new LinearAssetModel())->forNetwork($organizationId, null, 12),
            'mapApiUrl' => site_url('api/assets/map'),
        ]);
    }
}
