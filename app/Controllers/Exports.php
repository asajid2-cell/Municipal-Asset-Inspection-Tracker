<?php

namespace App\Controllers;

use App\Libraries\ExportManager;
use App\Models\ExportJobModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Handles durable export jobs for large inventory datasets.
 */
class Exports extends BaseController
{
    public function index(): string
    {
        return view('exports/index', [
            'pageTitle' => 'Export Jobs',
            'activeNav' => 'admin',
            'jobs' => (new ExportJobModel())->recentForOrganization($this->currentOrganizationId(), 20),
        ]);
    }

    public function createAssetExport()
    {
        $filters = $this->inventoryFiltersFromRequest();
        $name = trim((string) $this->request->getPost('name'));

        if ($name === '') {
            $name = 'Asset inventory export';
        }

        $job = (new ExportManager())->createAssetExport(
            $this->currentOrganizationId(),
            $this->currentUserId(),
            $name,
            $filters + ['organization_id' => $this->currentOrganizationId()]
        );

        return redirect()->to(site_url('exports'))->with('success', 'Export generated: ' . ($job['name'] ?? $name));
    }

    public function download(int $jobId): ResponseInterface
    {
        $job = (new ExportJobModel())->find($jobId);

        if (! is_array($job) || (int) $job['organization_id'] !== $this->currentOrganizationId()) {
            throw PageNotFoundException::forPageNotFound('Export job not found.');
        }

        $absolutePath = WRITEPATH . (string) $job['file_path'];

        if (! is_file($absolutePath)) {
            throw PageNotFoundException::forPageNotFound('Export file not found.');
        }

        return $this->response->download($absolutePath, null)->setFileName(basename($absolutePath));
    }

    /**
     * @return array<string, int|string|null>
     */
    private function inventoryFiltersFromRequest(): array
    {
        $nullableInteger = static function (mixed $value): ?int {
            $value = trim((string) $value);

            return $value === '' ? null : (int) $value;
        };

        return [
            'q' => trim((string) $this->request->getPost('q')),
            'location' => trim((string) $this->request->getPost('location')),
            'category_id' => $nullableInteger($this->request->getPost('category_id')),
            'department_id' => $nullableInteger($this->request->getPost('department_id')),
            'status' => trim((string) $this->request->getPost('status')),
            'overdue' => $this->request->getPost('overdue') === '1' ? '1' : '',
            'source_dataset' => trim((string) $this->request->getPost('source_dataset')),
            'geometry_family' => trim((string) $this->request->getPost('geometry_family')),
            'sort' => trim((string) $this->request->getPost('sort')) ?: 'asset_code_asc',
        ];
    }
}
