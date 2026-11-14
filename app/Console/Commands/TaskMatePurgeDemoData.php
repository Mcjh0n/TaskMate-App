<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaskMatePurgeDemoData extends Command
{
    protected $signature = 'taskmate:purge-demo-data {--force : Confirm removal of synthetic @example.test demo accounts}';
    protected $description = 'Remove seeded synthetic TaskMate accounts and their related records';

    public function handle(): int
    {
        if (! app()->environment('local')) { $this->error('Demo data can only be purged in the local environment.'); return self::FAILURE; }
        if (! $this->option('force')) { $this->error('Pass --force to purge synthetic demo data.'); return self::FAILURE; }
        $userIds = DB::table('users')->where('email', 'like', '%@example.test')->pluck('id')->all();
        if ($userIds === []) { $this->info('No synthetic demo accounts were found.'); return self::SUCCESS; }

        $taskIds = $this->ids('tasks', 'poster_id', $userIds);
        $applicationIds = $this->ids('applications', 'applicant_id', $userIds, fn ($q) => $q->orWhereIn('task_id', $taskIds));
        $agreementIds = $this->ids('agreements', 'poster_id', $userIds, fn ($q) => $q->orWhereIn('student_id', $userIds)->orWhereIn('task_id', $taskIds)->orWhereIn('application_id', $applicationIds));
        $reportIds = $this->ids('reports', 'reporter_id', $userIds, fn ($q) => $q->orWhereIn('reported_user_id', $userIds)->orWhereIn('task_id', $taskIds)->orWhereIn('agreement_id', $agreementIds));
        $ratingIds = $this->ids('ratings', 'rater_id', $userIds, fn ($q) => $q->orWhereIn('ratee_id', $userIds)->orWhereIn('task_id', $taskIds)->orWhereIn('agreement_id', $agreementIds));
        $flagIds = $this->ids('content_flags', 'source_user_id', $userIds, fn ($q) => $q->orWhereIn('flaggable_id', $taskIds));

        DB::transaction(function () use ($userIds, $taskIds, $applicationIds, $agreementIds, $reportIds, $ratingIds, $flagIds): void {
            foreach (DB::table('report_evidence')->whereIn('report_id', $reportIds)->pluck('stored_path') as $path) {
                \Illuminate\Support\Facades\Storage::disk('evidence')->delete($path);
            }
            $this->delete('rating_scores', 'rating_id', $ratingIds);
            $this->delete('ratings', 'id', $ratingIds);
            $this->delete('agreement_confirmations', 'agreement_version_id', $this->ids('agreement_versions', 'agreement_id', $agreementIds));
            $this->delete('agreement_messages', 'agreement_id', $agreementIds);
            $this->delete('agreement_versions', 'agreement_id', $agreementIds);
            $this->delete('payment_events', 'agreement_id', $agreementIds);
            $this->delete('task_completions', 'agreement_id', $agreementIds);
            $this->delete('report_evidence', 'report_id', $reportIds);
            $this->delete('report_statements', 'report_id', $reportIds);
            $this->delete('user_sanctions', 'user_id', $userIds);
            $this->delete('user_sanctions', 'report_id', $reportIds);
            $this->delete('user_sanctions', 'flag_id', $flagIds);
            $this->delete('user_sanctions', 'issued_by', $userIds);
            $this->delete('user_sanctions', 'lifted_by', $userIds);
            $this->delete('content_flags', 'id', $flagIds);
            $this->delete('task_screenings', 'task_id', $taskIds);
            if (Schema::hasTable('admin_actions')) {
                $subjectIds = array_merge($userIds, $taskIds, $reportIds, $ratingIds, $flagIds, $applicationIds, $agreementIds);
                DB::table('admin_actions')->whereIn('admin_id', $userIds)->orWhere(fn ($query) => $query
                    ->whereIn('subject_type', ['App\\Models\\User', 'App\\Models\\Task', 'App\\Models\\Report', 'App\\Models\\Rating', 'App\\Models\\ContentFlag', 'App\\Models\\Application', 'App\\Models\\Agreement'])
                    ->whereIn('subject_id', $subjectIds))->delete();
            }
            $this->delete('admin_actions', 'admin_id', $userIds);
            $this->delete('reports', 'id', $reportIds);
            $this->delete('agreements', 'id', $agreementIds);
            $this->delete('applications', 'id', $applicationIds);
            $this->delete('tasks', 'id', $taskIds);
            $teamIds = Schema::hasTable('team_members') ? DB::table('team_members')->whereIn('user_id', $userIds)->where('role', 'owner')->pluck('team_id')->all() : [];
            $this->delete('team_invitations', 'invited_by', $userIds);
            $this->delete('team_invitations', 'team_id', $teamIds);
            $this->delete('team_members', 'user_id', $userIds);
            $this->delete('team_members', 'team_id', $teamIds);
            DB::table('users')->whereIn('id', $userIds)->update(['current_team_id' => null]);
            $this->delete('teams', 'id', $teamIds);
            $this->delete('passkeys', 'user_id', $userIds);
            $this->delete('sessions', 'user_id', $userIds);
            $this->delete('users', 'id', $userIds);
        });

        $this->info('Synthetic @example.test accounts and related TaskMate records were removed.');
        return self::SUCCESS;
    }

    private function ids(string $table, string $column, array $values, ?\Closure $extra = null): array
    {
        if (! Schema::hasTable($table)) return [];
        return DB::table($table)->where(function ($query) use ($column, $values, $extra): void {
            $query->whereIn($column, $values);
            if ($extra) $extra($query);
        })->pluck('id')->all();
    }

    private function delete(string $table, string $column, array $values): void
    {
        if ($values !== [] && Schema::hasTable($table)) DB::table($table)->whereIn($column, $values)->delete();
    }
}
