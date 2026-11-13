<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // Keep tasks attributable and prevent a poster account being removed while tasks remain.
            $table->foreignId('poster_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('category');
            $table->text('description');
            $table->text('required_activities');
            $table->text('deliverables');
            $table->decimal('estimated_duration_hours', 5, 1);
            $table->text('skill_requirements')->nullable();
            $table->string('complexity');
            $table->decimal('budget', 10, 2);
            $table->string('work_mode');
            $table->string('workplace_name')->nullable();
            $table->string('workplace_address')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('pending_review');
            $table->timestamp('guidelines_confirmed_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
