<?php

namespace App\Commands;

use App\Libraries\NotificationManager;
use App\Libraries\PerformanceTelemetry;
use App\Libraries\ReportingService;
use App\Libraries\SyncJobManager;
use App\Models\AssetModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

/**
 * Runs the scheduled operational tasks used by admins and deployment cron jobs.
 */
class RunScheduledOps extends BaseCommand
{
    protected $group = 'Municipal';
    protected $name = 'ops:run-scheduled';
    protected $description = 'Runs scheduled sync, reminder, source-health, and telemetry tasks.';
    protected $usage = 'ops:run-scheduled [--source edmonton-benches] [--limit 100] [--all] [--user admin@northriver.local] [--health-only]';
    protected $options = [
        '--source' => 'Optional open-data source key to sync during this run.',
        '--limit' => 'Maximum number of records to import when a source is provided.',
        '--all' => 'Import the full dataset for the provided source.',
        '--user' => 'Seeded user email recorded as the actor.',
        '--health-only' => 'Skip source sync and only capture source health, reminders, and telemetry.',
    ];

    public function run(array $params): void
    {
        $sourceKey = trim((string) (CLI::getOption('source') ?? ''));
        $limit = (int) (CLI::getOption('limit') ?? 100);
        $syncAll = CLI::getOption('all') !== null;
        $healthOnly = CLI::getOption('health-only') !== null;
        $email = trim((string) (CLI::getOption('user') ?? 'admin@northriver.local'));
        $user = $this->actorUser($email);
        $organizationId = (int) ($user['organization_id'] ?? 1);
        $actorUserId = (int) $user['id'];
        $reporting = new ReportingService();

        CLI::write('Running scheduled operations for organization #' . $organizationId . '...', 'yellow');

        if (! $healthOnly && $sourceKey !== '') {
            CLI::write('Syncing source ' . $sourceKey . '...', 'yellow');
            $job = (new SyncJobManager())->run($organizationId, $sourceKey, $limit, $actorUserId, $syncAll);
            CLI::write(
                'Sync complete'
                . ' | fetched: ' . (string) ($job['fetched_count'] ?? 0)
                . ' | imported: ' . (string) ($job['imported_count'] ?? 0)
                . ' | updated: ' . (string) ($job['updated_count'] ?? 0)
                . ' | unchanged: ' . (string) ($job['unchanged_count'] ?? 0),
                'green'
            );
        }

        $sourceHealth = $reporting->sourceHealth($organizationId, true);
        CLI::write('Captured ' . count($sourceHealth) . ' source health snapshots.', 'green');

        $assets = (new AssetModel())->overdueAssetsForNotifications();
        $reminderCount = (new NotificationManager())->captureOverdueInspectionReminders($assets, $actorUserId);
        CLI::write('Captured ' . $reminderCount . ' overdue reminder notifications.', 'green');

        $summary = $reporting->executiveSummary($organizationId);
        $telemetry = new PerformanceTelemetry();
        $telemetry->capture($organizationId, 'scheduled_ops', 'asset_count', (int) $summary['asset_count']);
        $telemetry->capture($organizationId, 'scheduled_ops', 'overdue_count', (int) $summary['overdue_count']);
        $telemetry->capture($organizationId, 'scheduled_ops', 'repair_backlog_count', (int) $summary['repair_backlog_count']);
        $telemetry->capture($organizationId, 'scheduled_ops', 'average_risk', (float) $summary['average_risk']);

        CLI::newLine();
        CLI::write('Operational snapshot', 'yellow');
        CLI::write('Assets: ' . (string) $summary['asset_count']);
        CLI::write('Overdue: ' . (string) $summary['overdue_count']);
        CLI::write('Backlog: ' . (string) $summary['repair_backlog_count']);
        CLI::write('Average risk: ' . (string) $summary['average_risk']);
    }

    /**
     * @return array<string, mixed>
     */
    private function actorUser(string $email): array
    {
        $row = (new UserModel())->where('email', $email)->first();

        if (! is_array($row)) {
            throw new RuntimeException('No user found for email ' . $email . '.');
        }

        return $row;
    }
}
