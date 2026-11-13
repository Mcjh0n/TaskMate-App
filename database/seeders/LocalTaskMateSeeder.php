<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LocalTaskMateSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Synthetic Project Admin', 'admin@example.test', 'faculty', 'TaskMate Demo Office', 'FAKE-ADMIN-001', true, 'verified'],
            ['Synthetic Student One', 'student1@example.test', 'student', 'Demo College A', 'FAKE-STU-001', false, 'verified'],
            ['Synthetic Student Two', 'student2@example.test', 'student', 'Demo College B', 'FAKE-STU-002', false, 'verified'],
            ['Synthetic Pending Student', 'student.pending@example.test', 'student', 'Demo College C', 'FAKE-STU-003', false, 'pending'],
            ['Synthetic Faculty Staff', 'faculty@example.test', 'faculty', 'Demo Office', 'FAKE-EMP-001', false, 'verified'],
            ['Synthetic Organization Representative', 'organization@example.test', 'organization', 'Example Demo Organization', null, false, 'verified'],
            ['Synthetic Pending Organization Representative', 'organization.pending@example.test', 'organization', 'Example Pending Organization', null, false, 'pending'],
        ];

        foreach ($accounts as [$name, $email, $type, $affiliation, $idNumber, $isAdmin, $status]) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => $email,
                    'user_type' => $type,
                    'affiliation' => $affiliation,
                    'id_number' => $idNumber,
                ]);
            } else {
                $user->name = $name;
                $user->user_type = $type;
                $user->affiliation = $affiliation;
                $user->id_number = $idNumber;
            }

            $user->is_admin = $isAdmin;
            $user->verification_status = $status;
            $user->save();
        }
    }
}
