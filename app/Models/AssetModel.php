<?php

namespace App\Models;

use App\Libraries\AssetVersionRecorder;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Model for municipal assets tracked in the inventory.
 */
class AssetModel extends Model
{
    public const API_DEFAULT_PER_PAGE = 250;
    public const API_MAX_PER_PAGE = 1000;
    public const MAP_RENDER_LIMIT = 1200;

    /**
     * Shared asset status values used across inventory and inspection flows.
     *
     * @var list<string>
     */
    public const STATUS_OPTIONS = [
        'Active',
        'Needs Inspection',
        'Needs Repair',
        'Out of Service',
    ];

    protected $DBGroup = 'default';
    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat = 'datetime';
    protected $allowedFields = [
        'organization_id',
        'asset_code',
        'department_id',
        'category_id',
        'name',
        'status',
        'location_text',
        'latitude',
        'longitude',
        'installed_on',
        'last_inspected_at',
        'next_inspection_due_at',
        'notes',
        'condition_score',
        'criticality_score',
        'risk_score',
        'lifecycle_state',
        'replacement_cost',
        'actual_cost_to_date',
        'service_level',
        'source_system',
        'source_dataset',
        'source_record_id',
        'source_url',
        'source_geometry_type',
        'source_geometry',
        'source_checksum',
        'created_by',
        'updated_by',
    ];

    /**
     * Applies the joined query used by the asset inventory list.
     */
    public function forInventoryList(array $filters = []): self
    {
        $this->select(
            'assets.*, departments.name AS department_name, departments.code AS department_code, '
            . 'asset_categories.name AS category_name, asset_categories.inspection_interval_days'
        )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id');
        $this->applyInventoryFiltersToQuery($this, $filters);

        $this->applyInventorySortToQuery($this, (string) ($filters['sort'] ?? 'asset_code_asc'));

        return $this;
    }

    /**
     * Returns the complete filtered inventory without pagination.
     *
     * @return array<int, array<string, mixed>>
     */
    public function inventoryCount(array $filters = []): int
    {
        return $this->forInventoryList($filters)->countAllResults();
    }

    /**
     * Returns filtered assets with coordinates for the map inventory.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forMapView(array $filters = [], int $limit = self::MAP_RENDER_LIMIT): array
    {
        $this->select(
            'assets.id, assets.asset_code, assets.name, assets.status, assets.location_text, assets.latitude, assets.longitude, '
            . 'assets.next_inspection_due_at, assets.source_dataset, assets.source_geometry_type, assets.source_geometry, '
            . 'departments.name AS department_name, departments.code AS department_code, asset_categories.name AS category_name'
        )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id');

        $this->applyInventoryFiltersToQuery($this, $filters);
        $this->applyMapBoundsToQuery($this, $filters);
        $this->where('assets.latitude IS NOT NULL', null, false)
            ->where('assets.longitude IS NOT NULL', null, false)
            ->orderBy('assets.asset_code', 'ASC')
            ->limit($limit);

        return $this->findAll();
    }

    /**
     * Counts how many mapped assets match the current viewport and filter state.
     */
    public function mapCount(array $filters = []): int
    {
        $this->select('assets.id')
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id');

        $this->applyInventoryFiltersToQuery($this, $filters);
        $this->applyMapBoundsToQuery($this, $filters);
        $this->where('assets.latitude IS NOT NULL', null, false)
            ->where('assets.longitude IS NOT NULL', null, false);

        return $this->countAllResults();
    }

    /**
     * Returns lightweight aggregate counts for the current filter set.
     *
     * @return array{
     *     mapped_count: int,
     *     point_count: int,
     *     line_count: int,
     *     polygon_count: int,
     *     unmapped_count: int,
     *     status_breakdown: array<string, int>,
     *     top_categories: array<int, array{name: string, total: int}>
     * }
     */
    public function filteredSummary(array $filters = []): array
    {
        $statusRows = $this->inventoryBaseBuilder(true)
            ->select('assets.status, COUNT(*) AS total')
            ->groupBy('assets.status');
        $this->applyInventoryFiltersToQuery($statusRows, $filters);
        $statusRows = $statusRows->get()->getResultArray();

        $statusBreakdown = array_fill_keys(self::STATUS_OPTIONS, 0);
        $total = 0;

        foreach ($statusRows as $row) {
            $status = (string) $row['status'];
            $count = (int) $row['total'];
            $statusBreakdown[$status] = $count;
            $total += $count;
        }

        $geometryBuilder = $this->inventoryBaseBuilder(true)->select(
            'SUM(CASE WHEN assets.latitude IS NOT NULL AND assets.longitude IS NOT NULL THEN 1 ELSE 0 END) AS mapped_count, '
            . 'SUM(CASE WHEN assets.source_geometry_type = "Point" OR (assets.source_geometry_type IS NULL AND assets.latitude IS NOT NULL AND assets.longitude IS NOT NULL) THEN 1 ELSE 0 END) AS point_count, '
            . 'SUM(CASE WHEN assets.source_geometry_type LIKE "%LineString" THEN 1 ELSE 0 END) AS line_count, '
            . 'SUM(CASE WHEN assets.source_geometry_type LIKE "%Polygon" THEN 1 ELSE 0 END) AS polygon_count'
        );
        $this->applyInventoryFiltersToQuery($geometryBuilder, $filters);
        $geometryRow = $geometryBuilder->get()->getRowArray() ?? [];

        $topCategoryRows = $this->inventoryBaseBuilder(true)
            ->select('asset_categories.name AS category_name, COUNT(*) AS total')
            ->groupBy('asset_categories.id')
            ->orderBy('total', 'DESC')
            ->limit(5);
        $this->applyInventoryFiltersToQuery($topCategoryRows, $filters);
        $topCategoryRows = $topCategoryRows->get()->getResultArray();

        return [
            'mapped_count' => (int) ($geometryRow['mapped_count'] ?? 0),
            'point_count' => (int) ($geometryRow['point_count'] ?? 0),
            'line_count' => (int) ($geometryRow['line_count'] ?? 0),
            'polygon_count' => (int) ($geometryRow['polygon_count'] ?? 0),
            'unmapped_count' => max(0, $total - (int) ($geometryRow['mapped_count'] ?? 0)),
            'status_breakdown' => $statusBreakdown,
            'top_categories' => array_map(
                static fn (array $row): array => [
                    'name' => (string) $row['category_name'],
                    'total' => (int) $row['total'],
                ],
                $topCategoryRows
            ),
        ];
    }

    /**
     * Returns one asset together with its lookup metadata.
     */
    public function findDetailedAsset(int $id): ?array
    {
        return $this->select(
            'assets.*, departments.name AS department_name, departments.code AS department_code, '
            . 'departments.contact_email AS department_contact_email, asset_categories.name AS category_name, '
            . 'asset_categories.description AS category_description, asset_categories.inspection_interval_days, '
            . 'asset_categories.default_status AS category_default_status'
        )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('assets.id', $id)
            ->first();
    }

    /**
     * Checks whether an asset code is already in use, including archived rows.
     */
    public function assetCodeExists(string $assetCode, ?int $ignoreId = null): bool
    {
        $query = $this->withDeleted()->select('id')->where('asset_code', $assetCode);

        if ($ignoreId !== null) {
            $query->where('id !=', $ignoreId);
        }

        return $query->first() !== null;
    }

    /**
     * Looks up an asset by its public source dataset and record identifier.
     */
    public function findBySourceReference(string $dataset, string $recordId): ?array
    {
        return $this->withDeleted()
            ->where('source_dataset', $dataset)
            ->where('source_record_id', $recordId)
            ->first();
    }

    /**
     * Finds one asset code even if the row has been archived.
     */
    public function findAnyByAssetCode(string $assetCode): ?array
    {
        return $this->withDeleted()
            ->where('asset_code', $assetCode)
            ->first();
    }

    /**
     * Inserts or updates a source-backed asset while preserving soft-delete history.
     *
     * @param array<string, int|string|null> $payload
     *
     * @return array{asset_id: int, action: string}
     */
    public function syncFromSource(array $payload, ?int $actorUserId): array
    {
        $payload['organization_id'] = (int) ($payload['organization_id'] ?? 1);
        $payload['source_checksum'] = $this->sourceChecksum($payload);
        $existing = $this->findBySourceReference(
            (string) $payload['source_dataset'],
            (string) $payload['source_record_id']
        );

        if ($existing === null) {
            $existing = $this->findAnyByAssetCode((string) $payload['asset_code']);
        }

        if ($existing === null) {
            $this->insert($payload + [
                'created_by' => $actorUserId,
                'updated_by' => $actorUserId,
            ]);
            $assetId = (int) $this->getInsertID();
            (new AssetVersionRecorder())->recordByAssetId($assetId, 'source_imported', $actorUserId, 'Imported from source sync.');

            return [
                'asset_id' => $assetId,
                'action' => 'imported',
            ];
        }

        if ($existing['deleted_at'] !== null) {
            $this->db->table($this->table)
                ->where('id', (int) $existing['id'])
                ->update($payload + [
                    'updated_by' => $actorUserId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted_at' => null,
                ]);
            (new AssetVersionRecorder())->recordByAssetId((int) $existing['id'], 'source_restored', $actorUserId, 'Restored from source sync.');

            return [
                'asset_id' => (int) $existing['id'],
                'action' => 'restored',
            ];
        }

        if ($this->payloadMatchesExisting($existing, $payload)) {
            return [
                'asset_id' => (int) $existing['id'],
                'action' => 'unchanged',
            ];
        }

        $this->update((int) $existing['id'], $payload + [
            'updated_by' => $actorUserId,
        ]);
        (new AssetVersionRecorder())->recordByAssetId((int) $existing['id'], 'source_updated', $actorUserId, 'Updated from source sync.');

        return [
            'asset_id' => (int) $existing['id'],
            'action' => 'updated',
        ];
    }

    /**
     * Applies the latest inspection outcome to the asset record.
     */
    public function applyInspectionOutcome(int $assetId, string $status, string $inspectedAt, string $nextDueAt, ?int $updatedBy = null): bool
    {
        return $this->update($assetId, [
            'status' => $status,
            'last_inspected_at' => $inspectedAt,
            'next_inspection_due_at' => $nextDueAt,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Returns status counts for the dashboard.
     *
     * @return array<string, int>
     */
    public function statusBreakdown(): array
    {
        $rows = $this->db->table($this->table)
            ->select('status, COUNT(*) AS total')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $breakdown = array_fill_keys(self::STATUS_OPTIONS, 0);

        foreach ($rows as $row) {
            $breakdown[(string) $row['status']] = (int) $row['total'];
        }

        return $breakdown;
    }

    /**
     * Counts assets with an overdue next inspection date.
     */
    public function overdueCount(): int
    {
        return $this->db->table($this->table)
            ->where('deleted_at', null)
            ->where('next_inspection_due_at <', date('Y-m-d H:i:s'))
            ->where('next_inspection_due_at IS NOT NULL', null, false)
            ->countAllResults();
    }

    /**
     * Returns the most urgent overdue assets for the dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function overdueAssets(int $limit = 5): array
    {
        return $this->db->table($this->table)
            ->select(
                'assets.id, assets.asset_code, assets.name, assets.location_text, assets.status, assets.next_inspection_due_at, '
                . 'departments.name AS department_name, asset_categories.name AS category_name'
            )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('assets.deleted_at', null)
            ->where('assets.next_inspection_due_at <', date('Y-m-d H:i:s'))
            ->where('assets.next_inspection_due_at IS NOT NULL', null, false)
            ->orderBy('assets.next_inspection_due_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Returns overdue assets with department contact information for reminder emails.
     *
     * @return array<int, array<string, mixed>>
     */
    public function overdueAssetsForNotifications(): array
    {
        return $this->db->table($this->table)
            ->select(
                'assets.id, assets.asset_code, assets.name, assets.location_text, assets.status, assets.next_inspection_due_at, '
                . 'departments.name AS department_name, departments.contact_email AS department_contact_email, '
                . 'asset_categories.name AS category_name'
            )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('assets.deleted_at', null)
            ->where('assets.next_inspection_due_at <', date('Y-m-d H:i:s'))
            ->where('assets.next_inspection_due_at IS NOT NULL', null, false)
            ->orderBy('assets.next_inspection_due_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Returns a stable projection used by the JSON API list endpoint.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forApiList(array $filters = []): array
    {
        return $this->forInventoryList($filters)
            ->select(
                'assets.id, assets.asset_code, assets.name, assets.status, assets.location_text, assets.latitude, assets.longitude, '
                . 'assets.last_inspected_at, assets.next_inspection_due_at, assets.source_system, assets.source_dataset, '
                . 'assets.source_record_id, assets.source_geometry_type, assets.source_geometry, departments.name AS department_name, '
                . 'departments.code AS department_code, asset_categories.name AS category_name'
            )
            ->findAll();
    }

    /**
     * Returns one asset for the JSON API detail endpoint.
     *
     * @return array<string, mixed>|null
     */
    public function findApiAsset(int $id): ?array
    {
        return $this->select(
            'assets.id, assets.asset_code, assets.name, assets.status, assets.location_text, assets.latitude, assets.longitude, '
            . 'assets.installed_on, assets.last_inspected_at, assets.next_inspection_due_at, assets.notes, '
            . 'assets.source_system, assets.source_dataset, assets.source_record_id, assets.source_url, '
            . 'assets.source_geometry_type, assets.source_geometry, '
            . 'departments.name AS department_name, departments.code AS department_code, departments.contact_email AS department_contact_email, '
            . 'asset_categories.name AS category_name, asset_categories.inspection_interval_days'
        )
            ->join('departments', 'departments.id = assets.department_id')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('assets.id', $id)
            ->first();
    }

    /**
     * Applies the selected inventory ordering.
     */
    private function applyInventorySortToQuery(BaseBuilder|self $query, string $sort): void
    {
        match ($sort) {
            'next_due_asc' => $query->orderBy('assets.next_inspection_due_at', 'ASC'),
            'last_inspected_desc' => $query->orderBy('assets.last_inspected_at', 'DESC'),
            'status_asc' => $query->orderBy('assets.status', 'ASC')->orderBy('assets.asset_code', 'ASC'),
            default => $query->orderBy('assets.asset_code', 'ASC'),
        };
    }

    /**
     * Applies the shared inventory filters used by table, API, and map queries.
     */
    private function applyInventoryFiltersToQuery(BaseBuilder|self $query, array $filters): void
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $location = trim((string) ($filters['location'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $status = trim((string) ($filters['status'] ?? ''));
        $overdue = (string) ($filters['overdue'] ?? '') === '1';
        $sourceDataset = trim((string) ($filters['source_dataset'] ?? ''));
        $geometryFamily = trim((string) ($filters['geometry_family'] ?? ''));
        $organizationId = $filters['organization_id'] ?? null;

        if ($search !== '') {
            $query->groupStart()
                ->like('assets.asset_code', $search)
                ->orLike('assets.name', $search)
                ->groupEnd();
        }

        if ($location !== '') {
            $query->like('assets.location_text', $location);
        }

        if ($categoryId !== null) {
            $query->where('assets.category_id', $categoryId);
        }

        if ($departmentId !== null) {
            $query->where('assets.department_id', $departmentId);
        }

        if ($status !== '') {
            $query->where('assets.status', $status);
        }

        if ($sourceDataset !== '') {
            $query->where('assets.source_dataset', $sourceDataset);
        }

        if ($organizationId !== null) {
            $query->where('assets.organization_id', $organizationId);
        }

        if ($geometryFamily !== '') {
            match ($geometryFamily) {
                'point' => $query->groupStart()
                    ->where('assets.source_geometry_type', 'Point')
                    ->orGroupStart()
                        ->where('assets.source_geometry_type IS NULL', null, false)
                        ->where('assets.latitude IS NOT NULL', null, false)
                        ->where('assets.longitude IS NOT NULL', null, false)
                    ->groupEnd()
                ->groupEnd(),
                'line' => $query->where('assets.source_geometry_type LIKE', '%LineString'),
                'polygon' => $query->where('assets.source_geometry_type LIKE', '%Polygon'),
                default => null,
            };
        }

        if ($overdue) {
            $query->where('assets.next_inspection_due_at <', date('Y-m-d H:i:s'))
                ->where('assets.next_inspection_due_at IS NOT NULL', null, false);
        }
    }

    /**
     * Applies a viewport bounding box when the map screen requests one.
     */
    private function applyMapBoundsToQuery(BaseBuilder|self $query, array $filters): void
    {
        $north = $this->decimalFilter($filters['north'] ?? null);
        $south = $this->decimalFilter($filters['south'] ?? null);
        $east = $this->decimalFilter($filters['east'] ?? null);
        $west = $this->decimalFilter($filters['west'] ?? null);

        if ($north === null || $south === null || $east === null || $west === null) {
            return;
        }

        $query->where('assets.latitude <=', $north)
            ->where('assets.latitude >=', $south)
            ->where('assets.longitude <=', $east)
            ->where('assets.longitude >=', $west);
    }

    private function inventoryBaseBuilder(bool $includeLookups = false): BaseBuilder
    {
        $builder = $this->db->table($this->table);

        if ($includeLookups) {
            $builder->join('departments', 'departments.id = assets.department_id')
                ->join('asset_categories', 'asset_categories.id = assets.category_id');
        }

        $builder->where('assets.deleted_at', null);

        return $builder;
    }

    private function decimalFilter(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Checks whether a source sync payload would materially change the stored asset.
     *
     * @param array<string, mixed> $existing
     * @param array<string, int|string|null> $payload
     */
    private function payloadMatchesExisting(array $existing, array $payload): bool
    {
        $fields = [
            'asset_code',
            'department_id',
            'category_id',
            'name',
            'status',
            'location_text',
            'latitude',
            'longitude',
            'installed_on',
            'notes',
            'source_system',
            'source_dataset',
            'source_record_id',
            'source_url',
            'source_geometry_type',
            'source_geometry',
        ];

        foreach ($fields as $field) {
            $existingValue = $existing[$field] ?? null;
            $payloadValue = $payload[$field] ?? null;

            if ($this->normalizedComparableValue($existingValue) !== $this->normalizedComparableValue($payloadValue)) {
                return false;
            }
        }

        return true;
    }

    private function normalizedComparableValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric((string) $value)) {
            return number_format((float) $value, 7, '.', '');
        }

        return trim((string) $value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sourceChecksum(array $payload): string
    {
        return sha1(json_encode([
            'asset_code' => $payload['asset_code'] ?? null,
            'name' => $payload['name'] ?? null,
            'status' => $payload['status'] ?? null,
            'location_text' => $payload['location_text'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'installed_on' => $payload['installed_on'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'source_geometry_type' => $payload['source_geometry_type'] ?? null,
            'source_geometry' => $payload['source_geometry'] ?? null,
        ], JSON_UNESCAPED_SLASHES) ?: '');
    }
}
