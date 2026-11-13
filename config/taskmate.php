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

    // PROVISIONAL: the team and adviser must confirm the approved categories.
    'categories' => [
        'tutoring_and_academic_support' => 'Tutoring and academic support',
        'design_and_creative' => 'Design and creative work',
        'writing_and_editing' => 'Writing and editing',
        'data_entry_and_clerical' => 'Data entry and clerical work',
        'event_support' => 'Event support',
        'tech_and_web' => 'Technology and web',
        'errands_and_delivery' => 'Errands and delivery',
        'other' => 'Other',
    ],

    'work_modes' => [
        'onsite' => 'Onsite',
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
    ],

    'complexity_levels' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    // PROVISIONAL: the Job and Task Guidelines must be finalized by the team and university.
    'task_guidelines' => [
        'PROVISIONAL: Tasks must be lawful and appropriate for a campus setting.',
        'PROVISIONAL: Do not request academic dishonesty, such as doing another person’s graded work.',
        'PROVISIONAL: Do not require a private meeting in an unsafe or isolated place.',
        'PROVISIONAL: The budget should be a fair estimate for the described work.',
        'PROVISIONAL: Payment is arranged directly between the poster and the student.',
    ],

    // Add prohibited terms here after the team agrees on the policy.
    'prohibited_keywords' => [],

    // Add higher-risk terms here after the team agrees on the screening policy.
    'high_risk_keywords' => [],
];
