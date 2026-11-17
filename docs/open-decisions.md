# Open decisions for the project team

Every item below is unresolved or provisional. Current behavior is not university-approved policy.

| Decision | Current location and provisional default | If decision differs |
|---|---|---|
| Who can post and what verification each role needs | `config/taskmate.php`: students can post by default; verification required | Change `students_can_post`, `require_verification`, role checks, and tests |
| Institutional email domains | `institutional_email_domains`: placeholder `cmu.edu.ph` | Confirm domains and update registration validation |
| Task fields, categories, work modes, complexity | `categories`, `work_modes`, `complexity_levels`; categories marked provisional | Edit labels/keys and update any related data migration only with a data plan |
| Task guidelines and prohibited work | `task_guidelines`, `prohibited_categories`, `prohibited_keywords` | Approve the rule list and adjust validation/moderation behavior |
| Risk questions, point rules, thresholds, escalation and auto-approval | `risk_questions`, `risk_rules`, `high_risk_action`, `auto_approval_enabled` (off) | Edit the configuration; retain screening history and re-review rules before enabling automation |
| Workplace validation | `in_person_requires_admin_review` (true); actual criteria undecided | Define criteria before changing automatic review behavior |
| Task and application status rules | `app/Enums/TaskStatus.php`, `app/Enums/ApplicationStatus.php` | Confirm state transitions and adjust policies/workflows/tests |
| In-app messaging | `config/taskmate.php` `messages`; one private thread per application, text only, permanent messages, and no attachments | Confirm retention, harassment handling, and whether contact or payment details may be shared inside chat. Messages remain participant-only except a reported snapshot or a single scanner-flagged message. |
| Rating visibility, dimensions, window, moderation, appeals | `config/taskmate.php` `ratings` section (after_both, 30 days, min count 3) | Change visibility and fields; approve moderation/appeals before operation |
| Reports: types, respondent replies, priority, rate limits, evidence, response, escalation | `config/taskmate.php` `reports` and `moderation` sections | Approve types, evidence standards, rate limits, handling, contact, and response targets |
| Designated administrative office / escalation | `moderation.sanction_notice_contact` placeholder; office label unset | Supply approved office name, contact path, and response target |
| Sanction rules and appeals | `config/taskmate.php` `sanctions`; code in sanction workflow; appeals not implemented | Approve thresholds and appeals process before real enforcement |
| Off-platform scanning patterns | `offplatform.patterns` sample phrases and patterns for testing | Review false positives/negatives and approve patterns; detection only flags |
| Privacy, consent, retention, deletion, and evidence storage | `docs/privacy-and-data-handling.md` DRAFT; retention is TBD | Approve policy, consent, protected storage, and disposal before real personal data |
| Hosting, backups, keys, and operational security | README and draft privacy document; no production platform selected | Select supported environment and complete security review |
| Research protocol and acceptance thresholds | `docs/testing/*` templates; all counts/thresholds/schedule TBD | Approve protocol and collect research responses outside TaskMate |
| TaskMate team use of starter-kit Teams | Team schema and routes retained, UI hidden | Decide separately whether to remove the unused scaffolding after data review |
| In-app notification retention | `config/taskmate.php` `notifications.retention_days` is `null` (keep all alerts); no schedule runs automatically | Set an approved retention period and invoke `taskmate:prune-notifications` through an approved operational process |
| Future email or WebSocket delivery | Current final decision is in-app alerts with visible-tab polling every 10 seconds; no email, push, SMS, WebSockets, or notification preferences | Revisit only through a later team decision; review privacy, delivery, availability, packages, and operational costs before expanding channels |
| Notification copy and polling | `app/Notifications/TaskMateAlertCatalog.php`; `config/taskmate.php` `notifications` (10 seconds, 20 per page, 120 summary requests per minute) | Review event copy and poll limits; keep wording privacy-safe and allow multiple visible tabs |
| Conversation retention | `config/taskmate.php` `messages.retention_days` is `null`; conversations and messages are append-only and currently kept | Approve a retention period and any deletion/anonymization process before adding a purge mechanism |
| Harassment handling in conversations | Message reports use the Phase 9 report types and only share the reported message plus configured context | Approve response expectations and safeguarding procedures; do not expose full private threads to administrators |
| Contact and payment details in chat | No message filtering blocks contact or payment details; links remain plain text; off-platform rules flag only configured bypass requests | Decide whether contact/payment details may be shared in chat and whether warnings or additional review are needed; do not conflate direct payment arrangements with bypass requests |

## Out of scope for this version

- Ongoing jobs are not supported. Tasks are individual, time-bounded work records; a different model and workflow would be needed for recurring employment.
- Graduate/alumni accounts are not supported as a separate account type. Master's and doctoral students use the ordinary Student account type.
- Outside applicants are not supported. Applicants must be verified CMU students.
- Faculty/staff applying to tasks is not supported. Faculty/staff can post only.

## Future work

The team may revisit ongoing jobs, graduate/alumni accounts, outside applicants, and faculty/staff applications in a later version. Those changes would require approved eligibility rules, registration/verification changes, and task/application workflow updates; no implementation is included now.
