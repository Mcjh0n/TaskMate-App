<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table): void {
            $table->dropUnique(['task_id']);
            $table->index(['task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table): void {
            $table->dropIndex(['task_id', 'status']);
            $table->unique('task_id');
        });
    }
};
