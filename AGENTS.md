# PROJECT CONTEXT

You are a senior Laravel developer working inside an existing Laravel 13 project named "taskmate". TaskMate is a proposed campus task marketplace for the Central Mindanao University (CMU) community. It helps students find suitable side jobs and helps eligible people and organizations post them. Its main goal is to give users better information before they agree to a task.

TaskMate records agreements and payment status only. Money moves directly between users. TaskMate never processes, holds, recovers, or guarantees payment. Verification, ratings, and risk screening only inform decisions and never guarantee safety or trustworthiness. The requirements are proposed and not all finalized, so every undecided rule must be editable configuration and not hard-coded.

# ROLES (final)

1. Student (`user_type = student`): registers with institutional email and student ID. Can browse approved tasks and apply. Posting is allowed only if `config('taskmate.students_can_post')` is true (default true).
2. Faculty/Staff (`user_type = faculty_staff`): registers with institutional email and employee ID. Can post tasks. Cannot apply.
3. Office/Organization representative (`user_type = organization`): registers with organization name, representative position, and a short supporting description. Can post tasks only after admin approval. Cannot apply. This is the only way for people outside the campus to join.
4. Administrator: a separate boolean flag (`is_admin`), set only by an artisan command, never through registration or mass assignment. The admins are the project team.

# WORKING RULES

1. Inspect first. Read `routes/web.php`, `app/Models/User.php`, the registration action and request classes, `resources/js/pages`, layouts, and components. Follow existing conventions (naming, UI components, route helpers).
2. Do only the phases I name, in order. After each phase, stop and report: files changed, how I can test manually, assumptions made. Then wait for me.
3. Adapt, do not duplicate. I may already have an earlier migration or code with `user_type` values (`student`, `faculty`, `external`), `is_admin`, `verification_status`, `affiliation`, or `id_number`, or an `EnsureUserHasRole` middleware. Find these and reconcile them with this spec. If a migration has already been run, write a new migration to change the schema. Remove obsolete leftovers such as the role middleware. Tell me what you changed.
4. Security: never make `is_admin` or `verification_status` mass-assignable. Set them explicitly in code. Validate everything with Form Requests or validation rules. Never log or flash sensitive fields.
5. Privacy (NFR-02, 04, 05): collect only what is needed. Do NOT add upload fields for ID documents or face photos. Seed data must be synthetic. Raw identity fields (`id_number`, supporting description) are shown only to the owner and administrators.
6. Every phase needs migrations, model updates, factories, feature tests in the test framework already configured, and React pages. Run `php artisan migrate`, `php artisan test`, and `npm run build`. Do NOT run `composer run dev` or any long-running server; I run that myself.
7. Add no new packages unless essential. Ask first.
8. All user-facing text is in plain, clear English.
9. If git is initialized, commit after each phase with a clear message.

# Final architecture notes (Phase 10)

- This prototype uses Laravel web routes, Form Requests, policies/middleware, Eloquent models, backed enums, SQLite migrations, and React/TypeScript Inertia pages.
- TaskMate workflow rules and provisional lists belong in `config/taskmate.php`; never duplicate those lists in controllers or pages.
- Private report evidence lives on the `evidence` filesystem disk and is downloaded only through an authorized route. Research responses stay outside the TaskMate database.
- Raw identity fields are limited to the account owner and administrator user detail. Do not add these fields to globally shared Inertia props or summary lists.
- Starter-kit Teams database structures and routes remain for compatibility, but TaskMate navigation and Teams settings links are hidden. Do not add new TaskMate features that depend on Teams.
- All research, SUS, safety, and functional evaluation documents are templates until the team approves a protocol. Never add fabricated participant data or results.
- Every newly registered web route must be reviewed and added explicitly to `tests/Fixtures/web-route-access.php`; `PrivacyAuditTest` fails when a route is missing or its middleware changes.
- A task can have multiple agreement records over time after a negotiating agreement is cancelled. `Task::agreement()` resolves to the latest record; use agreement `poster_id`/`student_id` and task `poster_id` as the authoritative participant roles. Do not infer task or agreement permissions from account type.
- Cancelling a negotiating agreement cancels its accepted application and reopens the task as approved. Other pending applications remain active until a later agreement locks; locking declines the remaining pending applications.
- Master's and doctoral students use the Student account type. Ongoing jobs, separate graduate/alumni accounts, outside applicants, and faculty/staff applications are out of scope for this version.

# STACK AND ENVIRONMENT

Laravel 13, PHP 8.4, React + TypeScript through Inertia (official starter kit), Laravel built-in authentication, SQLite, and Vite are already installed. Do not reinstall them. The starter kit may include Teams support; do not build on it. The environment is Windows PowerShell and the project path contains spaces, so quote paths.
