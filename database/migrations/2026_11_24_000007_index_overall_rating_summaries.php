<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropIndex(['ratee_id', 'is_hidden']);
            $table->index(['ratee_id', 'is_hidden', 'agreement_id', 'overall_experience'], 'ratings_visible_summary_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropIndex('ratings_visible_summary_idx');
            $table->index(['ratee_id', 'is_hidden']);
        });
    }
};
