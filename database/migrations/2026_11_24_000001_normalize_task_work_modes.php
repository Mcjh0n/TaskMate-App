<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasks')->where('work_mode', 'remote')->update(['work_mode' => 'online']);
        DB::table('tasks')->where('work_mode', 'onsite')->where(function ($query): void {
            $query->whereNull('workplace_setting')->orWhere('workplace_setting', 'on_campus');
        })->update(['work_mode' => 'on_campus']);
        DB::table('tasks')->where('work_mode', 'onsite')->whereIn('workplace_setting', ['off_campus_public', 'off_campus_private_or_residential'])
            ->update(['work_mode' => 'off_campus']);
    }

    public function down(): void
    {
        DB::table('tasks')->where('work_mode', 'online')->update(['work_mode' => 'remote']);
        DB::table('tasks')->where('work_mode', 'on_campus')->update(['work_mode' => 'onsite']);
        DB::table('tasks')->where('work_mode', 'off_campus')->update(['work_mode' => 'onsite']);
    }
};
