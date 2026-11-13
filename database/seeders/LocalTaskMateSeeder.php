<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\Application;
use App\Enums\TaskStatus;
use App\Enums\ApplicationStatus;
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

        $faculty = User::query()->where('email', 'faculty@example.test')->firstOrFail();
        $organization = User::query()->where('email', 'organization@example.test')->firstOrFail();
        $studentOne = User::query()->where('email', 'student1@example.test')->firstOrFail();
        $studentTwo = User::query()->where('email', 'student2@example.test')->firstOrFail();
        $categories = array_keys(config('taskmate.categories', []));
        $modes = array_keys(config('taskmate.work_modes', []));

        for ($index = 1; $index <= 12; $index++) {
            $mode = $modes[($index - 1) % count($modes)];
            $poster = $index % 3 === 0 ? $organization : $faculty;
            $title = 'Synthetic approved demo task '.$index;
            $task = Task::query()->firstOrCreate(
                ['title' => $title],
                [
                    'poster_id' => $poster->id,
                    'category' => $categories[($index - 1) % count($categories)],
                    'description' => 'Synthetic demonstration task for local testing only. This task contains no real offer or location.',
                    'required_activities' => 'Complete a clearly described sample activity for this local demo.',
                    'deliverables' => 'Provide the synthetic sample deliverable described for this local test.',
                    'estimated_duration_hours' => ($index % 5) + 1,
                    'skill_requirements' => 'Synthetic general skills only.',
                    'complexity' => ['low', 'medium', 'high'][($index - 1) % 3],
                    'budget' => 500 + ($index * 250),
                    'work_mode' => $mode,
                    'workplace_name' => $mode === 'remote' ? null : 'Synthetic Demo Worksite '.$index,
                    'workplace_address' => $mode === 'remote' ? null : 'Synthetic campus location '.$index,
                    'physical_demand' => ['none', 'light', 'moderate'][($index - 1) % 3],
                    'academic_involvement' => 'none',
                    'hazard_exposure' => false,
                    'hazard_description' => null,
                    'private_meeting_required' => false,
                    'workplace_setting' => $mode === 'remote' ? null : ['on_campus', 'off_campus_public'][($index - 1) % 2],
                    'deadline' => null,
                    'status' => TaskStatus::Approved,
                    'guidelines_confirmed_at' => now(),
                    'decision_source' => 'admin',
                    'risk_level' => 'low',
                    'risk_score' => 0,
                    'screened_at' => now(),
                    'reviewed_by' => User::query()->where('email', 'admin@example.test')->value('id'),
                    'reviewed_at' => now(),
                    'review_note' => null,
                ],
            );
            $task->status = TaskStatus::Approved;
            $task->decision_source = 'admin';
            $task->risk_level = 'low';
            $task->risk_score = 0;
            $task->screened_at = now();
            $task->reviewed_by = User::query()->where('email', 'admin@example.test')->value('id');
            $task->reviewed_at = now();
            $task->review_note = null;
            $task->save();

            if ($index <= 3) {
                $applicant = $index === 3 ? $studentTwo : $studentOne;
                Application::query()->firstOrCreate(
                    ['task_id' => $task->id, 'applicant_id' => $applicant->id],
                    [
                        'message' => 'Synthetic application message '.$index.' for local testing only.',
                        'proposed_amount' => 1000 + ($index * 100),
                        'status' => ApplicationStatus::Pending,
                    ],
                );
            }
        }
    }
}
