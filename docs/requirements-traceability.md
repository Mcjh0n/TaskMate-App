# Requirements traceability

Status reflects code and documents at this prototype phase. “Implemented but provisional” means the workflow exists but depends on editable rules that still need approval. Testing/evaluation materials are templates only; no participants or results are represented.

| Requirement | Status | Main route / file | Test coverage |
|---|---|---|---|
| FR-01 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-02 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-03 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-04 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-05 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-06 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-07 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-08 | Implemented but provisional | `registration, verification, admin user review` | `UserAccountRulesTest, AdminVerificationTest` |
| FR-09 | Implemented | `tasks, TaskController, TaskPolicy` | `TaskPostingTest` |
| FR-10 | Implemented | `tasks, TaskController, TaskPolicy` | `TaskPostingTest` |
| FR-11 | Implemented | `tasks, TaskController, TaskPolicy` | `TaskPostingTest` |
| FR-12 | Implemented | `tasks, TaskController, TaskPolicy` | `TaskPostingTest` |
| FR-13 | Not implemented | Automated risk screening was removed; all tasks require an administrator's decision | `TaskReviewWorkflowTest` verifies no automatic decision is made |
| FR-14 | Not implemented | No risk levels or automated prioritization | `TaskReviewWorkflowTest` verifies manual review queue ordering |
| FR-15 | Not implemented | No risk-score-based outcomes | `TaskReviewWorkflowTest` verifies submissions remain pending |
| FR-16 | Not implemented | No keyword-based risk score or block | `TaskReviewWorkflowTest` submits former sample phrases and verifies pending status |
| FR-17 | Not implemented | No automated risk assessment service is active | `TaskReviewWorkflowTest` |
| FR-18 | Implemented but provisional | `TaskController`, admin task review; workplace details are disclosed and decisions are manual | `TaskPostingTest`, `TaskReviewWorkflowTest` |
| FR-19 | Not implemented | Keyword-based risk decisions are disabled; task content is reviewed by an administrator | `TaskReviewWorkflowTest` |
| FR-20 | Not implemented | No automated risk outcomes or escalation | `TaskReviewWorkflowTest` |
| FR-21 | Implemented | Pending tasks can be approved, rejected, or sent back by an admin | `TaskReviewWorkflowTest` |
| FR-22 | Implemented | `BrowseController` discloses workplace details before applying | `BrowseApplicationsTest` |
| FR-23 | Implemented but provisional | Previously saved work-condition values remain visible; new posting form no longer collects the optional questionnaire | `TaskPostingTest`, `BrowseApplicationsTest` |
| FR-24 | Implemented | Workplace address is a stated worksite; no location tracking exists | `TaskPostingTest`, `PrivacyAuditTest` |
| FR-25 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-26 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-27 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-28 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-29 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-30 | Implemented but provisional | `browse, applications` | `BrowseApplicationsTest` |
| FR-31 | Implemented | `agreements` | `AgreementWorkflowTest` |
| FR-32 | Implemented | `agreements` | `AgreementWorkflowTest` |
| FR-33 | Implemented | `agreements` | `AgreementWorkflowTest` |
| FR-34 | Implemented | `agreements` | `AgreementWorkflowTest` |
| FR-35 | Implemented | `completion and payment` | `CompletionPaymentReportsTest` |
| FR-36 | Implemented | `completion and payment` | `CompletionPaymentReportsTest` |
| FR-37 | Implemented | `completion and payment` | `CompletionPaymentReportsTest` |
| FR-38 | Implemented | `completion and payment` | `CompletionPaymentReportsTest` |
| FR-39 | Implemented | `completion and payment` | `CompletionPaymentReportsTest` |
| FR-40 | Implemented but provisional | `ratings` | `RatingsTest` |
| FR-41 | Implemented but provisional | `ratings` | `RatingsTest` |
| FR-42 | Implemented but provisional | `ratings` | `RatingsTest` |
| FR-43 | Implemented but provisional | `ratings` | `RatingsTest` |
| FR-44 | Implemented but provisional | `reports, moderation, sanctions` | `CompletionPaymentReportsTest, PhaseNineSafetyTest` |
| FR-45 | Implemented but provisional | `reports, moderation, sanctions` | `CompletionPaymentReportsTest, PhaseNineSafetyTest` |
| FR-46 | Implemented but provisional | `reports, moderation, sanctions` | `CompletionPaymentReportsTest, PhaseNineSafetyTest` |
| FR-47 | Implemented but provisional | `reports, moderation, sanctions` | `CompletionPaymentReportsTest, PhaseNineSafetyTest` |
| FR-48 | Implemented but provisional | `reports, moderation, sanctions` | `CompletionPaymentReportsTest, PhaseNineSafetyTest` |
| FR-49 | Implemented but provisional | `OffPlatformScanner, content flags` | `PhaseNineSafetyTest` |
| NFR-01 | Implemented but provisional | `authorization and route audit` | `PrivacyAuditTest` / applicable feature tests |
| NFR-02 | Implemented but provisional | `privacy props and data handling doc` | `PrivacyAuditTest` / applicable feature tests |
| NFR-03 | Implemented but provisional | `route and mass-assignment audits` | `PrivacyAuditTest` / applicable feature tests |
| NFR-04 | Implemented but provisional | `append-only models` | `PrivacyAuditTest` / applicable feature tests |
| NFR-05 | Implemented but provisional | `privacy boundary and upload checks` | `PrivacyAuditTest` / applicable feature tests |
| NFR-06 | Documented only | `docs/research-data-separation.md` | `PrivacyAuditTest` / applicable feature tests |
| NFR-07 | Documented only | `docs/privacy-and-data-handling.md DRAFT` | `PrivacyAuditTest` / applicable feature tests |
| NFR-08 | Documented only | `docs/testing/usability-test-plan.md` | `PrivacyAuditTest` / applicable feature tests |
| NFR-09 | Implemented but provisional | `config, rate limits, audits` | `PrivacyAuditTest` / applicable feature tests |
| NFR-10 | Documented only | `synthetic demo/reset tooling` | `PrivacyAuditTest` / applicable feature tests |
| NFR-11 | Documented only | `docs/testing/usability-test-plan.md` | `PrivacyAuditTest` / applicable feature tests |
| NFR-12 | Documented only | `docs/testing/safety-scenarios.md` | `PrivacyAuditTest` / applicable feature tests |
| NFR-13 | Documented only | `docs/testing/matching-relevance-feedback.md` | `PrivacyAuditTest` / applicable feature tests |

## Phase 11 decisions and coverage

The final cancellation and role decisions are implemented in `AgreementWorkflow`, `ApplicationController`, `AgreementPolicy`, `TaskPolicy`, and the task/agreement pages. `AgreementWorkflowTest`, `BrowseApplicationsTest`, `TaskMatePageSmokeTest`, and `PrivacyAuditTest` cover cancellation/reopen, retaining applicants until lock, role-by-record-ID authorization, and the relevant rendered pages. Student posters remain ordinary Students and may also apply to other tasks. The README and `docs/open-decisions.md` define graduate/alumni accounts, ongoing jobs, outside applicants, and faculty/staff applications as out of scope. These boundaries require no additional data model and are not enabled in this prototype.

## Team-added capability

In-app notifications are **Added by team decision, not in the original requirements**. They use Laravel database notifications, `TaskMateAlertCatalog`, workflow-owned after-commit dispatch in `TaskMateNotifications`, `/notifications` routes, and `NotificationBell`. `NotificationsTest`, `PrivacyAuditTest`, and `TaskMatePageSmokeTest` cover safe copy, recipients, ownership, polling, retention pruning, route access, and page rendering. Retention is provisional and unset; polling is configurable and there are no email, push, SMS, WebSocket, scheduled-reminder, or preference features.

In-app conversations are **Added by team decision, not in the original requirements**. One private thread is created with each application and is limited to its poster and applicant. `ConversationWorkflow`, `ConversationController`, `ConversationPolicy`, and report message snapshots define access. `ConversationTest`, `PrivacyAuditTest`, `PhaseNineSafetyTest`, and `TaskMatePageSmokeTest` cover participant access, read-only states, append-only messages, flags, reports, snapshots, and route coverage. Retention and chat handling rules remain provisional.
