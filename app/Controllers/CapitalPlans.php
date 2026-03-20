<?php

namespace App\Controllers;

use App\Libraries\CapitalPlanningService;
use App\Libraries\ReportingService;
use App\Models\CapitalPlanScenarioModel;

/**
 * Capital planning screens and scenario generation.
 */
class CapitalPlans extends BaseController
{
    public function index(): string
    {
        $organizationId = $this->currentOrganizationId();

        return view('capital_plans/index', [
            'pageTitle' => 'Capital Planning',
            'activeNav' => 'plans',
            'scenarios' => (new CapitalPlanScenarioModel())->recentForOrganization($organizationId, 20),
            'topCandidates' => (new ReportingService())->capitalCandidates($organizationId, 10),
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function create()
    {
        $name = trim((string) $this->request->getPost('name'));
        $horizon = (int) $this->request->getPost('planning_horizon_years');
        $annualBudget = (float) $this->request->getPost('annual_budget');
        $notes = trim((string) $this->request->getPost('strategy_notes'));

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Scenario name is required.';
        }

        if ($horizon < 1) {
            $errors['planning_horizon_years'] = 'Planning horizon must be at least one year.';
        }

        if ($annualBudget <= 0) {
            $errors['annual_budget'] = 'Annual budget must be greater than zero.';
        }

        if ($errors !== []) {
            return redirect()->to(site_url('capital-planning'))->withInput()->with('errors', $errors);
        }

        (new CapitalPlanningService())->generateScenario(
            $this->currentOrganizationId(),
            $this->currentUserId(),
            $name,
            $horizon,
            $annualBudget,
            $notes === '' ? null : $notes
        );

        return redirect()->to(site_url('capital-planning'))->with('success', 'Capital scenario generated.');
    }
}
