<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

class SendPasswordResetLink
{
    /**
     * Send the reset link. The outcome is never revealed to avoid user enumeration.
     */
    public function handle(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }
}
