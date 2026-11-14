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
| In-app messaging | Agreement messages only; no general chat | Decide whether messaging is needed and define moderation and retention |
| Rating visibility, dimensions, window, moderation, appeals | `config/taskmate.php` `ratings` section (after_both, 30 days, min count 3) | Change visibility and fields; approve moderation/appeals before operation |
| Reports: types, respondent replies, priority, rate limits, evidence, response, escalation | `config/taskmate.php` `reports` and `moderation` sections | Approve types, evidence standards, rate limits, handling, contact, and response targets |
| Designated administrative office / escalation | `moderation.sanction_notice_contact` placeholder; office label unset | Supply approved office name, contact path, and response target |
| Sanction rules and appeals | `config/taskmate.php` `sanctions`; code in sanction workflow; appeals not implemented | Approve thresholds and appeals process before real enforcement |
| Off-platform scanning patterns | `offplatform.patterns` sample phrases and patterns for testing | Review false positives/negatives and approve patterns; detection only flags |
| Privacy, consent, retention, deletion, and evidence storage | `docs/privacy-and-data-handling.md` DRAFT; retention is TBD | Approve policy, consent, protected storage, and disposal before real personal data |
| Hosting, backups, keys, and operational security | README and draft privacy document; no production platform selected | Select supported environment and complete security review |
| Research protocol and acceptance thresholds | `docs/testing/*` templates; all counts/thresholds/schedule TBD | Approve protocol and collect research responses outside TaskMate |
| TaskMate team use of starter-kit Teams | Team schema and routes retained, UI hidden | Decide separately whether to remove the unused scaffolding after data review |
