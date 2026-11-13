<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreNonPaymentReportRequest extends FormRequest
{
    public function authorize(): bool { $agreement = $this->route('agreement'); return $agreement instanceof Agreement && ($this->user()?->can('reportNonPayment', $agreement) ?? false); }

    public function rules(): array
    {
        $maxFiles = (int) config('taskmate.reports.evidence_max_files', 5);
        $maxKb = (int) config('taskmate.reports.evidence_max_kb', 5120);
        $mimes = implode(',', config('taskmate.reports.evidence_mimes', ['jpg', 'jpeg', 'png', 'pdf']));
        return [
            'description' => ['required', 'string', 'max:'.config('taskmate.reports.statement_max_length', 3000)],
            'evidence' => ['sometimes', 'array', 'max:'.$maxFiles],
            'evidence.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes, 'mimetypes:image/jpeg,image/png,application/pdf'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateEvidenceContent($validator);
        }];
    }

    private function validateEvidenceContent(Validator $validator): void
    {
        $extensions = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'application/pdf' => ['pdf']];
        foreach ($this->file('evidence', []) as $index => $file) {
            if (! $file?->isValid()) continue;
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file->getRealPath()) ?: 'application/octet-stream';
            $clientExtension = strtolower($file->getClientOriginalExtension());
            if (! in_array($clientExtension, $extensions[$mime] ?? [], true)) {
                $validator->errors()->add("evidence.{$index}", 'The file content must match its JPG, PNG, or PDF file extension.');
            }
        }
    }
}
