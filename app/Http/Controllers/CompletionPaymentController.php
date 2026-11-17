<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmReceivedRequest;
use App\Http\Requests\MarkDeliveredRequest;
use App\Http\Requests\MarkPaidRequest;
use App\Http\Requests\RecordCompletionRequest;
use App\Http\Requests\ReportNotReceivedRequest;
use App\Models\Agreement;
use App\Services\CompletionWorkflow;
use App\Services\PaymentWorkflow;
use Illuminate\Http\RedirectResponse;

class CompletionPaymentController extends Controller
{
    public function markDelivered(MarkDeliveredRequest $request, Agreement $agreement, CompletionWorkflow $workflow): RedirectResponse
    {
        $workflow->markDelivered($agreement, $request->user(), $request->validated('note'));
        return back()->with('status', 'Your delivery was recorded. The poster will record completion.');
    }

    public function complete(RecordCompletionRequest $request, Agreement $agreement, CompletionWorkflow $workflow): RedirectResponse
    {
        $workflow->recordCompletion($agreement, $request->user(), $request->validated('note'));
        return back()->with('status', 'Task completion was recorded.');
    }

    public function markPaid(MarkPaidRequest $request, Agreement $agreement, PaymentWorkflow $workflow): RedirectResponse
    {
        $event = $workflow->markPaid($agreement, $request->user(), $request->validated());
        $agreement->loadMissing('currentVersion');
        $mismatch = (float) $event->amount !== (float) $agreement->currentVersion->final_amount;
        return back()->with('status', $mismatch
            ? 'Payment was recorded. The amount differs from the agreed amount; both parties can see the difference.'
            : 'Payment was recorded. The worker can now confirm whether it was received.');
    }

    public function confirmReceived(ConfirmReceivedRequest $request, Agreement $agreement, PaymentWorkflow $workflow): RedirectResponse
    {
        $workflow->confirmReceived($agreement, $request->user(), $request->validated('amount'));
        return back()->with('status', 'Your payment response was recorded.');
    }

    public function reportNotReceived(ReportNotReceivedRequest $request, Agreement $agreement, PaymentWorkflow $workflow): RedirectResponse
    {
        $workflow->reportNotReceived($agreement, $request->user());
        return back()->with('status', 'Your payment response was recorded. You may report non-payment if it remains unresolved.');
    }
}
