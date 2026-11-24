# TaskMate

TaskMate is a prototype campus task marketplace for the Central Mindanao University community. It helps students review task details before applying and helps eligible posters record reviewed tasks and agreements.

## Account types

Students may browse approved tasks whether their verification is pending, rejected, or awaiting more information. Only verified students may apply. Student posting is enabled while `students_can_post` is true. Student registration requires a ten-digit numeric Student ID and collects one full name. Letters are removed from the Student ID as the user types or pastes; the server also enforces exactly ten digits. Master's and doctoral students use the ordinary Student account type. Faculty/staff may post tasks and their employee ID format is unchanged. Approved office or organization representatives may post tasks. Faculty/staff and organizations cannot apply.

**Prototype: use synthetic accounts and sample data only. Do not enter real identity or contact details.** TaskMate records agreements and payment status only. Payments move directly between users; TaskMate does not hold, process, recover, or guarantee payment. Verification, administrator review, and ratings do not guarantee safety.

## Proposed first-version journey

The simplified first-version proposal focuses on account review, clear task posting and review, student browsing and applications, agreement confirmation before work, and recording completion and payment status. Accepting an applicant prepares a draft agreement from the task and application; the poster reviews and sends it, then the applicant confirms or requests a change. Posters can enter one estimated amount or a minimum–maximum budget range. Each task-posting amount is capped at ₱999,999.99; this limit does not restrict application suggestions or later agreed/payment amounts. Ratings and reports support this journey but are optional; serious concerns belong in reports, not rating comments.

New ratings use one overall score and an optional comment. Authorized viewers can still see dimension scores submitted through the earlier rating form. The repository also contains detailed report categories and escalation, sanctions, off-platform flag scanning, notifications, and conversations. See [the product simplification proposal](docs/product-simplification-plan.md) and [open decisions](docs/open-decisions.md).

## Requirements

- PHP 8.4, Composer, Node.js and npm, SQLite, and the Laravel/Vite requirements in the installed lockfiles.
- A project path without spaces is recommended on Windows. For this project, PHPUnit is configured to rely on Composer's test launcher instead of the unreadable bootstrap path; `php artisan test` works from the current path with spaces.

## Local setup

```text
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
composer run dev  # optional local server and asset processes
npm run build
```

The `.env` file is git-ignored. Keep `APP_DEBUG=false` outside local development; never expose stack traces or secrets in a shared environment. Review session cookie settings (`SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`) for the deployment and use HTTPS. Web forms use Laravel CSRF protection. Password rules are defined in the starter authentication configuration; production requires at least 12 characters with mixed case, numbers, symbols, and a breach check.

The project's `phpunit.xml` omits the redundant bootstrap path because this Windows PHP runtime reports spaced workspace paths as unreadable to PHPUnit. The normal `php artisan test` command uses Composer autoloading and is verified to work here.

The signed-in user's email and ID number appear on their own dashboard and profile settings only. Existing full names are retained as saved and edited as one field. The nullable `first_name`, `middle_name`, and `last_name` columns added during prototyping are retained for compatibility with existing databases, but the app no longer writes or reads them.

## Synthetic local accounts

Local seeding is guarded by `APP_ENV=local`. Password for synthetic users: `password` (local testing only; do not reuse outside this synthetic prototype).

| Email                               | Role                               | Initial state  |
| ----------------------------------- | ---------------------------------- | -------------- |
| `admin@example.test`                | Administrator and faculty/staff    | Verified admin |
| `student1@example.test`             | Student                            | Verified       |
| `student2@example.test`             | Student                            | Verified       |
| `student.pending@example.test`      | Student                            | Pending        |
| `faculty@example.test`              | Faculty/staff                      | Verified       |
| `organization@example.test`         | Office/organization representative | Verified       |
| `organization.pending@example.test` | Office/organization representative | Pending        |

All names, IDs, tasks, locations, messages, reports, and payment records are synthetic. Fake identity numbers are clearly marked.

## Common commands

```text
php artisan migrate
php artisan db:seed
composer run dev  # optional local server and asset processes
php artisan taskmate:make-admin someone@example.test
php artisan taskmate:demo-seed --force
php artisan taskmate:reset-demo --force
php artisan taskmate:purge-demo-data --force
php artisan taskmate:prune-notifications
php artisan test
npm run build
composer audit
npm audit
```

The local and demo seeders can be run repeatedly: synthetic users use a fixed `@example.test` email list, synthetic tasks use the `Synthetic approved demo task` or `Synthetic scenario:` title prefixes, and related demo records use stable relationships and keys. The demo seed, reset, and purge commands are local-only and require `--force`. Purging targets those known synthetic accounts and marked demo tasks; it does not select unrelated users or tasks by ID. The separate `scripts/sus-score.php` reads anonymous CSV only and has no database connection.

## Configuration

Edit `config/taskmate.php` for institutional email domains, role posting rules, task categories/guidelines, browse/applications, agreement terms, report types/evidence limits, moderation placeholders, off-platform sample patterns, sanctions, ratings, notifications, messages, prototype mode, and rate limits. Every new or resubmitted task waits for an administrator's decision. Automated risk scoring, auto-approval, and automatic blocking have been removed. Many other rules are explicitly provisional; see [open decisions](docs/open-decisions.md).

One private, text-only conversation is created for each application. Only the task poster and applicant can read the thread. Administrators can see limited context from a user report or a single message linked to a flag. Automatic keyword flagging is paused by default. `TASKMATE_OFFPLATFORM_SCANNING` enables it for testing, and existing flags remain available for review. Message retention is undecided (`messages.retention_days` defaults to `null`). See [open decisions](docs/open-decisions.md) before using real data.

In-app alerts use the `notifications` config section. They poll while a signed-in tab is visible; email, push, and SMS are not used. `taskmate:prune-notifications` deletes alerts older than `notifications.retention_days`; it does nothing while that value is `null`, and no scheduler is configured.

## Project guide

- `app/Http`, `app/Models`, `app/Policies`, `app/Services`: workflows and authorization.
- `resources/js`: Inertia React/TypeScript pages and shared UI.
- `database/migrations`, `database/factories`, `database/seeders`: schema and synthetic data.
- `docs/requirements-traceability.md`: status of FR-01–FR-49 and NFR-01–NFR-13.
- `docs/open-decisions.md`: decisions awaiting the team.
- `docs/privacy-and-data-handling.md`: DRAFT data inventory and retention checklist.
- `docs/research-data-separation.md`: rules for keeping research data outside TaskMate.
- `docs/testing`: blank functional, safety, usability, SUS, and relevance-feedback templates.

## Known limitations

Task categories, workplace validation, rating visibility, reports, sanctions, contact/escalation details, retention, consent, hosting, and evaluation protocols need team and university decisions. Automated task risk screening is no longer active; prior screening records remain in storage but are not used or displayed. There is no recommendation algorithm. Appeals are not implemented. Starter-kit Teams migrations, routes, and data structures are retained, but TaskMate hides the team switcher and Teams settings/navigation UI. No study has been run and this repository contains no participant responses or test results.
