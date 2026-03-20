<?php

namespace App\Libraries;

use App\Models\SyncJobModel;
use RuntimeException;
use Throwable;

/**
 * Wraps open-data syncs in durable job records and health snapshots.
 */
class SyncJobManager
{
    /**
     * @return array<string, mixed>
     */
    public function run(
        int $organizationId,
        string $sourceKey,
        int $limit,
        ?int $actorUserId,
        bool $syncAll = false,
        int $startingOffset = 0
    ): array
    {
        /** @var OpenDataSyncService $syncService */
        $syncService = service('openDataSyncService');
        $source = $syncService->availableSources()[$sourceKey] ?? null;

        if (! is_array($source)) {
            throw new RuntimeException('Unknown source key: ' . $sourceKey);
        }

        $jobModel = new SyncJobModel();
        $jobModel->insert([
            'organization_id' => $organizationId,
            'source_key' => $sourceKey,
            'source_label' => (string) $source['label'],
            'status' => 'Running',
            'mode' => $startingOffset > 0 ? 'resume' : ($syncAll ? 'full' : 'sample'),
            'requested_limit' => $syncAll ? null : $limit,
            'processed_offset' => max(0, $startingOffset),
            'started_at' => date('Y-m-d H:i:s'),
            'created_by' => $actorUserId,
        ], false);

        $jobId = (int) $jobModel->getInsertID();

        try {
            $report = $syncService->syncSource(
                $sourceKey,
                $limit,
                $actorUserId,
                $syncAll,
                function (array $progress) use ($jobId, $jobModel): void {
                    $jobModel->update($jobId, [
                        'processed_offset' => (int) $progress['processed_offset'],
                        'fetched_count' => (int) $progress['fetched_count'],
                        'imported_count' => (int) $progress['imported_count'],
                        'updated_count' => (int) $progress['updated_count'],
                        'restored_count' => (int) $progress['restored_count'],
                        'unchanged_count' => (int) $progress['unchanged_count'],
                        'skipped_count' => (int) $progress['skipped_count'],
                    ]);
                },
                $startingOffset
            );

            $jobModel->update($jobId, [
                'status' => 'Completed',
                'processed_offset' => (int) $report['fetched_count'],
                'fetched_count' => (int) $report['fetched_count'],
                'imported_count' => (int) $report['imported_count'],
                'updated_count' => (int) $report['updated_count'],
                'restored_count' => (int) $report['restored_count'],
                'unchanged_count' => (int) $report['unchanged_count'],
                'skipped_count' => (int) $report['skipped_count'],
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            (new ReportingService())->sourceHealth($organizationId, true);
            (new PerformanceTelemetry())->capture($organizationId, 'sync_job', 'fetched_count', (int) $report['fetched_count'], [
                'source_key' => $sourceKey,
                'job_id' => $jobId,
            ]);
        } catch (Throwable $exception) {
            $jobModel->update($jobId, [
                'status' => 'Failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            throw $exception;
        }

        return $jobModel->find($jobId) ?? [];
    }
}
