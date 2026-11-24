<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedInteger('estimated_duration_value')->nullable()->after('estimated_duration_days');
            $table->string('estimated_duration_unit', 10)->nullable()->after('estimated_duration_value');
        });

        DB::table('tasks')->whereNotNull('estimated_duration_days')->update([
            'estimated_duration_value' => DB::raw('estimated_duration_days'),
            'estimated_duration_unit' => 'days',
        ]);

        Schema::table('agreement_versions', function (Blueprint $table): void {
            $table->unsignedInteger('duration_value')->nullable()->after('duration_days');
            $table->string('duration_unit', 10)->nullable()->after('duration_value');
        });

        // Keep historical agreement content hashes stable. The model formats
        // legacy duration_days values without changing those immutable rows.
    }

    public function down(): void
    {
        Schema::table('agreement_versions', function (Blueprint $table): void {
            $table->dropColumn(['duration_value', 'duration_unit']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['estimated_duration_value', 'estimated_duration_unit']);
        });
    }
};
