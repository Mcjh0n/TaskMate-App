<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_browse_filters_idx');
            $table->index(['status', 'category', 'work_mode'], 'tasks_browse_filters_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_browse_filters_idx');
            $table->index(['status', 'category', 'work_mode', 'complexity'], 'tasks_browse_filters_idx');
        });
    }
};
