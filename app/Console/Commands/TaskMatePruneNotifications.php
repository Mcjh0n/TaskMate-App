<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TaskMatePruneNotifications extends Command
{
    protected $signature = 'taskmate:prune-notifications';

    protected $description = 'Remove in-app notifications older than the configured retention period';

    public function handle(): int
    {
        $retentionDays = config('taskmate.notifications.retention_days');
        if ($retentionDays === null) {
            $this->info('Notification retention is not configured; no alerts were deleted.');

            return self::SUCCESS;
        }
        $deleted = DB::table('notifications')->where('created_at', '<', now()->subDays((int) $retentionDays))->delete();
        $this->info("Deleted {$deleted} expired notification(s).");

        return self::SUCCESS;
    }
}
