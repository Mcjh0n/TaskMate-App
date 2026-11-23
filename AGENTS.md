# PROJECT CONTEXT

You are a senior Laravel developer working inside an existing Laravel 13 project named "taskmate". TaskMate is a proposed campus task marketplace for the Central Mindanao University (CMU) community. It helps students find suitable side jobs and helps eligible people and organizations post them. Its main goal is to give users better information before they agree to a task.

TaskMate records agreements and payment status only. Money moves directly between users. TaskMate never processes, holds, recovers, or guarantees payment. Verification, ratings, and administrator review help inform decisions and never guarantee safety or trustworthiness. Automated risk scoring and task screening are disabled; every new or resubmitted task requires an administrator's decision. The requirements are proposed and not all finalized, so every undecided rule must be editable configuration and not hard-coded.

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
- In-app notifications use Laravel's database notification table, synchronous after-commit dispatch through `TaskMateNotifications`, and fixed copy from `TaskMateAlertCatalog`. Keep payloads limited to event/title/body/local target/related IDs; never put identity fields, notes, risk data, rating content, evidence, or conversation text in alerts.
- Notifications poll through `/notifications/summary` only while a signed-in browser tab is visible. There are no email, push, SMS, WebSocket, preference, or scheduler features. Retention is configurable and unset by default; `taskmate:prune-notifications` is manual.
- All new notification routes must be listed in `tests/Fixtures/web-route-access.php`; the route audit test will fail if any route is missing or its middleware changes.
- Conversations are one-to-one per application and use task/application participant IDs, never account type, for authorization. Messages are append-only and private to the two participants. Admins have no conversation browse route; report pages expose only frozen message snapshots, and `/admin/flags` exposes only the single flagged message. Add every new conversation route to `tests/Fixtures/web-route-access.php` and every conversation page to the smoke and privacy tests.
- Conversation polling uses `/messages/{conversation}/updates` while the thread tab is visible and the Phase 12 `notifications.poll_seconds` interval. `/notifications/summary` also reports the signed-in user's unread conversation count. Messages are never placed in notifications, logs, or error output.
- Every new or resubmitted task remains `pending_review` until an administrator approves, rejects, or requests changes. No automated risk level, risk score, screening record, auto-approval, or system block is generated. Legacy task risk columns and `task_screenings` rows are preserved for data history, not used in the active workflow.
- All student accounts may browse approved tasks regardless of verification status; only verified students may apply. Faculty/staff and organization accounts cannot browse or apply.
- New student registrations require exactly ten numeric Student ID digits. Faculty/staff employee ID formats are not constrained by this rule. Names are captured as first, optional middle, and last; legacy full names are never automatically split and remain unchanged until the owner updates their profile.
- A user's email and ID number are shown only on their own dashboard and settings page (and to administrators on the admin user detail page); never add them to shared Inertia props, public profiles, or another user's summaries.
- Task posting budget is capped at ₱999,999.99 in both server validation and the form. This cap does not apply to application suggestions, agreement amounts, or payment event amounts.

# STACK AND ENVIRONMENT

Laravel 13, PHP 8.4, React + TypeScript through Inertia (official starter kit), Laravel built-in authentication, SQLite, and Vite are already installed. Do not reinstall them. The starter kit may include Teams support; do not build on it. The environment is Windows PowerShell and the project path contains spaces, so quote paths.
