<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneralReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVerified() === true && ! $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $allowed = array_values(array_filter(array_column(ReportType::cases(), 'value'), fn (string $type) => $type !== ReportType::NonPayment->value));

        return [
            'type' => ['required', Rule::in($allowed)],
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
