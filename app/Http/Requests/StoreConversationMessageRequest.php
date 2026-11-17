<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');
        return $conversation instanceof Conversation && Gate::allows('send', $conversation);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) $this->merge(['body' => trim($this->input('body'))]);
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:'.(int) config('taskmate.messages.max_length', 1000)]];
    }
}
