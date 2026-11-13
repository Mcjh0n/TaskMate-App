<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivered_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_note')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event');
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('paid_on')->nullable();
            $table->string('method')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->text('description');
            $table->string('status')->default('open');
            $table->string('outcome')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['agreement_id', 'status']);
        });

        Schema::create('report_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role');
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('report_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('stored_path')->unique();
            $table->string('mime');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_evidence');
        Schema::dropIfExists('report_statements');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('task_completions');
    }
};
