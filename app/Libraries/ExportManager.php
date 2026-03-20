<?php

namespace App\Libraries;

use App\Models\AssetModel;
use App\Models\ExportJobModel;
use RuntimeException;

/**
 * Generates large asset exports in bounded chunks so PHP does not load everything at once.
 */
class ExportManager
{
    private const CHUNK_SIZE = 2000;

    public function createAssetExport(int $organizationId, ?int $requestedBy, string $name, array $filters): array
    {
        $jobModel = new ExportJobModel();
        $timestamp = date('Ymd_His');
        $relativePath = 'exports/assets_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.csv';
        $absolutePath = WRITEPATH . $relativePath;

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }

        $jobModel->insert([
            'organization_id' => $organizationId,
            'requested_by' => $requestedBy,
            'name' => $name,
            'format' => 'csv',
            'filters_json' => json_encode($filters, JSON_UNESCAPED_SLASHES),
            'status' => 'Processing',
            'file_path' => $relativePath,
            'row_count' => 0,
            'started_at' => date('Y-m-d H:i:s'),
        ], false);

        $jobId = (int) $jobModel->getInsertID();
        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new RuntimeException('Export file could not be created.');
        }

        fputcsv($handle, [
            'Asset Code',
            'Name',
            'Category',
            'Department',
            'Status',
            'Location',
            'Risk Score',
            'Lifecycle State',
            'Replacement Cost',
            'Next Inspection Due',
            'Source Dataset',
        ]);

        $page = 1;
        $rowsWritten = 0;

        while (true) {
            $assetModel = new AssetModel();
            $rows = $assetModel->forInventoryList($filters)->paginate(self::CHUNK_SIZE, 'export', $page);

            if ($rows === []) {
                break;
            }

            foreach ($rows as $asset) {
                fputcsv($handle, [
                    $asset['asset_code'],
                    $asset['name'],
                    $asset['category_name'],
                    $asset['department_name'],
                    $asset['status'],
                    $asset['location_text'],
                    $asset['risk_score'],
                    $asset['lifecycle_state'],
                    $asset['replacement_cost'],
                    $asset['next_inspection_due_at'],
                    $asset['source_dataset'],
                ]);
                $rowsWritten++;
            }

            if (count($rows) < self::CHUNK_SIZE) {
                break;
            }

            $page++;
        }

        fclose($handle);

        $jobModel->update($jobId, [
            'status' => 'Completed',
            'row_count' => $rowsWritten,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);

        return $jobModel->find($jobId) ?? [];
    }
}
