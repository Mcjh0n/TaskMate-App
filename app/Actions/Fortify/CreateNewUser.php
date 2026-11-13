<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'user_type' => ['required', Rule::in(['student', 'faculty', 'external'])],
            'affiliation' => ['required', 'string', 'max:255'],
            'id_number' => [Rule::requiredIf(in_array($input['user_type'] ?? null, ['student', 'faculty'], true)), 'nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = new User;
            $user->name = $input['name'];
            $user->email = $input['email'];
            $user->password = $input['password'];
            $user->user_type = $input['user_type'];
            $user->affiliation = $input['affiliation'];
            $user->id_number = $input['id_number'] ?? null;
            $user->save();

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }
}
