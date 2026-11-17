<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Http\Requests\StoreConversationMessageReportRequest;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\ReportWorkflow;
use Illuminate\Http\RedirectResponse;

class ConversationMessageReportController extends Controller
{
    public function __invoke(StoreConversationMessageReportRequest $request, Conversation $conversation, ConversationMessage $message, ReportWorkflow $workflow): RedirectResponse
    {
        abort_unless($message->conversation_id === $conversation->id, 404);
        $task = $conversation->task()->firstOrFail();
        $application = $conversation->application()->firstOrFail();
        $agreement = Agreement::query()->where('application_id', $application->id)->first();
        $report = $workflow->createProblem(
            $request->user(),
            $message->sender()->firstOrFail(),
            $task,
            $agreement,
            ReportType::from($request->validated('type')),
            $request->validated('description'),
            $request->validated('evidence', []),
            $message,
        );

        return redirect()->route('reports.show', $report)->with('status', 'Your report was submitted for review.');
    }
}
