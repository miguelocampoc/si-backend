<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUser
{
    /**
     * Validate the credentials and issue a Sanctum token.
     *
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
     * @return array{token: string, user: User}
     *
     * @throws ValidationException
     */
    public function handle(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }
}
