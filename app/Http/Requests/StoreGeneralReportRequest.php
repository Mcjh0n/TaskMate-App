<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneralReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || $user->isAdmin()) {
            return false;
        }

        if ($user->isVerified()) {
            return true;
        }

        $task = $this->route('task');

        return $this->routeIs('tasks.problem-reports.store')
            && $user->canBrowse()
            && $task instanceof Task
            && $task->status === TaskStatus::Approved;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function ($validator): void {
            foreach ((array) $this->file('evidence', []) as $index => $file) {
                if (! $file) {
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
                $extensions = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'application/pdf' => ['pdf']];
                if (! isset($extensions[$mime]) || ! in_array($extension, $extensions[$mime], true)) {
                    $validator->errors()->add("evidence.{$index}", 'The file content must match an allowed image or PDF format.');
                }
            }
        }];
    }
}
