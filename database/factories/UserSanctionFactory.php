<?php

namespace Database\Factories;

use App\Enums\SanctionType;
use App\Models\User;
use App\Models\UserSanction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserSanction> */
class UserSanctionFactory extends Factory
{
    protected $model = UserSanction::class;

    public function definition(): array
    {
        $issuer = User::factory()->facultyStaff()->verified();

        return [
            'user_id' => User::factory()->verified(),
            'type' => SanctionType::Warning,
            'reason' => 'Synthetic test warning reason.',
            'starts_at' => now(),
            'issued_by' => $issuer,
        ];
    }
}
