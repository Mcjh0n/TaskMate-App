<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_flags', function (Blueprint $table): void {
            $table->index(['category', 'status', 'created_at'], 'content_flags_category_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('content_flags', function (Blueprint $table): void {
            $table->dropIndex('content_flags_category_status_created_idx');
        });
    }
};
