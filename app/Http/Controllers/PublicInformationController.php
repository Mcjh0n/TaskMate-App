<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicInformationController extends Controller
{
    public function __invoke(string $page): Response
    {
        abort_unless(in_array($page, ['how-it-works', 'task-guidelines', 'safety-and-limits', 'privacy'], true), 404);
        $titles = [
            'how-it-works' => 'How TaskMate works',
            'task-guidelines' => 'Task Guidelines',
            'safety-and-limits' => 'Safety and Limits',
            'privacy' => 'Privacy Notice',
        ];

        return Inertia::render('public/info', [
            'page' => $page,
            'title' => $titles[$page],
            'guidelines' => $page === 'task-guidelines' ? config('taskmate.task_guidelines', []) : [],
            'privacyDraft' => $page === 'privacy' ? file_get_contents(base_path('docs/privacy-and-data-handling.md')) : null,
        ]);
    }
}
