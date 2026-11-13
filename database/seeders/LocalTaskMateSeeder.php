<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LocalTaskMateSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Synthetic Project Admin', 'admin@example.test', 'faculty_staff', 'TaskMate Demo Office', 'FAKE-ADMIN-001', null, null, null, true, 'verified'],
            ['Synthetic Student One', 'student1@example.test', 'student', 'Demo College A', 'FAKE-STU-001', null, null, null, false, 'verified'],
            ['Synthetic Student Two', 'student2@example.test', 'student', 'Demo College B', 'FAKE-STU-002', null, null, null, false, 'verified'],
            ['Synthetic Pending Student', 'student.pending@example.test', 'student', 'Demo College C', 'FAKE-STU-003', null, null, null, false, 'pending'],
            ['Synthetic Faculty Staff', 'faculty@example.test', 'faculty_staff', 'Demo Office', 'FAKE-EMP-001', null, null, null, false, 'verified'],
            ['Synthetic Organization Representative', 'organization@example.test', 'organization', null, null, 'Example Demo Organization', 'Synthetic Representative', 'Synthetic organization created for local testing.', false, 'verified'],
            ['Synthetic Pending Organization Representative', 'organization.pending@example.test', 'organization', null, null, 'Example Pending Organization', 'Synthetic Representative', 'Synthetic organization awaiting local test verification.', false, 'pending'],
        ];

        foreach ($accounts as [$name, $email, $type, $affiliation, $idNumber, $organizationName, $position, $supportingInfo, $isAdmin, $status]) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => $email,
                    'user_type' => $type,
                    'affiliation' => $affiliation,
                    'id_number' => $idNumber,
                    'organization_name' => $organizationName,
                    'position' => $position,
                    'supporting_info' => $supportingInfo,
                ]);
            } else {
                $user->name = $name;
                $user->user_type = $type;
                $user->affiliation = $affiliation;
                $user->id_number = $idNumber;
                $user->organization_name = $organizationName;
                $user->position = $position;
                $user->supporting_info = $supportingInfo;
            }

            $user->is_admin = $isAdmin;
            $user->verification_status = $status;
            $user->save();
        }
    }
}
