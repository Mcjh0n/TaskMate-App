<?php

return [
    'user_types' => [
        'student' => 'CMU Student',
        'faculty_staff' => 'CMU Faculty / Staff',
        'organization' => 'Office or Organization Representative',
    ],

    // The project team must confirm CMU's real institutional email domain(s).
    'institutional_email_domains' => ['cmu.edu.ph'],

    'students_can_post' => true,
    'require_verification' => true,

    // Add approved task categories here after the team finalizes them.
    'categories' => [],

    // Work modes are ready for team review; task rules are implemented in a later phase.
    'work_modes' => ['onsite', 'remote', 'hybrid'],

    // Add prohibited terms here after the team agrees on the policy.
    'prohibited_keywords' => [],

    // Add higher-risk terms here after the team agrees on the screening policy.
    'high_risk_keywords' => [],
];
