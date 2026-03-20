<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;
use App\Models\AssetCategoryModel;
use App\Models\AssetModel;
use App\Models\DepartmentModel;

/**
 * Imports asset rows from CSV while reporting row-level validation errors.
 */
class AssetImportService
{
    /**
     * Required CSV columns for a valid import file.
     *
     * @var list<string>
     */
    public const REQUIRED_HEADERS = [
        'asset_code',
        'name',
        'department_code',
        'category_name',
        'location_text',
    ];

    /**
     * Optional CSV columns supported by the importer.
     *
     * @var list<string>
     */
    public const OPTIONAL_HEADERS = [
        'status',
        'installed_on',
        'latitude',
        'longitude',
        'notes',
    ];

    /**
     * Imports one CSV file and returns a summary with row-level failures.
     *
     * @return array<string, int|array<int, string>>
     */
    public function import(string $path, ?int $actorUserId): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [
                'imported_count' => 0,
                'errors' => ['The CSV file could not be opened.'],
            ];
        }

        try {
            $headerRow = fgetcsv($handle);

            if (! is_array($headerRow)) {
                return [
                    'imported_count' => 0,
                    'errors' => ['The CSV file is empty.'],
                ];
            }

            $headers = array_map(
                static fn ($header): string => strtolower(trim((string) $header)),
                $headerRow
            );

            $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

            if ($missingHeaders !== []) {
                return [
                    'imported_count' => 0,
                    'errors' => ['Missing required columns: ' . implode(', ', $missingHeaders) . '.'],
                ];
            }

            $headerIndex = array_flip($headers);
            $departments = $this->departmentLookup();
            $categories = $this->categoryLookup();
            $assetModel = new AssetModel();
            $activityLog = new ActivityLogModel();

            $importedCount = 0;
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $result = $this->validateRow($row, $headerIndex, $departments, $categories, $assetModel);

                if ($result['errors'] !== []) {
                    foreach ($result['errors'] as $error) {
                        $errors[] = 'Row ' . $rowNumber . ': ' . $error;
                    }

                    continue;
                }

                /** @var array<string, int|string|null> $payload */
                $payload = $result['payload'];

                $assetModel->insert($payload + [
                    'created_by' => $actorUserId,
                    'updated_by' => $actorUserId,
                ]);

                $assetId = (int) $assetModel->getInsertID();
                $importedCount++;

                $activityLog->recordEntry(
                    $actorUserId,
                    'asset',
                    $assetId,
                    'imported',
                    'Imported asset ' . $payload['asset_code'] . ' from CSV.',
                    [
                        'asset_code' => $payload['asset_code'],
                        'status' => $payload['status'],
                    ]
                );
            }

            return [
                'imported_count' => $importedCount,
                'errors' => $errors,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Returns the expected CSV column order for the downloadable template.
     *
     * @return list<string>
     */
    public static function templateHeaders(): array
    {
        return array_merge(self::REQUIRED_HEADERS, self::OPTIONAL_HEADERS);
    }

    /**
     * Validates one CSV row and returns either an insert payload or row errors.
     *
     * @param array<int, mixed> $row
     * @param array<string, int> $headerIndex
     * @param array<string, array<string, mixed>> $departments
     * @param array<string, array<string, mixed>> $categories
     *
     * @return array{payload: array<string, int|string|null>, errors: array<int, string>}
     */
    private function validateRow(
        array $row,
        array $headerIndex,
        array $departments,
        array $categories,
        AssetModel $assetModel
    ): array {
        $departmentCode = strtoupper($this->csvValue($row, $headerIndex, 'department_code'));
        $categoryName = $this->csvValue($row, $headerIndex, 'category_name');
        $assetCode = strtoupper($this->csvValue($row, $headerIndex, 'asset_code'));

        $errors = [];

        if (! isset($departments[$departmentCode])) {
            $errors[] = 'Department code "' . $departmentCode . '" was not found.';
        }

        if (! isset($categories[strtolower($categoryName)])) {
            $errors[] = 'Category "' . $categoryName . '" was not found.';
        }

        if ($assetCode === '') {
            $errors[] = 'Asset code is required.';
        } elseif ($assetModel->assetCodeExists($assetCode)) {
            $errors[] = 'Asset code "' . $assetCode . '" already exists.';
        }

        $status = $this->csvValue($row, $headerIndex, 'status');

        if ($status === '' && isset($categories[strtolower($categoryName)])) {
            $status = (string) $categories[strtolower($categoryName)]['default_status'];
        }

        $payload = [
            'asset_code' => $assetCode,
            'department_id' => isset($departments[$departmentCode]) ? (int) $departments[$departmentCode]['id'] : null,
            'category_id' => isset($categories[strtolower($categoryName)]) ? (int) $categories[strtolower($categoryName)]['id'] : null,
            'name' => $this->csvValue($row, $headerIndex, 'name'),
            'status' => $status,
            'location_text' => $this->csvValue($row, $headerIndex, 'location_text'),
            'installed_on' => $this->nullable($this->csvValue($row, $headerIndex, 'installed_on')),
            'latitude' => $this->nullable($this->csvValue($row, $headerIndex, 'latitude')),
            'longitude' => $this->nullable($this->csvValue($row, $headerIndex, 'longitude')),
            'notes' => $this->nullable($this->csvValue($row, $headerIndex, 'notes')),
        ];

        $validation = service('validation');
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
        ]);

        if (! $validation->run($payload)) {
            foreach ($validation->getErrors() as $message) {
                $errors[] = $message;
            }
        }

        return [
            'payload' => $payload,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Builds a department lookup keyed by department code.
     *
     * @return array<string, array<string, mixed>>
     */
    private function departmentLookup(): array
    {
        $rows = (new DepartmentModel())->findAll();
        $lookup = [];

        foreach ($rows as $row) {
            $lookup[strtoupper((string) $row['code'])] = $row;
        }

        return $lookup;
    }

    /**
     * Builds a category lookup keyed by lower-case category name.
     *
     * @return array<string, array<string, mixed>>
     */
    private function categoryLookup(): array
    {
        $rows = (new AssetCategoryModel())->findAll();
        $lookup = [];

        foreach ($rows as $row) {
            $lookup[strtolower((string) $row['name'])] = $row;
        }

        return $lookup;
    }

    /**
     * Returns one CSV value by header name.
     *
     * @param array<int, mixed> $row
     * @param array<string, int> $headerIndex
     */
    private function csvValue(array $row, array $headerIndex, string $column): string
    {
        $index = $headerIndex[$column] ?? null;

        if ($index === null) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }

    /**
     * Detects blank rows so imports can skip trailing empty lines.
     *
     * @param array<int, mixed> $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
