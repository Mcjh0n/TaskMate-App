<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Services\ConversationWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->user_type = $type;
            $user->affiliation = $affiliation;
            $user->id_number = $idNumber;
            $user->organization_name = $organizationName;
            $user->position = $position;
            $user->supporting_info = $supportingInfo;
            if (! $user->exists) {
                $user->password = Hash::make('password');
            }
            $user->email_verified_at ??= now();

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
            $task = $poster->tasks()->where('title', $title)->first();
            if ($task && ! str_starts_with($task->description, 'Synthetic demonstration task for local testing only.')) {
                continue;
            }
            $task ??= $poster->tasks()->make(['title' => $title]);
            $task->poster()->associate($poster);
            $task->title = $title;
            $task->category = (string) $categories[($index - 1) % count($categories)];
            $task->description = 'Synthetic demonstration task for local testing only. This task contains no real offer or location.';
            $task->required_activities = 'Complete a clearly described sample activity for this local demo.';
            $task->deliverables = 'Provide the synthetic sample deliverable described for this local test.';
            $task->estimated_duration_value = ($index % 5) + 1;
            $task->estimated_duration_unit = 'days';
            $task->skill_requirements = 'Synthetic general skills only.';
            $task->complexity = ['low', 'medium', 'high'][($index - 1) % 3];
            $task->budget = 500 + ($index * 250);
            $task->work_mode = (string) $mode;
            $task->workplace_name = $mode === 'online' ? null : 'Synthetic Demo Worksite '.$index;
            $task->workplace_address = $mode === 'online' ? null : 'Synthetic campus location '.$index;
            $task->physical_demand = ['none', 'light', 'moderate'][($index - 1) % 3];
            $task->academic_involvement = 'none';
            $task->hazard_exposure = false;
            $task->hazard_description = null;
            $task->private_meeting_required = false;
            $task->workplace_setting = match ($mode) {
                'online' => null,
                'on_campus' => 'on_campus',
                default => 'off_campus_public',
            };
            $task->status = TaskStatus::Approved;
            $task->guidelines_confirmed_at = now();
            $task->decision_source = 'admin';
            $task->risk_level = null;
            $task->risk_score = null;
            $task->screened_at = null;
            $task->reviewed_by = User::query()->where('email', 'admin@example.test')->value('id');
            $task->reviewed_at = now();
            $task->review_note = null;
            $task->save();

            if ($index <= 3) {
                $applicant = $index === 3 ? $studentTwo : $studentOne;
                $application = $task->applications()->firstOrNew(['applicant_id' => $applicant->id]);
                if ($application->exists && ! str_starts_with($application->message, 'Synthetic application message ')) {
                    continue;
                }
                $application->task()->associate($task);
                $application->applicant()->associate($applicant);
                $application->message = 'Synthetic application message '.$index.' for local testing only.';
                $application->proposed_amount = 1000 + ($index * 100);
                $application->status = ApplicationStatus::Pending;
                $application->save();
                if (! $application->conversation()->exists()) {
                    app(ConversationWorkflow::class)->createForApplication($application);
                }
            }
        }
    }
}
