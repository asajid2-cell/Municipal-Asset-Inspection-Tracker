<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Notification settings used by the development-safe outbox flow.
 */
class Notifications extends BaseConfig
{
    /**
     * Capture emails in the database instead of sending them externally.
     */
    public bool $captureOnly = true;

    /**
     * Prefix added to generated subjects so dev-mode emails are obvious.
     */
    public string $subjectPrefix = '[North River Ops] ';
}
