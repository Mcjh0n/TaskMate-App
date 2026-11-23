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
            $table->decimal('budget', 10, 2)->nullable()->change();
            $table->decimal('budget_min', 10, 2)->nullable()->after('budget');
            $table->decimal('budget_max', 10, 2)->nullable()->after('budget_min');
            $table->index(['budget_min', 'budget_max'], 'tasks_budget_range_idx');
        });
    }

    public function down(): void
    {
        DB::table('tasks')->whereNull('budget')->update(['budget' => DB::raw('budget_min')]);

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_budget_range_idx');
            $table->dropColumn(['budget_min', 'budget_max']);
            $table->decimal('budget', 10, 2)->nullable(false)->change();
        });
    }
};
