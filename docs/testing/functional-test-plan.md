# Functional test plan template

**Template only.** The protocol, participants, scenarios, acceptance thresholds, and schedule have not been finalized. No results are recorded here.

| ID | Workflow | Role | Steps | Expected result | Requirement | Actual result | Pass/Fail |
|---|---|---|---|---|---|---|---|
| F-01 | Verification | Student | Register with configured institutional email and submit details | Account is pending; owner can see own submitted details | FR-01–08 |  |  |
| F-02 | Verification | Admin | Review an account and approve, reject, or request information | Status and audit history reflect the chosen action | FR-05, FR-07 |  |  |
| F-03 | Posting | Verified poster | Submit a complete task and confirm guidelines | Task enters review and screening history is recorded | FR-09–12 |  |  |
| F-04 | Risk screening: low | Admin/poster | Submit a low-risk remote task under current configuration | Review or auto-approval follows configuration; no safety guarantee is implied | FR-13–20 |  |  |
| F-05 | Risk screening: moderate | Admin/poster | Submit task meeting a moderate threshold | Task awaits admin review | FR-18–21 |  |  |
| F-06 | Risk screening: high | Admin/poster | Submit task meeting a high threshold | Task is escalated or blocked per configured action | FR-18–21 |  |  |
| F-07 | Workplace disclosure | Student | Open approved onsite/hybrid listing | Work name, address, setting, and conditions are visible before application | FR-22–29 |  |  |
| F-08 | Application | Student | Apply, withdraw pending application, and inspect status | One application is recorded and status changes are enforced | FR-25–30 |  |  |
| F-09 | Agreement | Poster/student | Propose, revise, send back, confirm same version | Agreement locks only after both confirm the current version | FR-31–34 |  |  |
| F-10 | Completion | Poster/student | Mark delivery and record completion | Each party's allowed action is recorded; completed state is visible | FR-35–39 |  |  |
| F-11 | Payment status | Poster/student | Mark paid, then confirm receipt or report non-receipt | Append-only events derive the displayed state; no money is processed | FR-35–39 |  |  |
| F-12 | Ratings | Poster/student | Submit contextual rating after completion | Correct dimensions and visibility rule apply; reports remain separate | FR-40–43 |  |  |
| F-13 | Reporting | Verified user | File a contextual report with optional evidence | Only allowed parties can view; evidence uses authorized private download | FR-44–46 |  |  |
| F-14 | Moderation and sanctions | Admin/user | Review a report and apply a permitted action | Action is audited and enforcement follows configured rules | FR-47–48 |  |  |
| F-15 | Off-platform flagging | Poster/student | Submit text asking to skip agreement or move chat | Content is saved, flagged, and recipient sees a neutral warning | FR-49 |  |  |
| F-16 | Role access | Guest/student/faculty/org/admin | Attempt routes outside each role's allowed workflow | Guest redirects, non-admin receives 403, and role restrictions are enforced | FR-01–49 |  |  |
