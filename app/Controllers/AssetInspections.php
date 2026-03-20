<?php

namespace App\Controllers;

use App\Libraries\WorkflowRuleEngine;
use App\Libraries\AssetVersionRecorder;
use App\Models\ActivityLogModel;
use App\Models\AttachmentModel;
use App\Models\AssetModel;
use App\Models\DepartmentModel;
use App\Models\InspectionModel;
use App\Models\MaintenanceRequestModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Handles the inspection workflow nested under asset records.
 */
class AssetInspections extends BaseController
{
    public function createForm(int $assetId): string
    {
        $asset = $this->assetOrFail($assetId);

        return view('inspections/form', [
            'pageTitle' => 'Log Inspection',
            'activeNav' => 'assets',
            'asset' => $asset,
            'inspectors' => (new UserModel())->inspectionStaff(),
            'statuses' => AssetModel::STATUS_OPTIONS,
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'requestPriorities' => MaintenanceRequestModel::PRIORITY_OPTIONS,
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function create(int $assetId)
    {
        $asset = $this->assetOrFail($assetId);
        $inspectionData = $this->inspectionPayload();
        $errors = $this->validateInspectionPayload($inspectionData);
        $attachmentFiles = $this->attachmentFiles();
        $attachmentErrors = $this->validateAttachmentFiles($attachmentFiles);

        if ($attachmentErrors !== []) {
            $errors = array_merge($errors, $attachmentErrors);
        }

        if ($errors !== []) {
            return redirect()
                ->to(site_url('assets/' . $assetId . '/inspections/new'))
                ->withInput()
                ->with('errors', $errors);
        }

        $normalizedInspectedAt = $this->normalizeDateTimeInput((string) $inspectionData['inspected_at']);

        if ($normalizedInspectedAt === null) {
            return redirect()
                ->to(site_url('assets/' . $assetId . '/inspections/new'))
                ->withInput()
                ->with('errors', ['inspected_at' => 'Enter a valid inspection date and time.']);
        }

        $nextDueAt = $this->nextDueAt($normalizedInspectedAt, (int) $asset['inspection_interval_days']);
        $previousStatus = (string) $asset['status'];

        $db = db_connect('default');
        $db->transException(true)->transStart();
        $attachmentWarnings = [];

        try {
            $inspectionModel = new InspectionModel();
            $inspectionModel->insert([
                'organization_id' => $this->currentOrganizationId(),
                'asset_id' => $assetId,
                'inspector_id' => $inspectionData['inspector_id'],
                'inspected_at' => $normalizedInspectedAt,
                'condition_rating' => $inspectionData['condition_rating'],
                'result_status' => $inspectionData['result_status'],
                'notes' => $inspectionData['notes'],
                'next_due_at' => $nextDueAt,
            ]);

            $inspectionId = (int) $inspectionModel->getInsertID();

            $assetModel = new AssetModel();
            $assetModel->applyInspectionOutcome(
                $assetId,
                (string) $inspectionData['result_status'],
                $normalizedInspectedAt,
                $nextDueAt,
                (int) $inspectionData['inspector_id']
            );
            (new AssetVersionRecorder())->recordByAssetId($assetId, 'inspection_updated', $this->currentUserId(), 'Asset updated from inspection outcome.');

            $activityLog = new ActivityLogModel();
            $activityLog->recordEntry(
                $this->currentUserId(),
                'inspection',
                $inspectionId,
                'created',
                'Logged inspection for ' . $asset['asset_code'] . ' with ' . $inspectionData['result_status'] . ' outcome.',
                [
                    'asset_id' => $assetId,
                    'asset_code' => $asset['asset_code'],
                    'next_due_at' => $nextDueAt,
                    'condition_rating' => $inspectionData['condition_rating'],
                ]
            );

            if ($inspectionData['create_request'] === '1') {
                $this->createMaintenanceRequest(
                    $asset,
                    $inspectionData,
                    $inspectionId,
                    $normalizedInspectedAt,
                    $activityLog
                );
            }

            $assetAction = $previousStatus === $inspectionData['result_status'] ? 'inspection_logged' : 'status_changed';
            $assetSummary = $previousStatus === $inspectionData['result_status']
                ? 'Recorded new inspection for ' . $asset['asset_code'] . '.'
                : 'Changed ' . $asset['asset_code'] . ' from ' . $previousStatus . ' to ' . $inspectionData['result_status'] . ' after inspection.';

            $activityLog->recordEntry(
                $this->currentUserId(),
                'asset',
                $assetId,
                $assetAction,
                $assetSummary,
                [
                    'previous_status' => $previousStatus,
                    'new_status' => $inspectionData['result_status'],
                    'inspected_at' => $normalizedInspectedAt,
                    'next_due_at' => $nextDueAt,
                ]
            );

            (new WorkflowRuleEngine())->handleInspectionLogged(
                $asset,
                [
                    'id' => $inspectionId,
                    'result_status' => $inspectionData['result_status'],
                    'inspected_at' => $normalizedInspectedAt,
                    'next_due_at' => $nextDueAt,
                    'condition_rating' => $inspectionData['condition_rating'],
                    'notes' => $inspectionData['notes'],
                ],
                $this->currentUserId()
            );

            if ($attachmentFiles !== []) {
                $this->storeAttachments($inspectionId, $attachmentFiles, $activityLog);
                $attachmentWarnings[] = count($attachmentFiles) . ' attachment' . (count($attachmentFiles) === 1 ? '' : 's') . ' uploaded.';
            }

            $db->transComplete();
        } catch (Throwable) {
            $db->transRollback();

            return redirect()
                ->to(site_url('assets/' . $assetId . '/inspections/new'))
                ->withInput()
                ->with('warning', 'Inspection could not be saved.');
        }

        return redirect()
            ->to(site_url('assets/' . $assetId))
            ->with('success', trim('Inspection logged. ' . implode(' ', $attachmentWarnings)));
    }

    /**
     * Loads the current asset record together with category metadata.
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
     * Normalizes the inspection form input for validation and persistence.
     *
     * @return array<string, int|string|null>
     */
    private function inspectionPayload(): array
    {
        return [
            'inspector_id' => $this->nullableInteger($this->request->getPost('inspector_id')),
            'inspected_at' => trim((string) $this->request->getPost('inspected_at')),
            'condition_rating' => $this->nullableInteger($this->request->getPost('condition_rating')),
            'result_status' => trim((string) $this->request->getPost('result_status')),
            'notes' => $this->nullableString($this->request->getPost('notes')),
            'create_request' => $this->request->getPost('create_request') === '1' ? '1' : '',
            'request_title' => trim((string) $this->request->getPost('request_title')),
            'request_priority' => trim((string) $this->request->getPost('request_priority')),
            'request_due_at' => trim((string) $this->request->getPost('request_due_at')),
            'request_description' => $this->nullableString($this->request->getPost('request_description')),
            'request_assigned_department_id' => $this->nullableInteger($this->request->getPost('request_assigned_department_id')),
        ];
    }

    /**
     * Validates the inspection fields before the transaction starts.
     *
     * @param array<string, int|string|null> $inspectionData
     *
     * @return array<string, string>
     */
    private function validateInspectionPayload(array $inspectionData): array
    {
        $validation = service('validation');
        $validation->setRules([
            'inspector_id' => 'required|is_natural_no_zero',
            'inspected_at' => 'required',
            'condition_rating' => 'required|is_natural_no_zero|less_than_equal_to[5]',
            'result_status' => 'required|in_list[' . implode(',', AssetModel::STATUS_OPTIONS) . ']',
            'notes' => 'permit_empty|max_length[4000]',
        ]);

        if (! $validation->run($inspectionData)) {
            /** @var array<string, string> $errors */
            $errors = $validation->getErrors();

            return $errors;
        }

        if ((new UserModel())->find((int) $inspectionData['inspector_id']) === null) {
            return ['inspector_id' => 'Select a valid inspector.'];
        }

        if ($inspectionData['create_request'] === '1') {
            if (! in_array((string) $inspectionData['result_status'], ['Needs Repair', 'Out of Service'], true)) {
                return ['create_request' => 'Follow-up requests can only be created for service issues.'];
            }

            $requestValidation = service('validation');
            $requestValidation->setRules([
                'request_title' => 'required|max_length[190]',
                'request_priority' => 'required|in_list[' . implode(',', MaintenanceRequestModel::PRIORITY_OPTIONS) . ']',
                'request_due_at' => 'permit_empty',
                'request_description' => 'permit_empty|max_length[4000]',
                'request_assigned_department_id' => 'required|is_natural_no_zero',
            ]);

            if (! $requestValidation->run($inspectionData)) {
                /** @var array<string, string> $errors */
                $errors = $requestValidation->getErrors();

                return $errors;
            }

            if ((new DepartmentModel())->find((int) $inspectionData['request_assigned_department_id']) === null) {
                return ['request_assigned_department_id' => 'Select a valid assigned department.'];
            }

            if (
                $inspectionData['request_due_at'] !== ''
                && $this->normalizeDateTimeInput((string) $inspectionData['request_due_at']) === null
            ) {
                return ['request_due_at' => 'Enter a valid due date and time.'];
            }
        }

        return [];
    }

    /**
     * Persists a linked maintenance request when a failed inspection needs follow-up work.
     *
     * @param array<string, mixed> $asset
     * @param array<string, int|string|null> $inspectionData
     */
    private function createMaintenanceRequest(
        array $asset,
        array $inspectionData,
        int $inspectionId,
        string $inspectedAt,
        ActivityLogModel $activityLog
    ): void {
        $dueAt = $inspectionData['request_due_at'] === ''
            ? $this->defaultRequestDueAt($inspectedAt, (string) $inspectionData['result_status'])
            : $this->normalizeDateTimeInput((string) $inspectionData['request_due_at']);

        $description = $inspectionData['request_description'] ?? $inspectionData['notes'];

        $maintenanceModel = new MaintenanceRequestModel();
        $maintenanceModel->insert([
            'organization_id' => $this->currentOrganizationId(),
            'asset_id' => $asset['id'],
            'inspection_id' => $inspectionId,
            'opened_by' => $inspectionData['inspector_id'],
            'assigned_department_id' => $inspectionData['request_assigned_department_id'],
            'title' => $inspectionData['request_title'],
            'description' => $description,
            'priority' => $inspectionData['request_priority'],
            'status' => 'Open',
            'due_at' => $dueAt,
            'sla_target_at' => $dueAt,
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);

        $requestId = (int) $maintenanceModel->getInsertID();

        $activityLog->recordEntry(
            $this->currentUserId(),
            'maintenance_request',
            $requestId,
            'created',
            'Opened follow-up maintenance request for ' . $asset['asset_code'] . '.',
            [
                'asset_id' => $asset['id'],
                'asset_code' => $asset['asset_code'],
                'inspection_id' => $inspectionId,
                'priority' => $inspectionData['request_priority'],
                'status' => 'Open',
            ]
        );
    }

    /**
     * Returns uploaded inspection files, excluding empty slots.
     *
     * @return array<int, UploadedFile>
     */
    private function attachmentFiles(): array
    {
        $files = $this->request->getFileMultiple('attachments') ?? [];

        return array_values(array_filter(
            $files,
            static fn (UploadedFile $file): bool => $file->getError() !== UPLOAD_ERR_NO_FILE
        ));
    }

    /**
     * Validates file extension and size before the inspection transaction runs.
     *
     * @param array<int, UploadedFile> $files
     *
     * @return array<string, string>
     */
    private function validateAttachmentFiles(array $files): array
    {
        foreach ($files as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                return ['attachments' => 'One or more attachments could not be uploaded.'];
            }

            $extension = strtolower($file->getClientExtension());

            if (! in_array($extension, AttachmentModel::ALLOWED_EXTENSIONS, true)) {
                return ['attachments' => 'Attachments must be JPG, PNG, or PDF files.'];
            }

            if ($file->getSize() > AttachmentModel::MAX_FILE_SIZE_BYTES) {
                return ['attachments' => 'Each attachment must be 5 MB or smaller.'];
            }
        }

        return [];
    }

    /**
     * Stores attachment metadata and files after a successful inspection insert.
     *
     * @param array<int, UploadedFile> $files
     */
    private function storeAttachments(int $inspectionId, array $files, ActivityLogModel $activityLog): void
    {
        $attachmentModel = new AttachmentModel();
        $relativeFolder = 'inspection-attachments/' . $inspectionId;
        $absoluteFolder = WRITEPATH . 'uploads/' . $relativeFolder;

        if (! is_dir($absoluteFolder)) {
            mkdir($absoluteFolder, 0777, true);
        }

        foreach ($files as $file) {
            $extension = strtolower($file->getClientExtension());
            $storedName = bin2hex(random_bytes(12)) . '.' . $extension;
            $relativePath = $relativeFolder . '/' . $storedName;
            $absolutePath = WRITEPATH . 'uploads/' . $relativePath;

            $this->moveAttachmentFile($file, $absoluteFolder, $absolutePath, $storedName);

            $attachmentModel->insert([
                'inspection_id' => $inspectionId,
                'maintenance_request_id' => null,
                'uploaded_by' => $this->currentUserId(),
                'original_name' => $file->getClientName(),
                'storage_path' => $relativePath,
                'mime_type' => $file->getClientMimeType(),
                'file_size_bytes' => filesize($absolutePath),
                'created_at' => date('Y-m-d H:i:s'),
            ], false);

            $attachmentId = (int) $attachmentModel->getInsertID();

            $activityLog->recordEntry(
                $this->currentUserId(),
                'attachment',
                $attachmentId,
                'uploaded',
                'Uploaded inspection attachment ' . $file->getClientName() . '.',
                [
                    'inspection_id' => $inspectionId,
                    'original_name' => $file->getClientName(),
                ]
            );
        }
    }

    private function moveAttachmentFile(UploadedFile $file, string $absoluteFolder, string $absolutePath, string $storedName): void
    {
        if (ENVIRONMENT === 'testing' && ! is_uploaded_file($file->getTempName())) {
            if (! copy($file->getTempName(), $absolutePath)) {
                throw new RuntimeException('Attachment could not be copied into the testing upload directory.');
            }

            return;
        }

        $file->move($absoluteFolder, $storedName, true);
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

    private function defaultRequestDueAt(string $inspectedAt, string $resultStatus): string
    {
        $date = new DateTimeImmutable($inspectedAt);
        $modifier = $resultStatus === 'Out of Service' ? '+2 days' : '+7 days';

        return $date->modify($modifier)->format('Y-m-d H:i:s');
    }

    private function nextDueAt(string $inspectedAt, int $intervalDays): string
    {
        $date = new DateTimeImmutable($inspectedAt);

        return $date->modify('+' . $intervalDays . ' days')->format('Y-m-d H:i:s');
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
