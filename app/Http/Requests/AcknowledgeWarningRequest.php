<?php

namespace App\Http\Requests;

use App\Enums\SanctionType;
use App\Models\UserSanction;
use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sanction = $this->route('sanction');
        return $sanction instanceof UserSanction && $sanction->user_id === $this->user()?->id && $sanction->type === SanctionType::Warning && ! $sanction->acknowledged_at;
    }
    public function rules(): array { return []; }
}
