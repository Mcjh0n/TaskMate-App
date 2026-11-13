<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('organization_name')->nullable();
            $table->string('position')->nullable();
            $table->text('supporting_info')->nullable();
            $table->text('verification_note')->nullable();
        });

        DB::table('users')->where('user_type', 'faculty')->update(['user_type' => 'faculty_staff']);
        DB::table('users')->where('user_type', 'external')->update(['user_type' => 'organization']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('affiliation')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('user_type', 'faculty_staff')->update(['user_type' => 'faculty']);
        DB::table('users')->where('user_type', 'organization')->update(['user_type' => 'external']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['organization_name', 'position', 'supporting_info', 'verification_note']);
            $table->string('affiliation')->nullable(false)->change();
        });
    }
};
