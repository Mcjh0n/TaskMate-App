<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('poster_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['poster_id', 'updated_at']);
            $table->index(['applicant_id', 'updated_at']);
        });

        Schema::create('conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['conversation_id', 'id'], 'conversation_messages_conversation_id_id_idx');
        });

        Schema::create('conversation_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_read_message_id')->nullable()->constrained('conversation_messages')->nullOnDelete();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('conversation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('event');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('report_message_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('conversation_messages')->restrictOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->string('sender_display_name');
            $table->text('body');
            $table->timestamp('sent_at');
            $table->boolean('is_reported_message')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['report_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_message_snapshots');
        Schema::dropIfExists('conversation_events');
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
