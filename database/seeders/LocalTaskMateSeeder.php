<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LocalTaskMateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'TaskMate Administrator',
            'email' => 'admin@taskmate.test',
            'user_type' => 'faculty',
            'affiliation' => 'TaskMate Administration',
            'id_number' => 'ADMIN-001',
        ]);
        $admin->is_admin = true;
        $admin->verification_status = 'verified';
        $admin->save();

        foreach ([
            ['student1@taskmate.test', 'student', 'College of Agriculture', 'STU-001'],
            ['student2@taskmate.test', 'student', 'College of Education', 'STU-002'],
            ['faculty@taskmate.test', 'faculty', 'Office of Student Affairs', 'FAC-001'],
            ['staff@taskmate.test', 'faculty', 'Library', 'FAC-002'],
            ['outside@taskmate.test', 'external', 'Malaybalay Community', null],
        ] as [$email, $type, $affiliation, $idNumber]) {
            User::factory()->create([
                'email' => $email,
                'user_type' => $type,
                'affiliation' => $affiliation,
                'id_number' => $idNumber,
            ]);
        }
    }
}
