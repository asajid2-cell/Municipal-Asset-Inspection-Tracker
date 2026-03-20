<?php

namespace App\Controllers;

use App\Libraries\AssetVersionRecorder;
use App\Libraries\AssetImportService;
use App\Libraries\OpenDataSyncService;
use App\Libraries\SyncJobManager;
use App\Models\ActivityLogModel;
use App\Models\AssetCategoryModel;
use App\Models\AssetModel;
use App\Models\DepartmentModel;
use App\Models\AttachmentModel;
use App\Models\AssetVersionModel;
use App\Models\InspectionModel;
use App\Models\LinearAssetModel;
use App\Models\MaintenanceRequestModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Handles the asset inventory screens used in the MVP workflow.
 */
class Assets extends BaseController
{
    private const PER_PAGE = 5;
    private const FULL_TABLE_DEFAULT_PER_PAGE = 250;
    private const MAP_CENTER_LAT = 53.5461;
    private const MAP_CENTER_LNG = -113.4938;
    private const MAP_ZOOM = 11;

    /**
     * @var list<string>
     */
    private const STATUS_OPTIONS = AssetModel::STATUS_OPTIONS;

    public function index(): ResponseInterface|string
    {
        $filters    = $this->inventoryFilters();
        $assetModel = new AssetModel();

        if (($this->request->getGet('export') ?? '') === 'csv') {
            return $this->downloadCsv($assetModel->forInventoryList($filters)->findAll());
        }

        $assets = $assetModel->forInventoryList($filters)->paginate(self::PER_PAGE);
        $pager  = $assetModel->pager->only(['q', 'location', 'category_id', 'department_id', 'status', 'overdue', 'source_dataset', 'geometry_family', 'sort']);
        $summary = $assetModel->filteredSummary($filters);

        return view('assets/index', [
            'pageTitle' => 'Asset Inventory',
            'activeNav' => 'assets',
            'assets'    => $assets,
            'pager'     => $pager,
            'filters'   => $filters,
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'categories' => (new AssetCategoryModel())->orderBy('name', 'ASC')->findAll(),
            'statuses' => AssetModel::STATUS_OPTIONS,
            'sortOptions' => $this->sortOptions(),
            'csvUrl' => site_url('assets') . '?' . http_build_query(array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null) + ['export' => 'csv']),
            'importTemplateUrl' => site_url('assets/import-template'),
            'resultTotal' => $pager->getTotal(),
            'importReport' => session()->getFlashdata('importReport'),
            'syncReport' => session()->getFlashdata('syncReport'),
            'openDataSources' => $this->openDataSources(),
            'geometryFilters' => $this->geometryFilters(),
            'summary' => $summary,
        ]);
    }

    public function show(int $assetId): string
    {
        $asset = (new AssetModel())->findDetailedAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        $inspectionHistory = (new InspectionModel())->forAssetHistory($assetId);
        $inspectionIds = array_map(
            static fn (array $inspection): int => (int) $inspection['id'],
            $inspectionHistory
        );

        return view('assets/show', [
            'pageTitle' => $asset['asset_code'] . ' - Asset Detail',
            'activeNav' => 'assets',
            'asset'     => $asset,
            'inspectionHistory' => $inspectionHistory,
            'inspectionAttachments' => (new AttachmentModel())->groupedByInspectionIds($inspectionIds),
            'maintenanceHistory' => (new MaintenanceRequestModel())->forAssetHistory($assetId),
            'versionHistory' => (new AssetVersionModel())->forAsset($assetId, 12),
            'linearAsset' => (new LinearAssetModel())->findForAsset($assetId),
        ]);
    }

    public function full(): string
    {
        $filters = $this->inventoryFilters();
        $assetModel = new AssetModel();
        $perPage = $this->fullTablePerPage();
        $assets = $assetModel->forInventoryList($filters)->paginate($perPage, 'full');
        $summary = $assetModel->filteredSummary($filters);
        $pager = $assetModel->pager->only([
            'q',
            'location',
            'category_id',
            'department_id',
            'status',
            'overdue',
            'source_dataset',
            'geometry_family',
            'sort',
            'per_page',
        ]);

        return view('assets/full', [
            'pageTitle' => 'Full Asset Inventory',
            'activeNav' => 'assets',
            'assets' => $assets,
            'filters' => $filters,
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'categories' => (new AssetCategoryModel())->orderBy('name', 'ASC')->findAll(),
            'statuses' => AssetModel::STATUS_OPTIONS,
            'sortOptions' => $this->sortOptions(),
            'pager' => $pager,
            'resultTotal' => $pager->getTotal(),
            'currentPerPage' => $perPage,
            'perPageOptions' => $this->fullTablePerPageOptions(),
            'backToPagedUrl' => site_url('assets') . $this->filterQueryString($filters),
            'mapViewUrl' => site_url('assets/map') . $this->filterQueryString($filters),
            'openDataSources' => $this->openDataSources(),
            'geometryFilters' => $this->geometryFilters(),
            'summary' => $summary,
        ]);
    }

    public function map(): string
    {
        $filters = $this->inventoryFilters();

        return view('assets/map', [
            'pageTitle' => 'Map Inventory',
            'activeNav' => 'assets',
            'filters' => $filters,
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'categories' => (new AssetCategoryModel())->orderBy('name', 'ASC')->findAll(),
            'statuses' => AssetModel::STATUS_OPTIONS,
            'sortOptions' => $this->sortOptions(),
            'initialCenter' => [
                'lat' => self::MAP_CENTER_LAT,
                'lng' => self::MAP_CENTER_LNG,
            ],
            'initialZoom' => self::MAP_ZOOM,
            'mapApiUrl' => site_url('api/assets/map'),
            'tableViewUrl' => site_url('assets') . $this->filterQueryString($filters),
            'fullViewUrl' => site_url('assets/full') . $this->filterQueryString($filters),
            'openDataSources' => $this->openDataSources(),
            'geometryFilters' => $this->geometryFilters(),
            'mapFeatureLimit' => AssetModel::MAP_RENDER_LIMIT,
        ]);
    }

    public function createForm(): string
    {
        return view('assets/form', [
            'pageTitle'   => 'Add Asset',
            'activeNav'   => 'assets',
            'formAction'  => site_url('assets'),
            'submitLabel' => 'Create asset',
            'asset'       => [],
            'errors'      => session()->getFlashdata('errors') ?? [],
            ...$this->assetFormLookups(),
        ]);
    }

    public function create()
    {
        $assetData = $this->assetPayload();
        $errors    = $this->validateAssetPayload($assetData);

        if ($errors !== []) {
            return redirect()
                ->to(site_url('assets/new'))
                ->withInput()
                ->with('errors', $errors);
        }

        $assetModel = new AssetModel();
        $assetModel->insert($assetData + [
            'organization_id' => $this->currentOrganizationId(),
            'created_by' => $this->currentUserId(),
            'updated_by' => $this->currentUserId(),
        ]);
        $assetId = (int) $assetModel->getInsertID();
        (new AssetVersionRecorder())->recordByAssetId($assetId, 'manual_created', $this->currentUserId(), 'Created through asset form.');

        (new ActivityLogModel())->recordEntry(
            $this->currentUserId(),
            'asset',
            $assetId,
            'created',
            'Created asset ' . $assetData['asset_code'] . '.',
            [
                'asset_code' => $assetData['asset_code'],
                'status' => $assetData['status'],
            ]
        );

        return redirect()
            ->to(site_url('assets/' . $assetId))
            ->with('success', 'Asset created.');
    }

    public function editForm(int $assetId): string
    {
        $asset = (new AssetModel())->findDetailedAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        return view('assets/form', [
            'pageTitle'   => 'Edit Asset',
            'activeNav'   => 'assets',
            'formAction'  => site_url('assets/' . $assetId),
            'submitLabel' => 'Save changes',
            'asset'       => $asset,
            'errors'      => session()->getFlashdata('errors') ?? [],
            ...$this->assetFormLookups(),
        ]);
    }

    public function update(int $assetId)
    {
        $assetModel = new AssetModel();
        $asset      = $assetModel->findDetailedAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        $assetData = $this->assetPayload();
        $errors    = $this->validateAssetPayload($assetData, $assetId);

        if ($errors !== []) {
            return redirect()
                ->to(site_url('assets/' . $assetId . '/edit'))
                ->withInput()
                ->with('errors', $errors);
        }

        $assetModel->update($assetId, $assetData + [
            'updated_by' => $this->currentUserId(),
        ]);
        (new AssetVersionRecorder())->recordByAssetId($assetId, 'manual_updated', $this->currentUserId(), 'Updated through asset form.');

        (new ActivityLogModel())->recordEntry(
            $this->currentUserId(),
            'asset',
            $assetId,
            'updated',
            'Updated asset ' . $asset['asset_code'] . '.',
            [
                'asset_code' => $asset['asset_code'],
                'status' => $assetData['status'],
            ]
        );

        return redirect()
            ->to(site_url('assets/' . $assetId))
            ->with('success', 'Asset updated.');
    }

    public function archive(int $assetId)
    {
        $assetModel = new AssetModel();
        $asset      = $assetModel->find($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        $assetModel->delete($assetId);
        (new AssetVersionRecorder())->recordByAssetId($assetId, 'archived', $this->currentUserId(), 'Asset archived from inventory.');

        (new ActivityLogModel())->recordEntry(
            $this->currentUserId(),
            'asset',
            $assetId,
            'archived',
            'Archived asset ' . $asset['asset_code'] . '.',
            [
                'asset_code' => $asset['asset_code'],
            ]
        );

        return redirect()
            ->to(site_url('assets'))
            ->with('success', 'Asset archived.');
    }

    public function import()
    {
        $file = $this->request->getFile('asset_import');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()
                ->to(site_url('assets'))
                ->with('warning', 'Choose a CSV file to import.');
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return redirect()
                ->to(site_url('assets'))
                ->with('warning', 'The CSV file could not be uploaded.');
        }

        if (strtolower($file->getClientExtension()) !== 'csv') {
            return redirect()
                ->to(site_url('assets'))
                ->with('warning', 'Asset imports must use a CSV file.');
        }

        $report = (new AssetImportService())->import($file->getTempName(), $this->currentUserId());

        return $this->redirectWithImportReport($report);
    }

    public function syncOpenData()
    {
        $sourceKey = trim((string) $this->request->getPost('source_key'));
        $limit = $this->nullableInteger($this->request->getPost('limit')) ?? 0;
        $syncAll = $this->request->getPost('sync_all') === '1';

        if ($sourceKey === '') {
            return redirect()
                ->to(site_url('assets'))
                ->with('warning', 'Choose a public data source before syncing.');
        }

        try {
            $job = (new SyncJobManager())->run($this->currentOrganizationId(), $sourceKey, $limit, $this->currentUserId(), $syncAll);
        } catch (\Throwable $exception) {
            return redirect()
                ->to(site_url('assets'))
                ->with('warning', $exception->getMessage());
        }

        $redirect = redirect()
            ->to(site_url('assets'))
            ->with('syncReport', $job);

        $summary = $job['imported_count'] . ' imported, '
            . $job['updated_count'] . ' updated, '
            . $job['restored_count'] . ' restored, '
            . $job['unchanged_count'] . ' unchanged.';

        if ((int) $job['fetched_count'] === 0) {
            return $redirect->with('warning', 'The selected source returned no records.');
        }

        if ((int) $job['imported_count'] + (int) $job['updated_count'] + (int) $job['restored_count'] > 0) {
            $redirect->with('success', 'Public data sync complete: ' . $summary);
        }

        if ((string) ($job['error_message'] ?? '') !== '') {
            $redirect->with('warning', (string) $job['error_message']);
        }

        return $redirect;
    }

    public function importTemplate(): ResponseInterface
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, AssetImportService::templateHeaders());
        fputcsv($handle, [
            'PARK-BENCH-120',
            'Trail Rest Bench 120',
            'PARKS',
            'Park Bench',
            'Riverfront Park west path',
            'Active',
            '2026-03-20',
            '53.5471000',
            '-113.4889000',
            'Imported from spreadsheet planning.',
        ]);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="asset-import-template.csv"')
            ->setBody((string) $csv);
    }

    /**
     * Supplies lookup options shared by the create and edit forms.
     *
     * @return array<string, array<int, array<string, mixed>>|list<string>>
     */
    private function assetFormLookups(): array
    {
        return [
            'departments' => (new DepartmentModel())->orderBy('name', 'ASC')->findAll(),
            'categories'  => (new AssetCategoryModel())->orderBy('name', 'ASC')->findAll(),
            'statuses'    => self::STATUS_OPTIONS,
        ];
    }

    /**
     * Normalizes and trims request input before validation and persistence.
     *
     * @return array<string, int|string|null>
     */
    private function assetPayload(): array
    {
        return [
            'asset_code' => strtoupper(trim((string) $this->request->getPost('asset_code'))),
            'department_id' => $this->nullableInteger($this->request->getPost('department_id')),
            'category_id' => $this->nullableInteger($this->request->getPost('category_id')),
            'name' => trim((string) $this->request->getPost('name')),
            'status' => trim((string) $this->request->getPost('status')),
            'location_text' => trim((string) $this->request->getPost('location_text')),
            'installed_on' => $this->nullableString($this->request->getPost('installed_on')),
            'latitude' => $this->nullableString($this->request->getPost('latitude')),
            'longitude' => $this->nullableString($this->request->getPost('longitude')),
            'condition_score' => $this->nullableInteger($this->request->getPost('condition_score')),
            'criticality_score' => $this->nullableInteger($this->request->getPost('criticality_score')),
            'replacement_cost' => $this->nullableString($this->request->getPost('replacement_cost')),
            'service_level' => $this->nullableString($this->request->getPost('service_level')),
            'notes' => $this->nullableString($this->request->getPost('notes')),
        ];
    }

    /**
     * Runs form validation and the asset code uniqueness check.
     *
     * @param array<string, int|string|null> $assetData
     *
     * @return array<string, string>
     */
    private function validateAssetPayload(array $assetData, ?int $assetId = null): array
    {
        $validation = service('validation');
        $validation->setRules([
            'asset_code' => 'required|max_length[60]',
            'department_id' => 'required|is_natural_no_zero',
            'category_id' => 'required|is_natural_no_zero',
            'name' => 'required|max_length[190]',
            'status' => 'required|in_list[' . implode(',', self::STATUS_OPTIONS) . ']',
            'location_text' => 'required|max_length[255]',
            'installed_on' => 'permit_empty|valid_date[Y-m-d]',
            'latitude' => 'permit_empty|decimal',
            'longitude' => 'permit_empty|decimal',
            'condition_score' => 'permit_empty|is_natural|less_than_equal_to[100]',
            'criticality_score' => 'permit_empty|is_natural|less_than_equal_to[100]',
            'replacement_cost' => 'permit_empty|decimal',
            'service_level' => 'permit_empty|max_length[60]',
            'notes' => 'permit_empty|max_length[4000]',
        ]);

        if (! $validation->run($assetData)) {
            /** @var array<string, string> $errors */
            $errors = $validation->getErrors();

            return $errors;
        }

        if ((new DepartmentModel())->find((int) $assetData['department_id']) === null) {
            return ['department_id' => 'Select a valid department.'];
        }

        if ((new AssetCategoryModel())->find((int) $assetData['category_id']) === null) {
            return ['category_id' => 'Select a valid category.'];
        }

        if ((new AssetModel())->assetCodeExists((string) $assetData['asset_code'], $assetId)) {
            return ['asset_code' => 'Asset code must be unique.'];
        }

        return [];
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

    /**
     * Normalizes the query filters used by the operations inventory screen.
     *
     * @return array<string, int|string|null>
     */
    private function inventoryFilters(): array
    {
        return [
            'q' => trim((string) $this->request->getGet('q')),
            'location' => trim((string) $this->request->getGet('location')),
            'category_id' => $this->nullableInteger($this->request->getGet('category_id')),
            'department_id' => $this->nullableInteger($this->request->getGet('department_id')),
            'status' => trim((string) $this->request->getGet('status')),
            'overdue' => $this->request->getGet('overdue') === '1' ? '1' : '',
            'source_dataset' => trim((string) $this->request->getGet('source_dataset')),
            'geometry_family' => trim((string) $this->request->getGet('geometry_family')),
            'organization_id' => $this->currentOrganizationId(),
            'sort' => in_array((string) $this->request->getGet('sort'), array_keys($this->sortOptions()), true)
                ? (string) $this->request->getGet('sort')
                : 'asset_code_asc',
        ];
    }

    /**
     * Returns sort choices exposed on the asset inventory screen.
     *
     * @return array<string, string>
     */
    private function sortOptions(): array
    {
        return [
            'asset_code_asc' => 'Asset code',
            'next_due_asc' => 'Next inspection due',
            'last_inspected_desc' => 'Newest inspection',
            'status_asc' => 'Status',
        ];
    }

    /**
     * Returns open-data source options for the sync form.
     *
     * @return array<string, array<string, int|string>>
     */
    private function openDataSources(): array
    {
        /** @var OpenDataSyncService $service */
        $service = service('openDataSyncService');

        return $service->availableSources();
    }

    /**
     * Returns geometry-family filter choices used by inventory and map screens.
     *
     * @return array<string, string>
     */
    private function geometryFilters(): array
    {
        return [
            'point' => 'Point assets',
            'line' => 'Line assets',
            'polygon' => 'Area assets',
        ];
    }

    /**
     * Returns supported page sizes for the large-table inventory screen.
     *
     * @return list<int>
     */
    private function fullTablePerPageOptions(): array
    {
        return [100, 250, 500, 1000];
    }

    private function fullTablePerPage(): int
    {
        $requested = $this->nullableInteger($this->request->getGet('per_page')) ?? self::FULL_TABLE_DEFAULT_PER_PAGE;

        return in_array($requested, $this->fullTablePerPageOptions(), true)
            ? $requested
            : self::FULL_TABLE_DEFAULT_PER_PAGE;
    }

    /**
     * Builds a consistent redirect after CSV import attempts.
     *
     * @param array<string, int|array<int, string>> $report
     */
    private function redirectWithImportReport(array $report)
    {
        $importedCount = (int) $report['imported_count'];
        /** @var array<int, string> $errors */
        $errors = $report['errors'];
        $redirect = redirect()
            ->to(site_url('assets'))
            ->with('importReport', $report);

        if ($importedCount > 0) {
            $redirect->with('success', $importedCount . ' asset' . ($importedCount === 1 ? '' : 's') . ' imported.');
        } elseif ($errors === []) {
            $redirect->with('warning', 'The CSV did not contain any importable rows.');
        }

        if ($errors !== []) {
            $redirect->with('warning', count($errors) . ' row' . (count($errors) === 1 ? '' : 's') . ' could not be imported.');
        }

        return $redirect;
    }

    /**
     * Builds a query string from the currently active inventory filters.
     *
     * @param array<string, int|string|null> $filters
     */
    private function filterQueryString(array $filters): string
    {
        $query = http_build_query(array_filter(
            $filters,
            static fn (mixed $value): bool => $value !== '' && $value !== null
        ));

        return $query === '' ? '' : '?' . $query;
    }

    /**
     * Streams the filtered inventory as CSV for manager reporting.
     *
     * @param array<int, array<string, mixed>> $assets
     */
    private function downloadCsv(array $assets): ResponseInterface
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Asset Code',
            'Name',
            'Category',
            'Department',
            'Status',
            'Location',
            'Last Inspected',
            'Next Inspection Due',
        ]);

        foreach ($assets as $asset) {
            fputcsv($handle, [
                $asset['asset_code'],
                $asset['name'],
                $asset['category_name'],
                $asset['department_name'],
                $asset['status'],
                $asset['location_text'],
                $asset['last_inspected_at'],
                $asset['next_inspection_due_at'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="asset-inventory.csv"')
            ->setBody((string) $csv);
    }
}
