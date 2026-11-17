<?php

namespace Database\Factories;

use App\Models\ConversationMessage;
use App\Models\Report;
use App\Models\ReportMessageSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportMessageSnapshot> */
class ReportMessageSnapshotFactory extends Factory
{
    protected $model = ReportMessageSnapshot::class;
    public function definition(): array
    {
        $message = ConversationMessage::factory()->create();
        $conversation = $message->conversation()->firstOrFail();
        $report = Report::factory()->create(['task_id' => $conversation->task_id]);
        return [
            'report_id' => $report->id,
            'message_id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_display_name' => $message->sender()->firstOrFail()->name,
            'body' => $message->body,
            'sent_at' => $message->created_at,
            'is_reported_message' => true,
            'created_at' => now(),
        ];
    }
}
