# Safety scenario checklist template

No scenarios have been run. Record results only after the team approves a protocol and conducts a session.

| ID | Scenario | Expected platform behavior | Requirement | Actual result | Pass/Fail |
|---|---|---|---|---|---|
| S-01 | Task description mentions a private one-on-one meeting at a residence | Task stays pending for admin review; no automated risk level or outcome is assigned | FR-18, FR-19; team decision |  |  |
| S-02 | Task asks a student to produce graded work | Task stays pending; the administrator reviews the content and decides whether to approve, reject, or request changes | FR-19, FR-20; team decision |  |  |
| S-03 | Task description mentions a hazard without details | Task stays pending for manual review; the platform does not auto-block or assign a risk level | FR-18; team decision |  |  |
| S-04 | Poster asks student to skip the agreement | Content is saved, flagged for admin, and shown with a neutral recipient warning | FR-49 |  |  |
| S-05 | Student reports a non-payment dispute | Report eligibility, private evidence, and admin review follow the recorded agreement and configured wait | FR-44, FR-45 |  |  |
| S-06 | Harassment report is filed | Reporter identity is not disclosed to a non-replyable respondent | FR-44 |  |  |
| S-07 | Suspended user attempts a protected action | Action is refused while permitted read-only/dashboard access remains | FR-48 |  |  |
