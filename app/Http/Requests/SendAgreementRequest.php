<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        $agreement = $task instanceof Task ? $task->agreement()->first() : null;

        return $agreement !== null && ($this->user()?->can('send', $agreement) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
