<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminReportIndexRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(array_column(ReportType::cases(), 'value'))],
            'status' => ['nullable', Rule::in(array_column(ReportStatus::cases(), 'value'))],
        ];
    }
}
