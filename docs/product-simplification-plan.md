# TaskMate Product Simplification Proposal

**Status: Draft for team discussion.** This document records a proposed scope and implementation order. It is not an approved university policy. No database, user workflow, or configuration was changed as part of Phase 0.

## Product goal

Help CMU students find suitable, clearly described side-jobs and help eligible posters agree on the work before it begins. TaskMate records agreements and payment status; it does not handle money or guarantee safety.

## What the current product already includes

The current application supports account verification, task posting and screening, browsing and applications, agreements, completion and payment records, ratings, reports, sanctions, notifications, and private conversations. It also includes separate admin queues and extensive privacy and route audits.

The task form currently requires a title, category, description, required activities, deliverables, estimated duration, complexity, budget, work mode, a rules confirmation, and structured safety answers. Workplace details are required for onsite and hybrid tasks. The browse page offers search, category, work mode, complexity, budget range, and sorting. The dashboard currently contains placeholder space that does not help users choose a next action.

These features are implemented, but several policies and settings remain provisional. This proposal recommends simplifying the user-facing flow first and making no destructive schema changes as a shortcut.

## Proposed first-version scope

### Keep in the main user journey

- Student registration and a clear verification state.
- Browse approved tasks and read the disclosed work, budget, deadline, and work location details.
- Apply to a task and see the application status.
- Let an eligible poster create and review a task and choose an applicant.
- Record the deliverables, amount, and due date in a two-party-confirmed agreement before work begins.
- Record completion and payment status without processing payment.
- Provide a basic report path for serious concerns.
- Keep authorization, privacy protections, server-side validation, and admin review.

### Simplify for the first-version experience

- Make search, category, and work mode the primary browse controls. Put budget range and complexity in optional additional filters.
- Reduce the task form to the task title, category, a clear description of the work and expected result, budget estimate, work mode, optional deadline, and worksite details for in-person work. Keep a short rules confirmation.
- Revisit whether estimated duration, separate activity and deliverable fields, complexity, and each structured safety answer need to block initial submission. Retain only fields needed for a student to make an informed decision or an admin to review a task.
- Make dashboard content role-aware and action-focused. Remove placeholder space and show the next useful action or account state.
- Use concise status labels and explain what the user can do next.

### Defer from the main proposal/demo flow, pending team approval

- Multi-factor risk scoring, keyword score details, and automated risk outcomes.
- Multi-dimension public rating summaries.
- The full sanction and escalation menu.
- Advanced flag-scanning controls and extensive moderation history for ordinary users.
- Complex report variations beyond a clear basic report path.
- Extra notification and conversation behaviors that are not needed to complete the core task journey.

“Defer” means remove from the main user experience or label as future work after review. Existing records, migrations, and working workflows must not be dropped without a data-impact review and explicit team decision.

## Recommended user journeys

### Student

Create an account → browse approved tasks → review the full task details → apply → follow the application status → confirm the agreement before work starts → record completion/payment status → optionally rate or report.

### Poster

Create an account → submit a concise task description → follow admin review → review applicants → confirm the agreement with the selected applicant → record completion/payment status.

### Administrator

Review pending accounts and tasks → approve, request changes, or reject → review reports when needed. Keep advanced history and moderation tools in the admin area.

## Ordered implementation plan after Phase 0

### Phase 1 — Stabilize the baseline and clean the dashboard

- Address the existing failing demo-data purge tests before relying on the suite as a regression baseline.
- Replace dashboard placeholders with the next relevant action and status for each role.
- Check navigation links for dead ends and ensure each role sees relevant actions only.

### Phase 2 — Simplify browse and task details

- Keep search, category, and work mode prominent.
- Move less-used filters into an optional section.
- Make budget, work mode, deadline, and worksite details easy to scan on cards and detail pages.

### Phase 3 — Reduce task-posting friction

- Agree on the minimum task information needed before changing validation.
- Shorten or combine overlapping description fields.
- Revisit which structured safety fields must be required at submission.
- Keep in-person worksite details and essential task rules visible.
- Update validation messages and preserve user input on validation errors.

### Phase 4 — Clarify registration and verification

- Decide whether pending students may browse.
- Explain what each role can do while pending and why verification is required for restricted actions.
- Review each registration field for necessity and privacy.

### Phase 5 — Simplify application and agreement screens

- Keep application submission short and task details visible before applying.
- Make application status and the next step clear.
- Preserve two-party agreement confirmation before work starts, with concise wording.

### Phase 6 — Reduce visible moderation complexity

- Keep the admin review actions needed to handle pending work and reports.
- Hide risk scores and internal review details from ordinary users.
- Identify advanced screening, ratings, reports, and sanction features for future work only after the team approves that scope.

### Phase 7 — Review deferred features and documentation

- Update the landing page and help text to describe the approved first-version scope.
- Mark deferred work clearly in project documentation.
- Do not remove database structures or existing user data as part of interface simplification.

### Phase 8 — Usability and regression review

- Walk through browse, post, apply, agree, complete, and report paths for student, poster, and admin roles.
- Check mobile layout, keyboard navigation, labels, error messages, and privacy boundaries.
- Run the full test suite and build checks; record real results only.
- Collect usability feedback only after the team approves its evaluation protocol.

## Decisions to approve before changing behavior

1. May a pending student browse approved task details, or must verification happen first? Current behavior requires verification before browsing; this proposal preserves that until the team decides otherwise.
2. Which registration fields are necessary at signup, especially student/employee ID and affiliation?
3. Which account types are in the proposal/demo scope: students, faculty/staff posters, and organization posters?
4. Which task fields are essential before submission, and which can be optional?
5. Which safety questions must be answered by every poster, and which apply only to in-person or higher-risk tasks?
6. Which advanced features should remain visible in the proposal/demo, and which should be marked future work?

Recommended starting position: allow students to browse while pending if the team approves; retain verification for applying and posting; keep only task details necessary for informed decisions as required fields; retain the agreement, privacy, and basic reporting protections.

## Baseline findings recorded on 2026-11-22

- Inspected `routes/web.php`, `app/Models/User.php`, the task store/update requests, the shared task form, browse controller, README, open decisions, and requirements traceability.
- `php artisan test`: 251 tests, 249 passed, 2 failed. Both failures are in demo-data purge/reset coverage with SQLite foreign-key violations while deleting tasks that still have related records. The failures are in `tests/Feature/DemoScenarioSeederTest.php`; the stack points to `app/Console/Commands/TaskMatePurgeDemoData.php`. Fix this baseline issue before later UX changes and do not weaken those tests.
- The repository has existing route/privacy audit coverage and role-specific workflow tests; use those to protect behavior while the screens are simplified.
- No test participants, usability results, acceptance scores, or policy approvals are represented in this proposal.

## Phase 1 outcome

- The purge selector now includes synthetic accepted applications, and purge removes their dependent conversation snapshots, events, reads, messages, and conversation rows before their tasks. The demo seed/reset/purge tests pass without removing the manual-record preservation assertions.
- The dashboard placeholders were replaced with role- and account-state-aware next actions. Suspended users do not receive post/apply action links through shared navigation props.
- The baseline test failure noted above was observed before Phase 1 and is covered by the current test run after the fix.

## Phase 2 outcome

- Search, category, work mode, and sort remain visible in the primary browse controls. Complexity and budget range are grouped under a collapsible “More filters” section; active advanced filters reopen the section.
- Task cards now include a short work description and make the budget, work mode, deadline, and stated workplace easier to scan. The task detail page presents budget, work mode, deadline, and worksite before the longer work and condition details.
- Browse access, query validation, filtering, sorting, task visibility, and privacy rules were retained. No schema or route changes were needed.

## Phase 3 outcome

- The posting form now focuses on the task title, category, clear description, budget, work mode, any required worksite details, optional deadline, and the platform rules confirmation.
- Activities, deliverables, estimated hours, skills, complexity, and structured safety answers are available under an optional details section. Validation errors in that section automatically open it again.
- Existing task records were preserved. A new migration allows the optional values to remain empty; task detail pages show when safety answers were not provided rather than implying a negative answer.
- Screening gives missing safety answers no assumed “safe” value and keeps tasks with incomplete structured safety information in the review queue even if automatic approval is enabled.
- Manual comparison in the browser remains for the team; automated feature tests cover minimal posting, optional data, validation, screening, and safe rendering of missing details.

## Phase 4 outcome

- Registration now explains the review steps, distinguishes email confirmation from administrator account review, and describes what each account type can do after approval.
- The current access rule is preserved: unverified accounts cannot browse, post, or apply. In particular, students must be verified before browsing; this phase clarifies that behavior instead of changing authorization.
- Registration fields are explained where they appear: ID numbers support administrator review and stay private; affiliation may appear on profiles; organization names are display names; position and supporting information support administrator review.
- The details-resubmission page repeats those privacy and purpose notes. Existing data, registration requirements, and role permissions were not changed.

## Phase 5 outcome

- The task detail page now directs students to review the full work and condition details before the short application form; the message prompt explains what to include and the suggested amount remains optional.
- My Applications explains what each active or final status means and the next step, including pending applications that remain active while another applicant negotiates.
- Agreement pages explain that both people must confirm the same version before work starts. Confirming, proposing changes, asking for a change, and cancelling before lock use shorter action labels; the legal and payment boundary notice remains visible.
- Application, agreement, and authorization rules were unchanged. No schema changes were needed.

## Phase 6 outcome

- Public rating summaries now show only the overall average and visible rating count. The rating dimensions remain stored and are still available to the two agreement participants in their own agreement view; no rating data was deleted.
- Risk scores, keyword matches, screening history, admin notes, flags, sanctions, and escalation controls remain confined to administrator pages. The privacy audit found these details were already absent from ordinary task, profile, and report props, so those access rules were preserved.
- The detailed admin moderation tools and report categories remain available for review and are listed as advanced scope pending team approval. No server workflow, schema, or stored records changed.
- Automated tests cover the reduced public rating summary and verify that dimension scores remain stored. Phase checks passed: 257 PHP tests (3,571 assertions), 5 UI render tests, TypeScript checking, and the production build. The migration command reported no pending migrations.

## Phase 7 outcome

- The landing page and public “How TaskMate works” page now explain the main journey in order: account review, task posting and review, browsing and applying, agreement confirmation, completion/payment recording, and optional rating or reporting.
- Landing actions match the signed-in user's role and account access. Unverified users are directed to their dashboard rather than shown unavailable posting or application actions.
- The README and open-decisions list now distinguish the proposed core journey from advanced features that remain implemented while the team decides whether they belong in the first-version demo.
- No routes, schema, records, or workflows were removed.
- Phase checks passed: 257 PHP tests (3,571 assertions), 7 UI render tests, TypeScript checking, and the production build. The migration command reported no pending migrations.
