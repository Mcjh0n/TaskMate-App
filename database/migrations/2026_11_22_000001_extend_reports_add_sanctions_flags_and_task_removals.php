<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('priority')->default('normal')->after('type');
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('escalation_note')->nullable();
            $table->text('resolution_message')->nullable();
            $table->nullableMorphs('subject');
            $table->index(['reporter_id', 'reported_user_id', 'task_id', 'type', 'status'], 'reports_duplicate_context_idx');
        });

        Schema::create('user_sanctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('reason');
            $table->text('internal_note')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flag_id')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('lift_note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type', 'lifted_at', 'starts_at', 'ends_at'], 'user_sanctions_active_idx');
        });

        Schema::create('content_flags', function (Blueprint $table): void {
            $table->id();
            $table->morphs('flaggable');
            $table->foreignId('source_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->json('matched');
            $table->string('field');
            $table->string('status')->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['flaggable_type', 'flaggable_id', 'category'], 'content_flags_unique_subject_category');
            $table->index(['status', 'created_at']);
        });

        Schema::table('user_sanctions', function (Blueprint $table): void {
            $table->foreign('flag_id')->references('id')->on('content_flags')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('removal_reason')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('removed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('removed_by');
            $table->dropColumn(['removal_reason', 'removed_at']);
        });
        Schema::dropIfExists('content_flags');
        Schema::dropIfExists('user_sanctions');
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex('reports_duplicate_context_idx');
            $table->dropMorphs('subject');
            $table->dropConstrainedForeignId('escalated_by');
            $table->dropColumn(['priority', 'escalated_at', 'escalation_note', 'resolution_message']);
        });
    }
};
