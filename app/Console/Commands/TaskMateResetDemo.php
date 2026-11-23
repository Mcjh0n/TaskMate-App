<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TaskMateResetDemo extends Command
{
    protected $signature = 'taskmate:reset-demo {--force : Confirm removal and recreation of synthetic demo data}';

    protected $description = 'Reset the local TaskMate database to synthetic demo scenarios';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Demo data can only be reset in the local environment.');

            return self::FAILURE;
        }
        if (! $this->option('force')) {
            $this->error('Pass --force to reset synthetic demo data.');

            return self::FAILURE;
        }
        if ($this->call('taskmate:purge-demo-data', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        return $this->call('taskmate:demo-seed', ['--force' => true]);
    }
}
