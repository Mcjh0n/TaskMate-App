<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction');
            $table->unsignedTinyInteger('overall_experience');
            $table->text('comment')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->timestamps();
            $table->unique(['agreement_id', 'rater_id']);
            $table->index(['ratee_id', 'is_hidden']);
        });

        Schema::create('rating_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->string('dimension');
            $table->unsignedTinyInteger('score');
            $table->unique(['rating_id', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_scores');
        Schema::dropIfExists('ratings');
    }
};
