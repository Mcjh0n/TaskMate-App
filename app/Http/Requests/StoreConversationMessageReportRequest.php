<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreConversationMessageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');
        $message = $this->route('message');

        return $conversation instanceof Conversation && $message instanceof ConversationMessage
            && Gate::allows('reportMessage', [$conversation, $message]);
    }

    public function rules(): array
    {
        $relevant = ['harassment', 'unsafe_behavior', 'off_platform_transaction', 'rule_bypass_request', 'misleading_description', 'fraudulent_post', 'other'];
        $types = array_values(array_intersect($relevant, array_keys((array) config('taskmate.reports.types', []))));

        return [
            'type' => ['required', Rule::in($types)],
            'description' => ['required', 'string', 'max:'.(int) config('taskmate.reports.description_max_length', 3000)],
            'evidence' => ['sometimes', 'array', 'max:'.(int) config('taskmate.reports.evidence_max_files', 5)],
            'evidence.*' => ['file', 'max:'.(int) config('taskmate.reports.evidence_max_kb', 5120), 'mimes:'.implode(',', config('taskmate.reports.evidence_mimes', ['jpg', 'jpeg', 'png', 'pdf'])), 'mimetypes:image/jpeg,image/png,application/pdf'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            foreach ((array) $this->file('evidence', []) as $index => $file) {
                if (! $file) {
                    continue;
                }
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file->getRealPath());
                $extension = strtolower($file->getClientOriginalExtension());
                $extensions = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'application/pdf' => ['pdf']];
                if (! isset($extensions[$mime]) || ! in_array($extension, $extensions[$mime], true)) {
                    $validator->errors()->add("evidence.{$index}", 'The file content must match an allowed image or PDF format.');
                }
            }
        }];
    }
}
