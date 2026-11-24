<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $missing = DB::table('applications')
            ->join('tasks', 'tasks.id', '=', 'applications.task_id')
            ->leftJoin('conversations', 'conversations.application_id', '=', 'applications.id')
            ->whereNull('conversations.id')
            ->select('applications.id', 'applications.task_id', 'tasks.poster_id', 'applications.applicant_id', 'applications.created_at', 'applications.updated_at');

        DB::table('conversations')->insertOrIgnoreUsing(
            ['application_id', 'task_id', 'poster_id', 'applicant_id', 'created_at', 'updated_at'],
            $missing,
        );
    }

    public function down(): void
    {
        // These threads can contain participant messages after the migration.
    }
};
