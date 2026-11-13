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

    // PROVISIONAL: the team, adviser, and university must approve these screening questions and labels.
    'risk_questions' => [
        'physical_demand' => [
            'none' => 'None',
            'light' => 'Light',
            'moderate' => 'Moderate',
            'heavy' => 'Heavy',
        ],
        'academic_involvement' => [
            'none' => 'None',
            'supporting' => 'Supporting academic work',
            'produces_graded_work' => 'Produces another person’s graded work',
        ],
        'workplace_setting' => [
            'on_campus' => 'On campus',
            'off_campus_public' => 'Off campus, public place',
            'off_campus_private_or_residential' => 'Off campus, private or residential place',
        ],
    ],

    // PROVISIONAL: the team, adviser, and university must approve all scoring rules and thresholds.
    'risk_rules' => [
        'points' => [
            'category_base' => [
                'tutoring_and_academic_support' => 0,
                'design_and_creative' => 0,
                'writing_and_editing' => 0,
                'data_entry_and_clerical' => 0,
                'event_support' => 0,
                'tech_and_web' => 0,
                'errands_and_delivery' => 0,
                'other' => 0,
                'default' => 0,
            ],
            'physical_demand' => ['none' => 0, 'light' => 0, 'moderate' => 1, 'heavy' => 2],
            'academic_involvement' => ['none' => 0, 'supporting' => 0, 'produces_graded_work' => 0],
            'hazard_exposure' => ['no' => 0, 'yes' => 2],
            'private_meeting_required' => ['no' => 0, 'yes' => 3],
            'workplace_setting' => [
                'on_campus' => 0,
                'off_campus_public' => 1,
                'off_campus_private_or_residential' => 3,
            ],
            'keyword_per_hit' => 2,
        ],
        'keyword_max_points' => 4,
        'thresholds' => ['moderate_from' => 3, 'high_from' => 7],
        'hard_triggers' => [
            // PROVISIONAL: fill with categories the team, adviser, and university prohibit.
            'prohibited_categories' => [],
            'academic_involvement' => 'produces_graded_work',
        ],
    ],

    // PROVISIONAL: the team, adviser, and university must approve prohibited tasks and terms.
    'prohibited_categories' => [],
    // PROVISIONAL: sample phrases for testing only; replace after team, adviser, and university review.
    'prohibited_keywords' => ['take my exam', 'do my homework', 'write my thesis', 'answer key'],
    // PROVISIONAL: sample phrases for testing only; replace after team, adviser, and university review.
    'risk_keywords' => ['heavy lifting', 'power tools', 'late at night', 'meet alone'],

    // PROVISIONAL: the Job and Task Guidelines must be finalized by the team and university.
    'task_guidelines' => [
        'PROVISIONAL: Tasks must be lawful and appropriate for a campus setting.',
        'PROVISIONAL: Do not request academic dishonesty, such as doing another person’s graded work.',
        'PROVISIONAL: Do not require a private meeting in an unsafe or isolated place.',
        'PROVISIONAL: The budget should be a fair estimate for the described work.',
        'PROVISIONAL: Payment is arranged directly between the poster and the student.',
    ],

    // Retained for compatibility with earlier drafts; Phase 4 uses risk_keywords.
    'high_risk_keywords' => [],

    // PROVISIONAL: enable only after the team and university approve the risk rules.
    'auto_approval_enabled' => false,
    // PROVISIONAL: workplace-validation criteria are not decided; keep in-person tasks in review.
    'in_person_requires_admin_review' => true,
    // PROVISIONAL: the team, adviser, and university must approve this response.
    'high_risk_action' => 'escalate',

    'browse' => [
        'per_page' => 12,
        'sort_options' => [
            'newest' => 'Newest',
            'budget_high' => 'Budget: high to low',
            'budget_low' => 'Budget: low to high',
        ],
        // Filters beyond category and work mode are provisional and must be confirmed by the team (FR-26).
    ],

    'applications' => [
        'message_max_length' => 1000,
    ],
];
