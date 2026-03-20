<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use App\Models\AssetModel;
use App\Models\DepartmentModel;
use App\Models\MaintenanceRequestModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;

/**
 * Handles maintenance request queue screens and manual request entry.
 */
class MaintenanceRequests extends BaseController
{
    private const PER_PAGE = 8;

    public function index(): string
    {
        $filters = $this->queueFilters();
        $requestModel = new MaintenanceRequestModel();
        $requests = $requestModel->forQueueList($filters)->paginate(self::PER_PAGE);
        $pager = $requestModel->pager->only(['q', 'status', 'priority', 'assigned_department_id', 'active_only']);

        return view('maintenance_requests/index', [
            'pageTitle' => 'Maintenance Requests',
            'activeNav' => 'maintenance',
            'requests' => $requests,
            'pager' => $pager,
            'filters' => $filters,
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'priorities' => MaintenanceRequestModel::PRIORITY_OPTIONS,
            'statuses' => MaintenanceRequestModel::STATUS_OPTIONS,
            'resultTotal' => $pager->getTotal(),
        ]);
    }

    public function createForm(int $assetId): string
    {
        $asset = $this->assetOrFail($assetId);

        return view('maintenance_requests/form', [
            'pageTitle' => 'New Maintenance Request',
            'activeNav' => 'assets',
            'asset' => $asset,
            'requestData' => [],
            'formAction' => site_url('assets/' . $assetId . '/maintenance-requests'),
            'submitLabel' => 'Create request',
            'cancelUrl' => site_url('assets/' . $assetId),
            'errors' => session()->getFlashdata('errors') ?? [],
            ...$this->requestFormLookups(),
        ]);
    }

    public function create(int $assetId)
    {
        $asset = $this->assetOrFail($assetId);
        $requestData = $this->requestPayload($assetId);
        $errors = $this->validateRequestPayload($requestData);

        if ($errors !== []) {
            return redirect()
                ->to(site_url('assets/' . $assetId . '/maintenance-requests/new'))
                ->withInput()
                ->with('errors', $errors);
        }

        $maintenanceModel = new MaintenanceRequestModel();
        $saveData = $this->prepareRequestData($requestData);
        $maintenanceModel->insert($saveData);

        $requestId = (int) $maintenanceModel->getInsertID();

        (new ActivityLogModel())->recordEntry(
            $this->currentUserId(),
            'maintenance_request',
            $requestId,
            'created',
            'Opened maintenance request for ' . $asset['asset_code'] . '.',
            [
                'asset_id' => $assetId,
                'asset_code' => $asset['asset_code'],
                'priority' => $saveData['priority'],
                'status' => $saveData['status'],
            ]
        );

        return redirect()
            ->to(site_url('assets/' . $assetId))
            ->with('success', 'Maintenance request created.');
    }

    public function editForm(int $requestId): string
    {
        $request = $this->requestOrFail($requestId);

        return view('maintenance_requests/form', [
            'pageTitle' => 'Update Maintenance Request',
            'activeNav' => 'maintenance',
            'asset' => [
                'id' => $request['asset_id'],
                'asset_code' => $request['asset_code'],
                'name' => $request['asset_name'],
                'location_text' => $request['location_text'],
                'status' => $request['asset_status'],
            ],
            'requestData' => $request,
            'formAction' => site_url('maintenance-requests/' . $requestId),
            'submitLabel' => 'Save request',
            'cancelUrl' => site_url('maintenance-requests'),
            'errors' => session()->getFlashdata('errors') ?? [],
            ...$this->requestFormLookups(),
        ]);
    }

    public function update(int $requestId)
    {
        $existingRequest = $this->requestOrFail($requestId);
        $requestData = $this->requestPayload((int) $existingRequest['asset_id'], $existingRequest['inspection_id'] ? (int) $existingRequest['inspection_id'] : null);
        $errors = $this->validateRequestPayload($requestData);

        if ($errors !== []) {
            return redirect()
                ->to(site_url('maintenance-requests/' . $requestId . '/edit'))
                ->withInput()
                ->with('errors', $errors);
        }

        $saveData = $this->prepareRequestData($requestData, $existingRequest);
        (new MaintenanceRequestModel())->update($requestId, $saveData);

        $statusChanged = (string) $existingRequest['status'] !== (string) $saveData['status'];
        $action = in_array((string) $saveData['status'], MaintenanceRequestModel::COMPLETED_STATUSES, true)
            && ! in_array((string) $existingRequest['status'], MaintenanceRequestModel::COMPLETED_STATUSES, true)
            ? 'resolved'
            : 'updated';

        $summary = $statusChanged
            ? 'Updated maintenance request status from ' . $existingRequest['status'] . ' to ' . $saveData['status'] . '.'
            : 'Updated maintenance request details for ' . $existingRequest['asset_code'] . '.';

        (new ActivityLogModel())->recordEntry(
            $this->currentUserId(),
            'maintenance_request',
            $requestId,
            $action,
            $summary,
            [
                'asset_id' => $existingRequest['asset_id'],
                'asset_code' => $existingRequest['asset_code'],
                'priority' => $saveData['priority'],
                'status' => $saveData['status'],
            ]
        );

        return redirect()
            ->to(site_url('maintenance-requests'))
            ->with('success', 'Maintenance request updated.');
    }

    /**
     * Loads the parent asset for nested maintenance request routes.
     *
     * @return array<string, mixed>
     */
    private function assetOrFail(int $assetId): array
    {
        $asset = (new AssetModel())->findDetailedAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        return $asset;
    }

    /**
     * Loads one request together with related labels used by the edit form.
     *
     * @return array<string, mixed>
     */
    private function requestOrFail(int $requestId): array
    {
        $request = (new MaintenanceRequestModel())->findDetailedRequest($requestId);

        if ($request === null) {
            throw PageNotFoundException::forPageNotFound('Maintenance request not found.');
        }

        return $request;
    }

    /**
     * Returns lookup data shared by the create and edit forms.
     *
     * @return array<string, array<int, array<string, mixed>>|list<string>>
     */
    private function requestFormLookups(): array
    {
        return [
            'staff' => (new UserModel())->inspectionStaff(),
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'priorities' => MaintenanceRequestModel::PRIORITY_OPTIONS,
            'statuses' => MaintenanceRequestModel::STATUS_OPTIONS,
        ];
    }

    /**
     * Normalizes request input before validation.
     *
     * @return array<string, int|string|null>
     */
    private function requestPayload(int $assetId, ?int $inspectionId = null): array
    {
        return [
            'organization_id' => $this->currentOrganizationId(),
            'asset_id' => $assetId,
            'inspection_id' => $inspectionId,
            'opened_by' => $this->nullableInteger($this->request->getPost('opened_by')),
            'assigned_department_id' => $this->nullableInteger($this->request->getPost('assigned_department_id')),
            'assigned_user_id' => $this->nullableInteger($this->request->getPost('assigned_user_id')),
            'work_order_code' => trim((string) $this->request->getPost('work_order_code')),
            'title' => trim((string) $this->request->getPost('title')),
            'description' => $this->nullableString($this->request->getPost('description')),
            'priority' => trim((string) $this->request->getPost('priority')),
            'status' => trim((string) $this->request->getPost('status')),
            'due_at' => trim((string) $this->request->getPost('due_at')),
            'sla_target_at' => trim((string) $this->request->getPost('sla_target_at')),
            'started_at' => trim((string) $this->request->getPost('started_at')),
            'completed_at' => trim((string) $this->request->getPost('completed_at')),
            'labor_hours' => trim((string) $this->request->getPost('labor_hours')),
            'estimated_cost' => trim((string) $this->request->getPost('estimated_cost')),
            'actual_cost' => trim((string) $this->request->getPost('actual_cost')),
            'resolution_notes' => $this->nullableString($this->request->getPost('resolution_notes')),
        ];
    }

    /**
     * Validates request fields and verifies referenced rows exist.
     *
     * @param array<string, int|string|null> $requestData
     *
     * @return array<string, string>
     */
    private function validateRequestPayload(array $requestData): array
    {
        $validation = service('validation');
        $validation->setRules([
            'opened_by' => 'required|is_natural_no_zero',
            'assigned_department_id' => 'required|is_natural_no_zero',
            'assigned_user_id' => 'permit_empty|is_natural_no_zero',
            'work_order_code' => 'permit_empty|max_length[60]',
            'title' => 'required|max_length[190]',
            'description' => 'permit_empty|max_length[4000]',
            'priority' => 'required|in_list[' . implode(',', MaintenanceRequestModel::PRIORITY_OPTIONS) . ']',
            'status' => 'required|in_list[' . implode(',', MaintenanceRequestModel::STATUS_OPTIONS) . ']',
            'due_at' => 'permit_empty',
            'sla_target_at' => 'permit_empty',
            'started_at' => 'permit_empty',
            'completed_at' => 'permit_empty',
            'labor_hours' => 'permit_empty|decimal',
            'estimated_cost' => 'permit_empty|decimal',
            'actual_cost' => 'permit_empty|decimal',
            'resolution_notes' => 'permit_empty|max_length[4000]',
        ]);

        if (! $validation->run($requestData)) {
            /** @var array<string, string> $errors */
            $errors = $validation->getErrors();

            return $errors;
        }

        if ((new UserModel())->find((int) $requestData['opened_by']) === null) {
            return ['opened_by' => 'Select a valid requester.'];
        }

        if ((new DepartmentModel())->find((int) $requestData['assigned_department_id']) === null) {
            return ['assigned_department_id' => 'Select a valid department.'];
        }

        if ($requestData['assigned_user_id'] !== null && (new UserModel())->find((int) $requestData['assigned_user_id']) === null) {
            return ['assigned_user_id' => 'Select a valid assignee.'];
        }

        if ($requestData['due_at'] !== '' && $this->normalizeDateTimeInput((string) $requestData['due_at']) === null) {
            return ['due_at' => 'Enter a valid due date and time.'];
        }

        foreach (['sla_target_at', 'started_at', 'completed_at'] as $field) {
            if ($requestData[$field] !== '' && $this->normalizeDateTimeInput((string) $requestData[$field]) === null) {
                return [$field => 'Enter a valid date and time.'];
            }
        }

        if (
            in_array((string) $requestData['status'], MaintenanceRequestModel::COMPLETED_STATUSES, true)
            && $requestData['resolution_notes'] === null
        ) {
            return ['resolution_notes' => 'Add resolution notes before closing a request.'];
        }

        return [];
    }

    /**
     * Prepares validated request data for insert and update operations.
     *
     * @param array<string, int|string|null> $requestData
     * @param array<string, mixed>|null $existingRequest
     *
     * @return array<string, int|string|null>
     */
    private function prepareRequestData(array $requestData, ?array $existingRequest = null): array
    {
        $status = (string) $requestData['status'];
        $completed = in_array($status, MaintenanceRequestModel::COMPLETED_STATUSES, true);
        $alreadyCompleted = $existingRequest !== null
            && in_array((string) $existingRequest['status'], MaintenanceRequestModel::COMPLETED_STATUSES, true)
            && $existingRequest['resolved_at'] !== null;

        return [
            'organization_id' => $requestData['organization_id'],
            'asset_id' => $requestData['asset_id'],
            'inspection_id' => $requestData['inspection_id'],
            'opened_by' => $requestData['opened_by'],
            'assigned_department_id' => $requestData['assigned_department_id'],
            'assigned_user_id' => $requestData['assigned_user_id'],
            'work_order_code' => $requestData['work_order_code'] === '' ? null : $requestData['work_order_code'],
            'title' => $requestData['title'],
            'description' => $requestData['description'],
            'priority' => $requestData['priority'],
            'status' => $status,
            'due_at' => $requestData['due_at'] === '' ? null : $this->normalizeDateTimeInput((string) $requestData['due_at']),
            'sla_target_at' => $requestData['sla_target_at'] === '' ? null : $this->normalizeDateTimeInput((string) $requestData['sla_target_at']),
            'started_at' => $requestData['started_at'] === '' ? null : $this->normalizeDateTimeInput((string) $requestData['started_at']),
            'completed_at' => $requestData['completed_at'] === '' ? null : $this->normalizeDateTimeInput((string) $requestData['completed_at']),
            'resolved_at' => $completed
                ? ($alreadyCompleted ? (string) $existingRequest['resolved_at'] : date('Y-m-d H:i:s'))
                : null,
            'labor_hours' => $requestData['labor_hours'] === '' ? null : $requestData['labor_hours'],
            'estimated_cost' => $requestData['estimated_cost'] === '' ? null : $requestData['estimated_cost'],
            'actual_cost' => $requestData['actual_cost'] === '' ? null : $requestData['actual_cost'],
            'resolution_notes' => $completed ? $requestData['resolution_notes'] : null,
        ];
    }

    /**
     * Normalizes the query filters used by the maintenance queue.
     *
     * @return array<string, int|string|null>
     */
    private function queueFilters(): array
    {
        $activeOnly = $this->request->getGet('active_only');

        return [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'priority' => trim((string) $this->request->getGet('priority')),
            'assigned_department_id' => $this->nullableInteger($this->request->getGet('assigned_department_id')),
            'active_only' => $activeOnly === null ? '1' : ($activeOnly === '1' ? '1' : ''),
        ];
    }

    private function normalizeDateTimeInput(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);

            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
