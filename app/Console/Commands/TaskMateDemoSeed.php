<?php

namespace App\Console\Commands;

use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Console\Command;

class TaskMateDemoSeed extends Command
{
    protected $signature = 'taskmate:demo-seed {--force : Confirm local synthetic demo data creation}';

    protected $description = 'Create synthetic local TaskMate demo scenarios';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Demo data can only be seeded in the local environment.');

            return self::FAILURE;
        }
        if (! $this->option('force')) {
            $this->error('Pass --force to create synthetic demo data.');

            return self::FAILURE;
        }
        $this->call(DemoScenarioSeeder::class);
        $this->info('Synthetic local demo scenarios are ready.');

        return self::SUCCESS;
    }
}
