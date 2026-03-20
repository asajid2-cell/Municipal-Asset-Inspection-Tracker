<?php

namespace App\Libraries;

use App\Libraries\OpenData\CallbackSourceAdapter;
use App\Libraries\OpenData\SourceAdapterRegistry;
use App\Models\ActivityLogModel;
use App\Models\AssetCategoryModel;
use App\Models\AssetModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;
use Config\OpenData;
use Config\Services;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fetches municipal open-data records and syncs them into the asset inventory.
 */
class OpenDataSyncService
{
    /**
     * @var callable|null
     */
    private $fetcher;

    private OpenData $config;
    private SourceAdapterRegistry $adapterRegistry;

    public function __construct(?callable $fetcher = null, ?OpenData $config = null)
    {
        $this->fetcher = $fetcher;
        $this->config = $config ?? config('OpenData');
        $this->adapterRegistry = new SourceAdapterRegistry([
            'edmonton-benches' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeBench($record)),
            'edmonton-hydrants' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeHydrant($record)),
            'edmonton-trees' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeTree($record)),
            'edmonton-playgrounds' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizePlayground($record)),
            'edmonton-spray-parks' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeSprayPark($record)),
            'edmonton-parks' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizePark($record)),
            'edmonton-streetlights' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeStreetlight($record)),
            'edmonton-drainage-manholes' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePointAsset($record, 'DMH', 'Drainage Manhole')),
            'edmonton-drainage-catch-basins' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePointAsset($record, 'DCB', 'Catch Basin')),
            'edmonton-drainage-pump-stations' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePointAsset($record, 'DPS', 'Pump Station')),
            'edmonton-drainage-outfalls' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePointAsset($record, 'DOF', 'Drainage Outfall')),
            'edmonton-drainage-inlets-outlets' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePointAsset($record, 'DIO', 'Drainage Inlet/Outlet')),
            'edmonton-drainage-pipe-segments' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainageLineAsset($record, 'DPIPE', 'Drainage Pipe Segment')),
            'edmonton-drainage-catch-basin-leads' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainageLineAsset($record, 'DCBL', 'Catch Basin Lead')),
            'edmonton-stormwater-facilities' => new CallbackSourceAdapter(fn (array $record): ?array => $this->normalizeDrainagePolygonAsset($record, 'SWF', 'Stormwater Facility')),
        ]);
    }

    /**
     * Returns source metadata used by the UI and CLI.
     *
     * @return array<string, array<string, int|string>>
     */
    public function availableSources(): array
    {
        return $this->config->sources;
    }

    /**
     * Fetches live records from one source and imports them into the app.
     *
     * @return array<string, int|string|array<int, string>>
     */
    public function syncSource(
        string $sourceKey,
        int $limit,
        ?int $actorUserId,
        bool $syncAll = false,
        ?callable $progressCallback = null,
        int $startingOffset = 0
    ): array
    {
        $source = $this->sourceDefinition($sourceKey);
        $requestedLimit = $syncAll ? null : $this->normalizedLimit($source, $limit);
        $batchSize = $this->batchSize($source, $requestedLimit, $syncAll);
        $offset = max(0, $startingOffset);
        $remaining = $requestedLimit;
        $report = $this->emptyReport($sourceKey, $source, $requestedLimit);
        $report['starting_offset'] = $offset;
        $context = $this->syncContext($sourceKey, $source, $actorUserId);

        do {
            $queryLimit = $syncAll ? $batchSize : min($batchSize, $remaining ?? $batchSize);
            $batch = $this->fetchBatch($source, $queryLimit, $offset);

            if ($batch === []) {
                break;
            }

            $report['fetched_count'] += count($batch);
            $db = db_connect('default');
            $db->transStart();
            $this->processRecords($batch, $actorUserId, $context, $report);
            $db->transComplete();

            if ($progressCallback !== null) {
                $progressCallback([
                    'source_key' => $sourceKey,
                    'source_label' => (string) $source['label'],
                    'requested_limit' => $report['requested_limit'],
                    'starting_offset' => $report['starting_offset'],
                    'processed_offset' => $offset + count($batch),
                    'batch_count' => count($batch),
                    'fetched_count' => $report['fetched_count'],
                    'imported_count' => $report['imported_count'],
                    'updated_count' => $report['updated_count'],
                    'restored_count' => $report['restored_count'],
                    'unchanged_count' => $report['unchanged_count'],
                    'skipped_count' => $report['skipped_count'],
                ]);
            }

            $offset += count($batch);

            if (! $syncAll && $remaining !== null) {
                $remaining -= count($batch);
            }
        } while (
            $batch !== []
            && ($syncAll || ($remaining !== null && $remaining > 0))
            && count($batch) === $queryLimit
        );

        $this->recordSyncSummary($actorUserId, $sourceKey, $source, $report);

        return $report;
    }

    /**
     * Imports prepared source records. Tests use this method to avoid live HTTP calls.
     *
     * @param array<int, array<string, mixed>> $records
     *
     * @return array<string, int|string|array<int, string>>
     */
    public function importRecords(string $sourceKey, array $records, ?int $actorUserId, ?int $requestedLimit = null): array
    {
        $source = $this->sourceDefinition($sourceKey);
        $report = $this->emptyReport($sourceKey, $source, $requestedLimit);
        $report['fetched_count'] = count($records);
        $context = $this->syncContext($sourceKey, $source, $actorUserId);

        $db = db_connect('default');
        $db->transStart();
        $this->processRecords($records, $actorUserId, $context, $report);
        $db->transComplete();
        $this->recordSyncSummary($actorUserId, $sourceKey, $source, $report);

        return $report;
    }

    /**
     * Builds one inventory payload from a live public record.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeRecord(string $sourceKey, array $record): ?array
    {
        return $this->adapterRegistry->adapter($sourceKey)->normalize($record);
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeBench(array $record): ?array
    {
        $recordId = trim((string) ($record['asset_id'] ?? ''));

        if ($recordId === '') {
            return null;
        }

        $latitude = $this->decimalValue($record['geom']['latitude'] ?? null);
        $longitude = $this->decimalValue($record['geom']['longitude'] ?? null);
        $type = $this->titleCase((string) ($record['type'] ?? 'Park bench'));

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-BENCH-' . $recordId,
            'name' => $this->trimmed($type . ' ' . $recordId, 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                null,
                $latitude,
                $longitude,
                'City of Edmonton parks network'
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => null,
            ...$this->pointGeometryPayload($latitude, $longitude),
            'notes' => $this->notes([
                'Public type: ' . $type,
                'Surface material: ' . $this->titleCase((string) ($record['surface_material'] ?? 'Unknown')),
                'Structure material: ' . $this->titleCase((string) ($record['structure_material'] ?? 'Unknown')),
                'Owner: ' . $this->titleCase((string) ($record['owner'] ?? 'Unknown')),
                'Maintainer: ' . $this->titleCase((string) ($record['maintainer'] ?? 'Unknown')),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeHydrant(array $record): ?array
    {
        $recordId = trim((string) ($record['hydrant_number'] ?? ''));

        if ($recordId === '') {
            return null;
        }

        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $lifeCycle = $this->titleCase((string) ($record['life_cycle_indicator'] ?? 'Active'));
        $status = str_contains(strtolower($lifeCycle), 'retired') || str_contains(strtolower($lifeCycle), 'inactive')
            ? 'Out of Service'
            : 'Active';

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-HYDRANT-' . $recordId,
            'name' => $this->trimmed('Fire Hydrant ' . $recordId, 190),
            'status' => $status,
            'location_text' => $this->locationText(
                trim((string) ($record['nearest_address'] ?? '')),
                $latitude,
                $longitude,
                'City of Edmonton utility network'
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateFromYear($record['installation_year'] ?? null),
            ...$this->pointGeometryPayload($latitude, $longitude),
            'notes' => $this->notes([
                'Dome colour: ' . strtoupper(trim((string) ($record['dome_colour'] ?? 'Unknown'))),
                'Flow rate: ' . trim((string) ($record['flow_rate'] ?? 'Unknown')),
                'Owner: ' . $this->titleCase((string) ($record['owner'] ?? 'Unknown')),
                'Lifecycle: ' . $lifeCycle,
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeTree(array $record): ?array
    {
        $recordId = trim((string) ($record['id'] ?? ''));

        if ($recordId === '') {
            return null;
        }

        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $commonSpecies = $this->titleCase((string) ($record['species'] ?? 'City tree'));

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-TREE-' . $recordId,
            'name' => $this->trimmed($commonSpecies . ' ' . $recordId, 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['neighbourhood_name'] ?? '')),
                $latitude,
                $longitude,
                'City of Edmonton urban forest'
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateValue($record['planted_date'] ?? null),
            ...$this->pointGeometryPayload($latitude, $longitude),
            'notes' => $this->notes([
                'Botanical species: ' . $this->titleCase((string) ($record['species_botanical'] ?? 'Unknown')),
                'Genus: ' . $this->titleCase((string) ($record['genus'] ?? 'Unknown')),
                'Location type: ' . $this->titleCase((string) ($record['location_type'] ?? 'Unknown')),
                'Condition percent: ' . trim((string) ($record['condition_percent'] ?? 'Unknown')),
                'Diameter at breast height: ' . trim((string) ($record['diameter_breast_height'] ?? 'Unknown')),
                'Owner: ' . $this->titleCase((string) ($record['owner'] ?? 'Unknown')),
                'Tree count at record: ' . trim((string) ($record['count'] ?? '1')),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizePlayground(array $record): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $name = trim((string) ($record['name'] ?? ''));
        $recordId = $this->syntheticRecordId('PLAY', [
            trim((string) ($record['id'] ?? '')),
            $name,
            trim((string) ($record['address'] ?? '')),
            $latitude ?? '',
            $longitude ?? '',
        ]);

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-PLAYGROUND-' . $recordId,
            'name' => $this->trimmed(($name !== '' ? $name : 'Playground') . ' Playground', 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['address'] ?? '')),
                $latitude,
                $longitude,
                trim((string) ($record['neighbourhood_name'] ?? 'City of Edmonton playground network'))
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateValue($record['redevelopment_date'] ?? null),
            ...$this->sourceGeometryPayload($record['geometry_point'] ?? $record['location'] ?? null),
            'notes' => $this->notes([
                'Surface type: ' . $this->titleCase((string) ($record['surface_type'] ?? 'Unknown')),
                'Accessibility: ' . $this->titleCase((string) ($record['accessibility'] ?? 'Unknown')),
                'User category: ' . $this->titleCase((string) ($record['user_category'] ?? 'Unknown')),
                'Maintainer: ' . $this->titleCase((string) ($record['maintainer'] ?? 'Unknown')),
                'Neighbourhood: ' . $this->titleCase((string) ($record['neighbourhood_name'] ?? 'Unknown')),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeSprayPark(array $record): ?array
    {
        $recordId = $this->publicId($record, 'id');

        if ($recordId === null) {
            return null;
        }

        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $name = trim((string) ($record['name'] ?? ''));

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-SPRAY-' . $recordId,
            'name' => $this->trimmed($name !== '' ? $name : 'Spray Park ' . $recordId, 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['address'] ?? '')),
                $latitude,
                $longitude,
                trim((string) ($record['neighbourhood_name'] ?? 'City of Edmonton spray parks'))
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateValue($record['redevelopment_date'] ?? null),
            ...$this->sourceGeometryPayload($record['geometry_point'] ?? $record['location'] ?? null),
            'notes' => $this->notes([
                'Surface type: ' . $this->titleCase((string) ($record['surface_type'] ?? 'Unknown')),
                'Accessibility: ' . $this->titleCase((string) ($record['accessibility'] ?? 'Unknown')),
                'Owner: ' . $this->titleCase((string) ($record['owner'] ?? 'Unknown')),
                'Maintainer: ' . $this->titleCase((string) ($record['maintainer'] ?? 'Unknown')),
                'Neighbourhood: ' . $this->titleCase((string) ($record['neighbourhood_name'] ?? 'Unknown')),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizePark(array $record): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $officialName = trim((string) ($record['official_name'] ?? ''));
        $commonName = trim((string) ($record['common_name'] ?? ''));
        $recordId = $this->publicId($record, 'id')
            ?? $this->syntheticRecordId('PARK', [
                $officialName,
                $commonName,
                trim((string) ($record['address'] ?? '')),
                $latitude ?? '',
                $longitude ?? '',
            ]);
        $name = $officialName !== '' ? $officialName : ($commonName !== '' ? $commonName : 'Park ' . $this->shortCodeToken($recordId));

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-PARK-' . $recordId,
            'name' => $this->trimmed($name, 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['address'] ?? '')),
                $latitude,
                $longitude,
                'City of Edmonton parks network'
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => null,
            ...$this->sourceGeometryPayload($record['geometry_multipolygon'] ?? null),
            'notes' => $this->notes([
                'Public park status: ' . $this->titleCase((string) ($record['status'] ?? 'Unknown')),
                'Park class: ' . $this->titleCase((string) ($record['class'] ?? 'Unknown')),
                'Park type: ' . $this->titleCase((string) ($record['type'] ?? 'Unknown')),
                'Area (m2): ' . trim((string) ($record['area'] ?? 'Unknown')),
                'Common name: ' . ($commonName !== '' ? $commonName : 'Unknown'),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeStreetlight(array $record): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $lightType = strtoupper(trim((string) ($record['type'] ?? 'LIGHT')));
        $recordId = $this->publicId($record, 'id')
            ?? $this->syntheticRecordId('LIGHT', [
                $lightType,
                trim((string) ($record['watts'] ?? '')),
                trim((string) ($record['start_date'] ?? '')),
                $latitude ?? '',
                $longitude ?? '',
            ]);

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-LIGHT-' . $recordId,
            'name' => $this->trimmed('Streetlight ' . $recordId, 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['location_address'] ?? '')),
                $latitude,
                $longitude,
                'City of Edmonton streetlight network'
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateValue($record['start_date'] ?? null),
            ...$this->sourceGeometryPayload($record['geometry_point'] ?? $record['location'] ?? null),
            'notes' => $this->notes([
                'Lamp type: ' . $lightType,
                'Watts: ' . trim((string) ($record['watts'] ?? 'Unknown')),
                'Ownership: ' . strtoupper(trim((string) ($record['ownership'] ?? 'Unknown'))),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeDrainagePointAsset(array $record, string $prefix, string $label): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $geometry = $record['geometry_point'] ?? null;
        $recordId = $this->syntheticRecordId($prefix, [
            trim((string) ($record['road_name'] ?? '')),
            trim((string) ($record['type'] ?? '')),
            trim((string) ($record['year_const'] ?? '')),
            $latitude ?? '',
            $longitude ?? '',
            $this->geometrySignature($geometry),
        ]);

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-' . $prefix . '-' . $this->shortCodeToken($recordId),
            'name' => $this->trimmed($label . ' ' . $this->shortLabelContext($record, $recordId), 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['road_name'] ?? '')),
                $latitude,
                $longitude,
                trim((string) ($record['neighbourhood_name'] ?? 'City of Edmonton drainage network'))
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateFromYear($record['year_const'] ?? null),
            ...$this->sourceGeometryPayload($geometry),
            'notes' => $this->drainageNotes($record),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeDrainageLineAsset(array $record, string $prefix, string $label): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $geometry = $record['geometry_line'] ?? null;
        $recordId = $this->syntheticRecordId($prefix, [
            trim((string) ($record['road_name'] ?? '')),
            trim((string) ($record['type'] ?? '')),
            trim((string) ($record['year_const'] ?? '')),
            $this->geometrySignature($geometry),
        ]);

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-' . $prefix . '-' . $this->shortCodeToken($recordId),
            'name' => $this->trimmed($label . ' ' . $this->shortLabelContext($record, $recordId), 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['road_name'] ?? '')),
                $latitude,
                $longitude,
                trim((string) ($record['neighbourhood_name'] ?? 'City of Edmonton drainage network'))
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateFromYear($record['year_const'] ?? null),
            ...$this->sourceGeometryPayload($geometry),
            'notes' => $this->drainageNotes($record),
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|null>|null
     */
    private function normalizeDrainagePolygonAsset(array $record, string $prefix, string $label): ?array
    {
        $latitude = $this->decimalValue($record['latitude'] ?? null);
        $longitude = $this->decimalValue($record['longitude'] ?? null);
        $geometry = $record['geometry_multipolygon'] ?? $record['geometry'] ?? null;
        $recordId = $this->syntheticRecordId($prefix, [
            trim((string) ($record['road_name'] ?? $record['street_avenue'] ?? '')),
            trim((string) ($record['type'] ?? '')),
            trim((string) ($record['year_const'] ?? $record['year'] ?? '')),
            $this->geometrySignature($geometry),
        ]);

        return [
            'source_record_id' => $recordId,
            'asset_code' => 'EDM-' . $prefix . '-' . $this->shortCodeToken($recordId),
            'name' => $this->trimmed($label . ' ' . $this->shortLabelContext($record, $recordId), 190),
            'status' => 'Active',
            'location_text' => $this->locationText(
                trim((string) ($record['street_avenue'] ?? $record['road_name'] ?? '')),
                $latitude,
                $longitude,
                trim((string) ($record['neighbourhood_name'] ?? 'City of Edmonton stormwater network'))
            ),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'installed_on' => $this->dateFromYear($record['year_const'] ?? $record['year'] ?? null),
            ...$this->sourceGeometryPayload($geometry),
            'notes' => $this->notes([
                'Type: ' . $this->titleCase((string) ($record['type'] ?? 'Unknown')),
                'Facility type: ' . $this->titleCase((string) ($record['facility_type'] ?? 'Unknown')),
                'Storm type: ' . $this->titleCase((string) ($record['storm_type'] ?? 'Unknown')),
                'Facility owner: ' . $this->titleCase((string) ($record['facility_owner'] ?? 'Unknown')),
                'Description: ' . trim((string) ($record['description'] ?? 'Unknown')),
                'Ward: ' . trim((string) ($record['ward'] ?? $record['ward_name'] ?? 'Unknown')),
                'Neighbourhood: ' . $this->titleCase((string) ($record['neighbourhood_name'] ?? 'Unknown')),
            ]),
        ];
    }

    /**
     * @param array<string, int|string> $source
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchBatch(array $source, int $limit, int $offset): array
    {
        $fetcher = $this->fetcher ?? [$this, 'defaultFetcher'];

        /** @var array<int, array<string, mixed>> $batch */
        $batch = $fetcher(
            (string) $source['endpoint'],
            [
                '$limit' => (string) $limit,
                '$offset' => (string) $offset,
            ],
            $this->requestHeaders()
        );

        return $batch;
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultFetcher(string $endpoint, array $query, array $headers): array
    {
        $client = Services::curlrequest();
        $response = $client->get($endpoint, [
            'headers' => $headers,
            'query' => $query,
            'http_errors' => false,
            'timeout' => 20,
            'verify' => $this->verifyPeer(),
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException('Open data request failed with status ' . $response->getStatusCode() . '.');
        }

        $payload = json_decode((string) $response->getBody(), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Open data request returned an invalid JSON payload.');
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function requestHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        $appToken = trim((string) env('openData.socrataAppToken'));

        if ($appToken !== '') {
            $headers['X-App-Token'] = $appToken;
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, string>
     */
    private function validatePayload(array $payload): array
    {
        $validation = service('validation');
        $validation->reset();
        $validation->setRules([
            'asset_code' => 'required|max_length[60]',
            'department_id' => 'required|is_natural_no_zero',
            'category_id' => 'required|is_natural_no_zero',
            'name' => 'required|max_length[190]',
            'status' => 'required|in_list[' . implode(',', AssetModel::STATUS_OPTIONS) . ']',
            'location_text' => 'required|max_length[255]',
            'installed_on' => 'permit_empty|valid_date[Y-m-d]',
            'latitude' => 'permit_empty|decimal',
            'longitude' => 'permit_empty|decimal',
            'notes' => 'permit_empty|max_length[4000]',
            'source_system' => 'required|max_length[80]',
            'source_dataset' => 'required|max_length[80]',
            'source_record_id' => 'required|max_length[120]',
            'source_url' => 'permit_empty|max_length[255]',
            'source_geometry_type' => 'permit_empty|max_length[40]',
            'source_geometry' => 'permit_empty',
        ]);

        if ($validation->run($payload)) {
            return [];
        }

        return array_values($validation->getErrors());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function departmentLookup(): array
    {
        $lookup = [];

        foreach ((new DepartmentModel())->findAll() as $row) {
            $lookup[(string) $row['code']] = $row;
        }

        return $lookup;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function categoryLookup(): array
    {
        $lookup = [];

        foreach ((new AssetCategoryModel())->findAll() as $row) {
            $lookup[strtolower((string) $row['name'])] = $row;
        }

        return $lookup;
    }

    /**
     * @param array<string, int|string> $source
     */
    private function normalizedLimit(array $source, int $limit): int
    {
        return $limit > 0 ? $limit : (int) $source['default_limit'];
    }

    /**
     * @return array<string, int|string>
     */
    private function sourceDefinition(string $sourceKey): array
    {
        $source = $this->config->sources[$sourceKey] ?? null;

        if (! is_array($source)) {
            throw new InvalidArgumentException('Unknown open data source: ' . $sourceKey);
        }

        return $source;
    }

    /**
     * @param array<string, int|string> $source
     */
    private function batchSize(array $source, ?int $limit, bool $syncAll): int
    {
        $configured = (int) ($source['default_batch_size'] ?? 1000);

        if ($syncAll) {
            return max(1, $configured);
        }

        if ($limit === null) {
            return max(1, $configured);
        }

        return max(1, min($configured, $limit));
    }

    private function decimalValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric((string) $value)) {
            return null;
        }

        return number_format((float) $value, 7, '.', '');
    }

    private function dateFromYear(mixed $year): ?string
    {
        $year = trim((string) $year);

        if (! preg_match('/^\d{4}$/', $year)) {
            return null;
        }

        return $year . '-01-01';
    }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function locationText(?string $primary, ?string $latitude, ?string $longitude, string $fallback): string
    {
        $primary = trim((string) $primary);

        if ($primary !== '') {
            return $this->trimmed($primary, 255);
        }

        if ($latitude !== null && $longitude !== null) {
            return $this->trimmed('Approx. ' . $latitude . ', ' . $longitude, 255);
        }

        return $this->trimmed($fallback, 255);
    }

    /**
     * @param array<int, string> $parts
     */
    private function notes(array $parts): ?string
    {
        $parts = array_values(array_filter($parts, static fn (string $value): bool => trim($value) !== ''));

        if ($parts === []) {
            return null;
        }

        return $this->trimmed(implode('; ', $parts), 4000);
    }

    private function trimmed(string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }

    private function titleCase(string $value): string
    {
        $value = trim(str_replace('_', ' ', strtolower($value)));

        if ($value === '') {
            return '';
        }

        return ucwords($value);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function publicId(array $record, string $field): ?string
    {
        $value = trim((string) ($record[$field] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @return array{source_geometry_type: string|null, source_geometry: string|null}
     */
    private function pointGeometryPayload(?string $latitude, ?string $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            return [
                'source_geometry_type' => null,
                'source_geometry' => null,
            ];
        }

        return $this->sourceGeometryPayload([
            'type' => 'Point',
            'coordinates' => [(float) $longitude, (float) $latitude],
        ]);
    }

    /**
     * @return array{source_geometry_type: string|null, source_geometry: string|null}
     */
    private function sourceGeometryPayload(mixed $geometry): array
    {
        $geoJson = $this->geoJsonFromField($geometry);

        if ($geoJson === null) {
            return [
                'source_geometry_type' => null,
                'source_geometry' => null,
            ];
        }

        return [
            'source_geometry_type' => trim((string) ($geoJson['type'] ?? '')) ?: null,
            'source_geometry' => json_encode($geoJson, JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function geoJsonFromField(mixed $geometry): ?array
    {
        if (! is_array($geometry)) {
            return null;
        }

        $type = trim((string) ($geometry['type'] ?? ''));
        $coordinates = $geometry['coordinates'] ?? null;

        if ($type === '' || $coordinates === null) {
            return null;
        }

        if ($type === 'Point' && (! is_array($coordinates) || count($coordinates) < 2 || $coordinates[0] === null || $coordinates[1] === null)) {
            return null;
        }

        return [
            'type' => $type,
            'coordinates' => $coordinates,
        ];
    }

    /**
     * @param list<string> $parts
     */
    private function syntheticRecordId(string $prefix, array $parts): string
    {
        $parts = array_values(array_filter($parts, static fn (string $part): bool => trim($part) !== ''));

        if ($parts === []) {
            return $prefix . '-UNKNOWN';
        }

        return $prefix . '-' . substr(sha1(implode('|', $parts)), 0, 20);
    }

    private function geometrySignature(mixed $geometry): string
    {
        $geoJson = $this->geoJsonFromField($geometry);

        if ($geoJson === null) {
            return '';
        }

        return sha1(json_encode($geoJson, JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function shortCodeToken(string $recordId): string
    {
        $compact = preg_replace('/[^A-Z0-9]+/i', '', strtoupper($recordId)) ?? strtoupper($recordId);

        if ($compact === '') {
            return 'UNKNOWN';
        }

        return substr($compact, -10);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function shortLabelContext(array $record, string $recordId): string
    {
        $roadName = trim((string) ($record['road_name'] ?? $record['street_avenue'] ?? ''));

        if ($roadName !== '') {
            return $this->trimmed($roadName . ' ' . $this->shortCodeToken($recordId), 120);
        }

        return $this->shortCodeToken($recordId);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function drainageNotes(array $record): ?string
    {
        return $this->notes([
            'Type: ' . $this->titleCase((string) ($record['type'] ?? 'Unknown')),
            'Ward: ' . trim((string) ($record['ward'] ?? $record['ward_name'] ?? 'Unknown')),
            'Neighbourhood: ' . $this->titleCase((string) ($record['neighbourhood_name'] ?? 'Unknown')),
            'Road: ' . trim((string) ($record['road_name'] ?? $record['street_avenue'] ?? 'Unknown')),
            'Construction year: ' . trim((string) ($record['year_const'] ?? $record['year'] ?? 'Unknown')),
        ]);
    }

    private function verifyPeer(): bool
    {
        $configured = env('openData.verifyPeer');

        if ($configured === null || $configured === '') {
            return ENVIRONMENT !== 'development';
        }

        $parsed = filter_var($configured, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? true;
    }

    /**
     * @param array<string, int|string> $source
     *
     * @return array<string, mixed>
     */
    private function emptyReport(string $sourceKey, array $source, ?int $requestedLimit): array
    {
        return [
            'source_key' => $sourceKey,
            'source_label' => (string) $source['label'],
            'requested_limit' => $requestedLimit ?? 'all',
            'starting_offset' => 0,
            'fetched_count' => 0,
            'imported_count' => 0,
            'updated_count' => 0,
            'restored_count' => 0,
            'unchanged_count' => 0,
            'skipped_count' => 0,
            'errors' => [],
        ];
    }

    /**
     * @param array<string, int|string> $source
     *
     * @return array<string, mixed>
     */
    private function syncContext(string $sourceKey, array $source, ?int $actorUserId): array
    {
        $departments = $this->departmentLookup();
        $categories = $this->categoryLookup();

        if (! isset($departments[(string) $source['department_code']])) {
            throw new RuntimeException('Missing department mapping for source ' . $sourceKey . '.');
        }

        if (! isset($categories[strtolower((string) $source['category_name'])])) {
            throw new RuntimeException('Missing category mapping for source ' . $sourceKey . '.');
        }

        return [
            'source_key' => $sourceKey,
            'source' => $source,
            'organization_id' => $this->organizationIdForActor($actorUserId),
            'department_id' => (int) $departments[(string) $source['department_code']]['id'],
            'category_id' => (int) $categories[strtolower((string) $source['category_name'])]['id'],
            'asset_model' => new AssetModel(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @param array<string, mixed> $context
     * @param array<string, mixed> $report
     */
    private function processRecords(array $records, ?int $actorUserId, array $context, array &$report): void
    {
        $sourceKey = (string) $context['source_key'];
        /** @var array<string, int|string> $source */
        $source = $context['source'];
        /** @var AssetModel $assetModel */
        $assetModel = $context['asset_model'];

        foreach ($records as $index => $record) {
            $normalized = $this->normalizeRecord($sourceKey, $record);

            if ($normalized === null) {
                $report['skipped_count']++;
                $report['errors'][] = 'Record ' . ($index + 1) . ' is missing the required public identifier.';

                continue;
            }

            $payload = [
                'organization_id' => (int) $context['organization_id'],
                'asset_code' => (string) $normalized['asset_code'],
                'department_id' => (int) $context['department_id'],
                'category_id' => (int) $context['category_id'],
                'name' => (string) $normalized['name'],
                'status' => (string) $normalized['status'],
                'location_text' => (string) $normalized['location_text'],
                'latitude' => $normalized['latitude'],
                'longitude' => $normalized['longitude'],
                'installed_on' => $normalized['installed_on'],
                'notes' => $normalized['notes'],
                'source_system' => (string) $source['system'],
                'source_dataset' => (string) $source['dataset_id'],
                'source_record_id' => (string) $normalized['source_record_id'],
                'source_url' => (string) $source['endpoint'],
                'source_geometry_type' => $normalized['source_geometry_type'],
                'source_geometry' => $normalized['source_geometry'],
            ];

            $errors = $this->validatePayload($payload);

            if ($errors !== []) {
                $report['skipped_count']++;

                foreach ($errors as $error) {
                    $report['errors'][] = 'Record ' . $payload['source_record_id'] . ': ' . $error;
                }

                continue;
            }

            $result = $assetModel->syncFromSource($payload, $actorUserId);
            $action = (string) $result['action'];

            if ($action === 'imported') {
                $report['imported_count']++;
            } elseif ($action === 'restored') {
                $report['restored_count']++;
            } elseif ($action === 'unchanged') {
                $report['unchanged_count']++;
            } else {
                $report['updated_count']++;
            }
        }
    }

    /**
     * @param array<string, int|string> $source
     * @param array<string, mixed> $report
     */
    private function recordSyncSummary(?int $actorUserId, string $sourceKey, array $source, array $report): void
    {
        if ($actorUserId === null) {
            return;
        }

        $changes = (int) $report['imported_count'] + (int) $report['updated_count'] + (int) $report['restored_count'];

        if ($changes === 0 && (int) $report['skipped_count'] === 0) {
            return;
        }

        (new ActivityLogModel())->recordEntry(
            $actorUserId,
            'open_data_sync',
            0,
            'synced',
            'Synced ' . $source['label'] . ' into the municipal inventory.',
            [
                'source_key' => $sourceKey,
                'dataset_id' => $source['dataset_id'],
                'fetched_count' => $report['fetched_count'],
                'imported_count' => $report['imported_count'],
                'updated_count' => $report['updated_count'],
                'restored_count' => $report['restored_count'],
                'unchanged_count' => $report['unchanged_count'],
                'skipped_count' => $report['skipped_count'],
            ],
            $this->organizationIdForActor($actorUserId)
        );
    }

    private function organizationIdForActor(?int $actorUserId): int
    {
        if ($actorUserId === null) {
            return 1;
        }

        $user = (new UserModel())->find($actorUserId);

        if (! is_array($user)) {
            return 1;
        }

        return (int) ($user['organization_id'] ?? 1);
    }
}
