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
            $table->text('required_activities')->nullable()->change();
            $table->text('deliverables')->nullable()->change();
            $table->decimal('estimated_duration_hours', 5, 1)->nullable()->change();
            $table->string('complexity')->nullable()->change();
            $table->boolean('hazard_exposure')->nullable()->default(null)->change();
            $table->boolean('private_meeting_required')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('tasks')->whereNull('required_activities')->update(['required_activities' => '']);
        DB::table('tasks')->whereNull('deliverables')->update(['deliverables' => '']);
        DB::table('tasks')->whereNull('estimated_duration_hours')->update(['estimated_duration_hours' => 1]);
        DB::table('tasks')->whereNull('complexity')->update(['complexity' => 'medium']);
        DB::table('tasks')->whereNull('hazard_exposure')->orWhereNull('private_meeting_required')->update([
            'hazard_exposure' => DB::raw('COALESCE(hazard_exposure, 0)'),
            'private_meeting_required' => DB::raw('COALESCE(private_meeting_required, 0)'),
        ]);

        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('required_activities')->nullable(false)->change();
            $table->text('deliverables')->nullable(false)->change();
            $table->decimal('estimated_duration_hours', 5, 1)->nullable(false)->change();
            $table->string('complexity')->nullable(false)->change();
            $table->boolean('hazard_exposure')->nullable(false)->default(false)->change();
            $table->boolean('private_meeting_required')->nullable(false)->default(false)->change();
        });
    }
};
