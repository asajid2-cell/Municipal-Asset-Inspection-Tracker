<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AssetModel;
use App\Models\InspectionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Provides authenticated JSON reads for external integrations and reporting tools.
 */
class Assets extends BaseController
{
    public function index()
    {
        $filters = $this->inventoryFilters();
        $assetModel = new AssetModel();
        $perPage = $this->requestedPerPage();
        $page = max(1, $this->nullableInteger($this->request->getGet('page')) ?? 1);
        $totalCount = $assetModel->inventoryCount($filters);
        $assets = $assetModel->forInventoryList($filters)->paginate($perPage, 'api');

        return $this->response->setJSON([
            'data' => $assets,
            'meta' => [
                'filters' => $filters,
                'page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'page_count' => $totalCount === 0 ? 0 : (int) ceil($totalCount / $perPage),
                'returned_count' => count($assets),
            ],
        ]);
    }

    public function map()
    {
        $filters = $this->inventoryFilters() + [
            'north' => $this->request->getGet('north'),
            'south' => $this->request->getGet('south'),
            'east' => $this->request->getGet('east'),
            'west' => $this->request->getGet('west'),
        ];

        $assetModel = new AssetModel();
        $assets = $assetModel->forMapView($filters);
        $viewportCount = $assetModel->mapCount($filters);

        return $this->response->setJSON([
            'data' => $assets,
            'meta' => [
                'filters' => $filters,
                'total_filtered_count' => (new AssetModel())->inventoryCount($filters),
                'mapped_count' => count($assets),
                'viewport_total_count' => $viewportCount,
                'feature_limit' => AssetModel::MAP_RENDER_LIMIT,
                'truncated' => $viewportCount > count($assets),
            ],
        ]);
    }

    public function show(int $assetId)
    {
        $asset = (new AssetModel())->findApiAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        return $this->response->setJSON([
            'data' => $asset,
        ]);
    }

    public function inspections(int $assetId)
    {
        $asset = (new AssetModel())->findApiAsset($assetId);

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound('Asset not found.');
        }

        return $this->response->setJSON([
            'data' => (new InspectionModel())->forApiAssetHistory($assetId),
            'meta' => [
                'asset_id' => $assetId,
                'asset_code' => $asset['asset_code'],
            ],
        ]);
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    /**
     * Parses shared inventory filters for API reads.
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
            'sort' => (string) ($this->request->getGet('sort') ?: 'asset_code_asc'),
        ];
    }

    private function requestedPerPage(): int
    {
        $requested = $this->nullableInteger($this->request->getGet('per_page')) ?? AssetModel::API_DEFAULT_PER_PAGE;

        if ($requested < 1) {
            return AssetModel::API_DEFAULT_PER_PAGE;
        }

        return min($requested, AssetModel::API_MAX_PER_PAGE);
    }
}
