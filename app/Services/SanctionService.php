<?php

namespace App\Services;

use App\Enums\SanctionType;
use App\Models\User;
use App\Models\UserSanction;
use Illuminate\Support\Collection;

class SanctionService
{
    /** @return Collection<int, UserSanction> */
    public function active(User $user): Collection
    {
        return $user->sanctions()->whereNull('lifted_at')->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')->get();
    }

    public function banned(User $user): bool
    {
        return $this->active($user)->contains(fn (UserSanction $sanction) => $sanction->type === SanctionType::Ban);
    }

    public function suspended(User $user): ?UserSanction
    {
        return $this->active($user)->first(fn (UserSanction $sanction) => $sanction->type === SanctionType::Suspension);
    }

    public function warning(User $user): ?UserSanction
    {
        return $this->active($user)->first(fn (UserSanction $sanction) => $sanction->type === SanctionType::Warning && ! $sanction->acknowledged_at);
    }
}
