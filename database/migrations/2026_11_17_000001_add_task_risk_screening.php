<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('physical_demand')->nullable();
            $table->string('academic_involvement')->nullable();
            $table->boolean('hazard_exposure')->default(false);
            $table->text('hazard_description')->nullable();
            $table->boolean('private_meeting_required')->default(false);
            $table->string('workplace_setting')->nullable();
            $table->string('risk_level')->nullable();
            $table->integer('risk_score')->nullable();
            $table->string('decision_source')->nullable();
            $table->timestamp('screened_at')->nullable();
        });

        Schema::create('task_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('risk_level');
            $table->unsignedInteger('risk_score');
            $table->json('factors');
            $table->json('keyword_hits');
            $table->json('hard_triggers');
            $table->string('outcome');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_screenings');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'physical_demand', 'academic_involvement', 'hazard_exposure', 'hazard_description',
                'private_meeting_required', 'workplace_setting', 'risk_level', 'risk_score',
                'decision_source', 'screened_at',
            ]);
        });
    }
};
