<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('poster_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('negotiating');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('proposed_by')->constrained('users')->restrictOnDelete();
            $table->text('deliverables');
            $table->text('terms');
            $table->decimal('final_amount', 10, 2);
            $table->date('start_date')->nullable();
            $table->date('due_date');
            $table->string('work_mode');
            $table->string('workplace_name')->nullable();
            $table->string('workplace_address')->nullable();
            $table->text('payment_arrangement');
            $table->text('comment')->nullable();
            $table->string('content_hash', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['agreement_id', 'version_number']);
        });

        Schema::create('agreement_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_version_id')->constrained('agreement_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->unique(['agreement_version_id', 'user_id']);
        });

        Schema::create('agreement_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_messages');
        Schema::dropIfExists('agreement_confirmations');
        Schema::dropIfExists('agreement_versions');
        Schema::dropIfExists('agreements');
    }
};
