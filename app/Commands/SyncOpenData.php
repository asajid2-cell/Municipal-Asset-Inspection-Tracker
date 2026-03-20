<?php

namespace App\Commands;

use App\Libraries\SyncJobManager;
use App\Models\SyncJobModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use InvalidArgumentException;
use RuntimeException;

/**
 * Pulls live municipal open-data records into the local asset inventory.
 */
class SyncOpenData extends BaseCommand
{
    protected $group = 'Municipal';
    protected $name = 'sync:open-data';
    protected $description = 'Syncs public municipal asset data into the inventory.';
    protected $usage = 'sync:open-data <source-key> [--limit 100] [--all] [--user admin@northriver.local] [--resume-offset 2000] [--resume-job 14]';
    protected $arguments = [
        'source-key' => 'One configured source key from app/Config/OpenData.php.',
    ];
    protected $options = [
        '--limit' => 'Maximum number of records to import for this run.',
        '--all' => 'Import the full dataset by paging through all available public records.',
        '--user' => 'Seeded user email recorded as the syncing actor.',
        '--resume-offset' => 'Resume the sync from a specific source offset.',
        '--resume-job' => 'Resume from the processed offset stored on an existing sync job.',
    ];

    public function run(array $params): void
    {
        $sourceKey = (string) ($params[0] ?? '');
        $limit = (int) (CLI::getOption('limit') ?? 100);
        $syncAll = CLI::getOption('all') !== null;
        $email = trim((string) (CLI::getOption('user') ?? 'admin@northriver.local'));
        $resumeOffset = max(0, (int) (CLI::getOption('resume-offset') ?? 0));
        $resumeJobId = CLI::getOption('resume-job');

        if ($sourceKey === '' && $resumeJobId === null) {
            throw new InvalidArgumentException('A source key is required.');
        }

        $user = $this->actorUser($email);
        $organizationId = (int) ($user['organization_id'] ?? 1);
        $actorUserId = (int) $user['id'];

        if ($resumeJobId !== null) {
            $job = (new SyncJobModel())->find((int) $resumeJobId);

            if (! is_array($job)) {
                throw new RuntimeException('No sync job found for id ' . $resumeJobId . '.');
            }

            $sourceKey = (string) $job['source_key'];
            $resumeOffset = (int) ($job['processed_offset'] ?? 0);
            $syncAll = (string) ($job['mode'] ?? '') !== 'sample';
            $limit = (int) (($job['requested_limit'] ?? $limit) ?: $limit);
        }

        CLI::write('Starting sync for ' . $sourceKey . ' from offset ' . $resumeOffset . '...', 'yellow');

        $report = (new SyncJobManager())->run(
            $organizationId,
            $sourceKey,
            $limit,
            $actorUserId,
            $syncAll,
            $resumeOffset
        );

        if ($report !== []) {
            CLI::write(
                'Progress'
                . ' | fetched: ' . (string) ($report['fetched_count'] ?? 0)
                . ' | imported: ' . (string) ($report['imported_count'] ?? 0)
                . ' | updated: ' . (string) ($report['updated_count'] ?? 0)
                . ' | restored: ' . (string) ($report['restored_count'] ?? 0)
                . ' | unchanged: ' . (string) ($report['unchanged_count'] ?? 0)
                . ' | skipped: ' . (string) ($report['skipped_count'] ?? 0),
                'green'
            );
        }

        CLI::write('Source: ' . (string) ($report['source_label'] ?? $sourceKey));
        CLI::write('Requested: ' . (string) (($report['requested_limit'] ?? null) ?? 'all'));
        CLI::write('Start offset: ' . (string) $resumeOffset);
        CLI::write('Processed offset: ' . (string) ($report['processed_offset'] ?? 0));
        CLI::write('Fetched: ' . (string) ($report['fetched_count'] ?? 0));
        CLI::write('Imported: ' . (string) ($report['imported_count'] ?? 0));
        CLI::write('Updated: ' . (string) ($report['updated_count'] ?? 0));
        CLI::write('Restored: ' . (string) ($report['restored_count'] ?? 0));
        CLI::write('Unchanged: ' . (string) ($report['unchanged_count'] ?? 0));
        CLI::write('Skipped: ' . (string) ($report['skipped_count'] ?? 0));

        $errorMessage = trim((string) ($report['error_message'] ?? ''));

        if ($errorMessage !== '') {
            CLI::newLine();
            CLI::write('Issues:', 'yellow');
            CLI::write('- ' . $errorMessage, 'yellow');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function actorUser(string $email): array
    {
        $row = (new UserModel())
            ->where('email', $email)
            ->first();

        if (! is_array($row)) {
            throw new RuntimeException('No user found for email ' . $email . '.');
        }

        return $row;
    }
}
