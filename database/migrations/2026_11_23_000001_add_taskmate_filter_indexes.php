<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['status', 'deadline', 'created_at'], 'tasks_browse_status_deadline_created_idx');
            $table->index(['status', 'category', 'work_mode', 'complexity'], 'tasks_browse_filters_idx');
            $table->index(['poster_id', 'created_at'], 'tasks_poster_created_idx');
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->index(['applicant_id', 'created_at'], 'applications_applicant_created_idx');
            $table->index(['task_id', 'status'], 'applications_task_status_idx');
        });
        Schema::table('reports', function (Blueprint $table): void {
            $table->index(['priority', 'status', 'created_at'], 'reports_queue_priority_status_created_idx');
            $table->index(['type', 'status', 'created_at'], 'reports_filter_type_status_created_idx');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['verification_status', 'created_at'], 'users_verification_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_verification_created_idx'));
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex('reports_queue_priority_status_created_idx');
            $table->dropIndex('reports_filter_type_status_created_idx');
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_applicant_created_idx');
            $table->dropIndex('applications_task_status_idx');
        });
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_browse_status_deadline_created_idx');
            $table->dropIndex('tasks_browse_filters_idx');
            $table->dropIndex('tasks_poster_created_idx');
        });
    }
};
