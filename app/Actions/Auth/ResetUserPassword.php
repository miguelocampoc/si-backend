<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetUserPassword
{
    /**
     * Reset the password using a broker token and revoke every existing API token.
     *
     * @param  array{email: string, token: string, password: string}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data): void
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }
    }
}
