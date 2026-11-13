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
            $table->string('user_type')->default('student');
            $table->boolean('is_admin')->default(false);
            $table->string('id_number')->nullable();

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        DB::table('users')->whereNull('affiliation')->update(['affiliation' => 'Not provided']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('affiliation')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student');
            $table->dropColumn(['user_type', 'is_admin', 'id_number']);
            $table->string('affiliation')->nullable()->change();
        });
    }
};
