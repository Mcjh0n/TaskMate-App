<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedSmallInteger('estimated_duration_days')->nullable()->after('estimated_duration_hours');
        });

        Schema::table('agreement_versions', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->change();
            $table->unsignedSmallInteger('duration_days')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('agreement_versions', function (Blueprint $table): void {
            $table->dropColumn('duration_days');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('estimated_duration_days');
        });
    }
};
