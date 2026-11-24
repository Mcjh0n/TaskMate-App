<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReportStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof Report && ($this->user()?->can('reply', $report) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('taskmate.reports.evidence_max_files', 5);
        $maxKb = (int) config('taskmate.reports.evidence_max_kb', 5120);
        $mimes = implode(',', config('taskmate.reports.evidence_mimes', ['jpg', 'jpeg', 'png', 'pdf']));

        return [
            'body' => ['required', 'string', 'max:'.config('taskmate.reports.statement_max_length', 3000)],
            'evidence' => ['sometimes', 'array', 'max:'.$maxFiles],
            'evidence.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes, 'mimetypes:image/jpeg,image/png,application/pdf'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $report = $this->route('report');
            $existing = $report instanceof Report ? $report->evidence()->count() : 0;
            if ($existing + count($this->file('evidence', [])) > (int) config('taskmate.reports.evidence_max_files', 5)) {
                $validator->errors()->add('evidence', 'The evidence file limit for this report has been reached.');
            }
            foreach ($this->file('evidence', []) as $index => $file) {
                if (! $file->isValid()) {
                    continue;
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo === false) {
                    $validator->errors()->add("evidence.{$index}", 'The uploaded file could not be checked.');

                    continue;
                }
                $mime = finfo_file($finfo, $file->getRealPath()) ?: 'application/octet-stream';
                finfo_close($finfo);
                $extension = strtolower($file->getClientOriginalExtension());
                $allowed = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'application/pdf' => ['pdf']];
                if (! in_array($extension, $allowed[$mime] ?? [], true)) {
                    $validator->errors()->add("evidence.{$index}", 'The file content must match its JPG, PNG, or PDF file extension.');
                }
            }
        }];
    }
}
